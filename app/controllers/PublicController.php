<?php
/**
 * PublicController — unauthenticated community portal.
 *
 * Flow: landing (search + captcha) → results (names only) → family view
 * (names only) → edit request (prefilled, captcha) → stored for admin review.
 *
 * Security:
 *  - every page emits noindex / nofollow (meta + X-Robots-Tag) to keep search
 *    engines and crawlers out;
 *  - search and edit-submit are gated by a self-built captcha (see Captcha);
 *  - all input is bound via PDO prepared statements and escaped on output;
 *  - request metadata (IP, UA, language, referer, time) is recorded on submit.
 */
class PublicController extends BaseController
{
    /** Member fields a visitor may propose changes to. */
    private const MEMBER_FIELDS = [
        'title', 'first_name', 'middle_name', 'last_name',
        'dob', 'gender_id', 'relationship_id', 'mobile', 'email', 'education',
    ];

    /** Group (address) fields a visitor may propose changes to. */
    private const GROUP_FIELDS = ['address', 'communication_address', 'is_in_surat'];

    /** How long (seconds) a verified family stays unlocked in the session. */
    private const VERIFY_TTL = 1800;
    /** Max failed gate attempts per IP within VERIFY_WINDOW_MIN before blocking. */
    private const MAX_VERIFY_FAILS = 8;
    private const VERIFY_WINDOW_MIN = 15;
    /** Max edit submissions per IP per day. */
    private const MAX_EDITS_PER_DAY = 30;

    private GroupModel          $groupModel;
    private MemberModel         $memberModel;
    private GenderModel         $genderModel;
    private RelationshipModel   $relModel;
    private EditRequestModel    $editModel;
    private PublicThrottleModel $throttle;

    public function __construct()
    {
        parent::__construct();
        $this->groupModel  = new GroupModel();
        $this->memberModel = new MemberModel();
        $this->genderModel = new GenderModel();
        $this->relModel    = new RelationshipModel();
        $this->editModel   = new EditRequestModel();
        $this->throttle    = new PublicThrottleModel();
        $this->noIndexHeaders();
    }

    /** Discourage indexing/archiving at the HTTP level. */
    private function noIndexHeaders(): void
    {
        header('X-Robots-Tag: noindex, nofollow, noarchive, noimageindex');
        header('Referrer-Policy: no-referrer');
    }

    // ── Landing + search ────────────────────────────────────────────────────

    /** GET / — landing page with search box + captcha. */
    public function landing(): void
    {
        Captcha::generate('search');
        $this->render('public/landing', [
            'error'     => $_SESSION['public_error'] ?? '',
            'q'         => '',
            'results'   => null,
            'pageTitle' => 'Community Directory',
        ], 'public');
        unset($_SESSION['public_error']);
    }

    /** POST /search — validate captcha, run a names-only search. */
    public function search(): void
    {
        $this->request->validateCsrf();

        $q       = (string) $this->request->post('q', '');
        $captcha = (string) $this->request->post('captcha', '');

        if (!Captcha::check('search', $captcha)) {
            Captcha::generate('search');
            $this->render('public/landing', [
                'error'     => 'The code you entered was incorrect. Please try again.',
                'q'         => $q,
                'results'   => null,
                'pageTitle' => 'Community Directory',
            ], 'public');
            return;
        }

        $q = trim($q);
        $results = ($q === '') ? [] : $this->groupModel->publicSearch($q);

        Captcha::generate('search'); // fresh code for the next search
        $this->render('public/landing', [
            'error'     => ($q === '') ? 'Please type a name or surname to search.' : '',
            'q'         => $q,
            'results'   => $results,
            'pageTitle' => 'Community Directory',
        ], 'public');
    }

    // ── Verification gate ──────────────────────────────────────────────────────
    //
    // Full family details (and all edit forms) sit behind a knowledge challenge
    // so a stranger cannot browse or "correct" a family they know nothing about.
    // Passing it once unlocks that family for the session (VERIFY_TTL). The gate
    // is IP-rate-limited to stop brute-forcing the last-4-digit answer.

    /** Is this family unlocked in the current session (and not expired)? */
    private function isVerified(int $gid): bool
    {
        $ts = $_SESSION['fam_ok'][$gid] ?? 0;
        return $ts > 0 && (time() - (int) $ts) < self::VERIFY_TTL;
    }

    /** Mark a family as verified for this session. */
    private function markVerified(int $gid): void
    {
        $_SESSION['fam_ok'][$gid] = time();
    }

    /** Honeypot: a hidden field bots tend to fill; humans leave it empty. */
    private function isBot(): bool
    {
        return trim((string) $this->request->post('website', '')) !== '';
    }

