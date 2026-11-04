<?php
/**
 * ReviewController — admin review of public edit requests (auth-guarded).
 *
 * Approval is field-level: the admin ticks which proposed values to accept and
 * only those are written to the master record. Status becomes 'approved' (all
 * changed fields accepted), 'partial' (some), or 'rejected'.
 */
class ReviewController extends BaseController
{
    private const MEMBER_FIELDS = [
        'title', 'first_name', 'middle_name', 'last_name',
        'dob', 'gender_id', 'relationship_id', 'mobile', 'email', 'education',
    ];
    private const GROUP_FIELDS = ['address', 'communication_address', 'is_in_surat'];

    private EditRequestModel  $editModel;
    private MemberModel       $memberModel;
    private GroupModel        $groupModel;
    private GenderModel       $genderModel;
    private RelationshipModel $relModel;

    public function __construct()
    {
        parent::__construct();
        $this->editModel   = new EditRequestModel();
        $this->memberModel = new MemberModel();
        $this->groupModel  = new GroupModel();
        $this->genderModel = new GenderModel();
        $this->relModel    = new RelationshipModel();
    }

    /** GET /reviews — list requests (pending by default, ?status=all). */
    public function index(): void
    {
        $status = $this->request->get('status', 'pending');
        if (!in_array($status, ['pending', 'approved', 'rejected', 'partial', 'all'], true)) {
            $status = 'pending';
        }
        $this->render('reviews/index', [
            'requests'   => $this->editModel->findAll($status),
            'status'     => $status,
            'pendingCount' => $this->editModel->countPending(),
            'pageTitle'  => 'Edit Requests',
        ]);
    }

    /** GET /reviews/{id} — field-level diff view. */
    public function show(int $id): void
    {
        $req = $this->editModel->findById($id);
        if (!$req) {
            $this->abort(404, 'Request not found.');
        }

        // Build a readable label map for gender/relationship IDs.
        $genders = $relationships = [];
        foreach ($this->genderModel->getAll() as $g) $genders[(string)$g['gender_id']] = $g['name'];
        foreach ($this->relModel->getAll() as $r)    $relationships[(string)$r['relationship_id']] = $r['name'];

        $fields = $req['target_type'] === 'member' ? self::MEMBER_FIELDS : self::GROUP_FIELDS;

        $this->render('reviews/show', [
            'req'           => $req,
            'fields'        => $fields,
            'genders'       => $genders,
            'relationships' => $relationships,
            'pageTitle'     => 'Review Request #' . $id,
        ]);
    }

    /** POST /reviews/{id}/approve — apply the ticked fields only. */
    public function approve(int $id): void
    {
        $this->request->validateCsrf();

        $req = $this->editModel->findById($id);
        if (!$req) {
            $this->abort(404, 'Request not found.');
        }
        if ($req['status'] !== 'pending') {
            $this->flash('warning', 'This request has already been reviewed.');
            $this->redirect('/reviews');
        }

        $accepted = (array) ($this->request->post('accept', []));
        $note     = (string) $this->request->post('note', '');
        $payload  = $req['payload'];
        $fields   = $req['target_type'] === 'member' ? self::MEMBER_FIELDS : self::GROUP_FIELDS;
        $accepted = array_values(array_intersect($fields, $accepted)); // whitelist

        if (empty($accepted)) {
            $this->flash('warning', 'No fields were selected. Use Reject to dismiss, or tick fields to accept.');
            $this->redirect('/reviews/' . $id);
        }

        if ($req['target_type'] === 'member') {
            $member = $this->memberModel->findById((int) $req['member_id']);
            if (!$member) {
                $this->editModel->setStatus($id, 'rejected', 'Target member no longer exists.');
                $this->flash('error', 'The member no longer exists; request marked rejected.');
                $this->redirect('/reviews');
            }
            $merged = [];
            foreach (self::MEMBER_FIELDS as $f) {
                $merged[$f] = in_array($f, $accepted, true)
                    ? ($payload[$f] ?? '')
                    : (string) ($member[$f] ?? '');
            }
            // Normalise empties to NULL-friendly values handled by the model
            $this->memberModel->update((int) $req['member_id'], $merged);
        } else {
            $group = $this->groupModel->findById((int) $req['group_id']);
            if (!$group) {
                $this->editModel->setStatus($id, 'rejected', 'Target family no longer exists.');
                $this->flash('error', 'The family no longer exists; request marked rejected.');
                $this->redirect('/reviews');
            }
            $address = in_array('address', $accepted, true)
                ? ($payload['address'] ?? '')
                : (string) ($group['address'] ?? '');
            $comm = in_array('communication_address', $accepted, true)
                ? ($payload['communication_address'] ?? '')
                : (string) ($group['communication_address'] ?? '');
            $this->groupModel->update((int) $req['group_id'], $address ?: null, $comm ?: null);
            if (in_array('is_in_surat', $accepted, true)) {
                $this->groupModel->setInSurat((int) $req['group_id'], (int) ($payload['is_in_surat'] ?? 1));
            }
        }

        // partial if the request proposed more changed fields than were accepted
        $changed = $this->changedFields($req, $fields);
        $status  = (count(array_diff($changed, $accepted)) > 0) ? 'partial' : 'approved';
        $this->editModel->setStatus($id, $status, $note);

        $this->flash('success', 'Request applied (' . count($accepted) . ' field(s) accepted).');
        $this->redirect('/reviews');
    }

    /** POST /reviews/{id}/reject — dismiss without changes. */
    public function reject(int $id): void
    {
        $this->request->validateCsrf();

        $req = $this->editModel->findById($id);
        if (!$req) {
            $this->abort(404, 'Request not found.');
        }
        if ($req['status'] !== 'pending') {
            $this->flash('warning', 'This request has already been reviewed.');
            $this->redirect('/reviews');
        }
        $note = (string) $this->request->post('note', '');
        $this->editModel->setStatus($id, 'rejected', $note);
        $this->flash('success', 'Request rejected.');
        $this->redirect('/reviews');
    }

    /** Field names whose proposed value differs from the original snapshot. */
    private function changedFields(array $req, array $fields): array
    {
        $changed = [];
        foreach ($fields as $f) {
            $old = (string) ($req['original'][$f] ?? '');
            $new = (string) ($req['payload'][$f] ?? '');
            if ($old !== $new) {
                $changed[] = $f;
            }
        }
        return $changed;
    }
}