    /** Decide which challenge a family uses: mobile last-4, else head's first name. */
    private function familyChallenge(int $gid): array
    {
        foreach ($this->memberModel->familyMobiles($gid) as $m) {
            if (preg_match('/\d{4,}/', $m)) {
                return ['type' => 'mobile'];
            }
        }
        return ['type' => 'name'];
    }

    /** Validate a gate answer against the family's existing data. */
    private function checkChallenge(int $gid, string $answer): bool
    {
        if ($this->familyChallenge($gid)['type'] === 'mobile') {
            $digits = preg_replace('/\D+/', '', $answer);
            if (strlen($digits) < 4) {
                return false;
            }
            $last4 = substr($digits, -4);
            foreach ($this->memberModel->familyMobiles($gid) as $m) {
                if (preg_match_all('/\d{4,}/', $m, $runs)) {
                    foreach ($runs[0] as $run) {
                        if (substr($run, -4) === $last4) {
                            return true;
                        }
                    }
                }
            }
            return false;
        }

        // Name tier — compare to the head of family's first name.
        $group = $this->groupModel->findById($gid);
        $head  = trim((string) ($group['head_first_name'] ?? ''));
        return $head !== '' && strcasecmp(trim($answer), $head) === 0;
    }

    /** Render the gate screen for a family. */
    private function renderVerify(int $gid, array $group, string $error = ''): void
    {
        $this->render('public/verify', [
            'group'     => $group,
            'challenge' => $this->familyChallenge($gid),
            'error'     => $error,
            'pageTitle' => 'Verify',
        ], 'public');
    }

    /** POST /family/{id}/verify — check the knowledge challenge. */
    public function verifyFamily(int $id): void
    {
        $this->request->validateCsrf();

        $group = $this->groupModel->findById($id);
        if (!$group) {
            $this->abort(404, 'Family not found.');
        }

        $ip = $this->request->ip();

        // Honeypot → silent bot bounce (still counts against the IP).
        if ($this->isBot()) {
            $this->throttle->record($ip, 'verify');
            $this->renderVerify($id, $group, 'Verification failed. Please try again.');
            return;
        }

        // Rate limit the brute-forceable answer.
        if ($this->throttle->countRecent($ip, 'verify', self::VERIFY_WINDOW_MIN) >= self::MAX_VERIFY_FAILS) {
            $this->renderVerify($id, $group,
                'Too many attempts. Please wait a few minutes and try again.');
            return;
        }

        $answer = (string) $this->request->post('answer', '');
        if ($this->checkChallenge($id, $answer)) {
            $this->throttle->clear($ip, 'verify');
            $this->markVerified($id);
            $this->redirect('/family/' . $id);
        }

        $this->throttle->record($ip, 'verify');
        $this->renderVerify($id, $group,
            'That did not match our records. Please check and try again.');
    }

    // ── Family view (full details — behind the gate) ────────────────────────────

    /** GET /family/{id} — gate first; once verified, show full details + edit links. */
    public function viewFamily(int $id): void
    {
        $group = $this->groupModel->findById($id);
        if (!$group) {
            $this->abort(404, 'Family not found.');
        }

        if (!$this->isVerified($id)) {
            $this->renderVerify($id, $group);
            return;
        }

        $members = $this->memberModel->findByGroup($id);
        $this->render('public/family', [
            'group'     => $group,
            'members'   => $members,
            'pageTitle' => 'Family Details',
        ], 'public');
    }

    // ── Edit a member ─────────────────────────────────────────────────────────

    /** GET /member/{id}/edit — prefilled form (family must be verified). */
    public function editMemberForm(int $id): void
    {
        $member = $this->memberModel->findById($id);
        if (!$member) {
            $this->abort(404, 'Member not found.');
        }
        if (!$this->isVerified((int) $member['group_id'])) {
            $this->redirect('/family/' . (int) $member['group_id']);
        }
        $this->render('public/edit_member', [
            'member'        => $member,
            'genders'       => $this->genderModel->getActive(),
            'relationships' => $this->relModel->getActive(),
            'errors'        => [],
            'old'           => $member,
            'pageTitle'     => 'Request a Correction',
        ], 'public');
    }

    /** POST /member/{id}/edit — store a pending request (family must be verified). */
    public function submitMember(int $id): void
    {
        $this->request->validateCsrf();

        $member = $this->memberModel->findById($id);
        if (!$member) {
            $this->abort(404, 'Member not found.');
        }
        $gid = (int) $member['group_id'];
        if (!$this->isVerified($gid)) {
            $this->redirect('/family/' . $gid);
        }

        // Bot (honeypot) or over the per-IP daily cap → bounce back silently.
        $ip = $this->request->ip();
        if ($this->isBot()
            || $this->throttle->countRecent($ip, 'edit', 1440) >= self::MAX_EDITS_PER_DAY) {
            $this->redirect('/family/' . $gid);
        }

        $data = [];
        foreach (self::MEMBER_FIELDS as $f) {
            $data[$f] = trim((string) $this->request->post($f, ''));
        }

        $errors = $this->validateMember($data);

        if (!empty($errors)) {
            $this->render('public/edit_member', [
                'member'        => $member,
                'genders'       => $this->genderModel->getActive(),
                'relationships' => $this->relModel->getActive(),
                'errors'        => $errors,
                'old'           => array_merge($member, $data),
                'pageTitle'     => 'Request a Correction',
            ], 'public');
            return;
        }

        // Snapshot current values for the admin diff
        $original = [];
        foreach (self::MEMBER_FIELDS as $f) {
            $original[$f] = (string) ($member[$f] ?? '');
        }

        $this->editModel->create(array_merge([
            'target_type' => 'member',
            'group_id'    => $gid,
            'member_id'   => (int) $member['member_id'],
            'original'    => $original,
            'payload'     => $data,
        ], $this->requestMeta()));
        $this->throttle->record($ip, 'edit');

        // PRG back to the family so the visitor can correct other members.
        $this->redirect('/family/' . $gid . '?submitted=1');
    }

    // ── Edit the family address ────────────────────────────────────────────────

    /** GET /family/{id}/edit-address — prefilled address form (family must be verified). */
    public function editAddressForm(int $id): void
    {
        $group = $this->groupModel->findById($id);
        if (!$group) {
            $this->abort(404, 'Family not found.');
        }
        if (!$this->isVerified($id)) {
            $this->redirect('/family/' . $id);
        }
        $this->render('public/edit_address', [
            'group'     => $group,
            'errors'    => [],
            'old'       => $group,
            'pageTitle' => 'Request an Address Correction',
        ], 'public');
    }

    /** POST /family/{id}/edit-address — store a request (family must be verified). */
    public function submitAddress(int $id): void
    {
        $this->request->validateCsrf();

        $group = $this->groupModel->findById($id);
        if (!$group) {
            $this->abort(404, 'Family not found.');
        }
        if (!$this->isVerified($id)) {
            $this->redirect('/family/' . $id);
        }

        // Bot (honeypot) or over the per-IP daily cap → bounce back silently.
        $ip = $this->request->ip();
        if ($this->isBot()
            || $this->throttle->countRecent($ip, 'edit', 1440) >= self::MAX_EDITS_PER_DAY) {
            $this->redirect('/family/' . $id);
        }

        $data = [
            'address'               => trim((string) $this->request->post('address', '')),
            'communication_address' => trim((string) $this->request->post('communication_address', '')),
            'is_in_surat'           => $this->request->post('is_in_surat', '1') === '1' ? '1' : '0',
        ];

        $original = [
            'address'               => (string) ($group['address'] ?? ''),
            'communication_address' => (string) ($group['communication_address'] ?? ''),
            'is_in_surat'           => (string) ($group['is_in_surat'] ?? '1'),
        ];

        $this->editModel->create(array_merge([
            'target_type' => 'group',
            'group_id'    => (int) $group['group_id'],
            'member_id'   => null,
            'original'    => $original,
            'payload'     => $data,
        ], $this->requestMeta()));
        $this->throttle->record($ip, 'edit');

        // PRG back to the family so the visitor can keep correcting details.
        $this->redirect('/family/' . (int) $group['group_id'] . '?submitted=1');
    }

    // ── Captcha image ──────────────────────────────────────────────────────────

    /** GET /captcha?for=search — stream the PNG (search gate only). */
    public function captcha(): void
    {
        Captcha::render('search');
        exit;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Collect request metadata for tracking (bounded, server-side only). */
    private function requestMeta(): array
    {
        return [
            'ip_address'      => $this->request->ip(),
            'user_agent'      => $_SERVER['HTTP_USER_AGENT']      ?? '',
            'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            'referer'         => $_SERVER['HTTP_REFERER']         ?? '',
        ];
    }

    /** Light validation — required name fields + format checks reused from admin. */
    private function validateMember(array $data): array
    {
        $errors = [];
        if ($e = Validator::required($data['first_name'], 'First Name')) $errors['first_name'] = $e;
        if ($e = Validator::required($data['last_name'],  'Last Name'))  $errors['last_name']  = $e;
        if ($data['dob'] !== '' && ($e = Validator::dateFormat($data['dob']))) $errors['dob'] = $e;
        if ($data['email'] !== '' && ($e = Validator::email($data['email']))) $errors['email'] = $e;

        if ($data['gender_id'] !== '' && !$this->genderModel->isValidId((int) $data['gender_id'])) {
            $errors['gender_id'] = 'Please choose a valid gender.';
        }
        if ($data['relationship_id'] !== '' && !$this->relModel->isValidId((int) $data['relationship_id'])) {
            $errors['relationship_id'] = 'Please choose a valid relationship.';
        }
        return $errors;
    }
}
