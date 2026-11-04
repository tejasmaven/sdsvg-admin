<?php
/**
 * FamilyController — family (group) CRUD
 */
class FamilyController extends BaseController
{
    private GroupModel        $groupModel;
    private MemberModel       $memberModel;
    private GenderModel       $genderModel;
    private RelationshipModel $relModel;

    public function __construct()
    {
        parent::__construct();
        $this->groupModel  = new GroupModel();
        $this->memberModel = new MemberModel();
        $this->genderModel = new GenderModel();
        $this->relModel    = new RelationshipModel();
    }

    /**
     * GET /families
     */
    public function index(): void
    {
        $search = $this->request->get('search', '');
        $page   = max(1, (int) $this->request->get('page', 1));

        $result     = $this->groupModel->findAll($search, $page);
        $totalPages = (int) ceil($result['total'] / 25);

        $this->render('families/index', [
            'families'   => $result['rows'],
            'search'     => $search,
            'page'       => $page,
            'totalPages' => max(1, $totalPages),
            'total'      => $result['total'],
            'pageTitle'  => 'Families',
        ]);
    }

    /**
     * GET /families/create
     */
    public function create(): void
    {
        $genders       = $this->genderModel->getActive();
        $relationships = $this->relModel->getActive();
        $selfId        = $this->relModel->getSelfId();

        $this->render('families/create', [
            'genders'       => $genders,
            'relationships' => $relationships,
            'selfId'        => $selfId,
            'old'           => [],
            'errors'        => [],
            'pageTitle'     => 'Add Family',
        ]);
    }

    /**
     * POST /families
     */
    public function store(): void
    {
        $this->request->validateCsrf();

        $address             = $this->request->post('address', '');
        $communicationAddress = $this->request->post('communication_address', '');
        $data = [
            'title'           => $this->request->post('title', ''),
            'first_name'      => $this->request->post('first_name', ''),
            'middle_name'     => $this->request->post('middle_name', ''),
            'last_name'       => $this->request->post('last_name', ''),
            'dob'             => $this->request->post('dob', ''),
            'gender_id'       => $this->request->post('gender_id', ''),
            'relationship_id' => $this->request->post('relationship_id', ''),
            'mobile'          => $this->request->post('mobile', ''),
            'email'           => $this->request->post('email', ''),
            'education'       => $this->request->post('education', ''),
        ];

        $errors = $this->validateMemberFields($data);

        if (!empty($errors)) {
            $genders       = $this->genderModel->getActive();
            $relationships = $this->relModel->getActive();
            $selfId        = $this->relModel->getSelfId();
            $this->render('families/create', [
                'genders'       => $genders,
                'relationships' => $relationships,
                'selfId'        => $selfId,
                'old'           => array_merge(['address' => $address, 'communication_address' => $communicationAddress], $data),
                'errors'        => $errors,
                'pageTitle'     => 'Add Family',
            ]);
            return;
        }

        // Wrap in transaction
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $groupId  = $this->groupModel->create($address ?: null, $communicationAddress ?: null);
            $memberId = $this->memberModel->create($groupId, 'P', $data);
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        $this->flash('success', 'Family created successfully.');
        $this->redirect('/families/' . $groupId . '?new=1');
    }

    /**
     * GET /families/{id}
     */
    public function show(int $id): void
    {
        $group = $this->groupModel->findById($id);
        if (!$group) $this->abort(404, 'Family not found.');

        $members         = $this->memberModel->findByGroup($id);
        $primaryMember   = null;
        $secondaryMembers = [];

        foreach ($members as $m) {
            if ($m['type'] === 'P') {
                $primaryMember = $m;
            } else {
                $secondaryMembers[] = $m;
            }
        }

        $this->render('families/show', [
            'group'            => $group,
            'members'          => $members,          // all members in display_order
            'primaryMember'    => $primaryMember,
            'secondaryMembers' => $secondaryMembers,
            'isNew'            => !empty($_GET['new']),
            'pageTitle'        => $primaryMember
                                    ? htmlspecialchars($primaryMember['last_name'], ENT_QUOTES, 'UTF-8') . ' Family'
                                    : 'Family #' . $id,
        ]);
    }

    /**
     * GET /families/{id}/edit
     */
    public function edit(int $id): void
    {
        $group = $this->groupModel->findById($id);
        if (!$group) $this->abort(404, 'Family not found.');

        $this->render('families/edit', [
            'group'     => $group,
            'errors'    => [],
            'pageTitle' => 'Edit Family Address',
        ]);
    }

    /**
     * POST /families/{id}/edit
     */
    public function update(int $id): void
    {
        $this->request->validateCsrf();

        $group = $this->groupModel->findById($id);
        if (!$group) $this->abort(404, 'Family not found.');

        $address              = $this->request->post('address', '');
        $communicationAddress = $this->request->post('communication_address', '');
        $this->groupModel->update($id, $address ?: null, $communicationAddress ?: null);

        $this->flash('success', 'Family address updated.');
        $this->redirect('/families/' . $id);
    }

    /**
     * POST /families/{id}/delete
     */
    public function destroy(int $id): void
    {
        $this->request->validateCsrf();

        $group = $this->groupModel->findById($id);
        if (!$group) $this->abort(404, 'Family not found.');

        $confirmName = $this->request->post('confirm_name', '');
        $headLastName = $group['head_last_name'] ?? '';

        if (strcasecmp(trim($confirmName), trim($headLastName)) !== 0) {
            $this->flash('error', 'Name did not match. Family was NOT deleted.');
            $this->redirect('/families/' . $id);
        }

        $this->groupModel->delete($id);
        $this->flash('success', 'Family and all members deleted.');
        $this->redirect('/families');
    }

    /**
     * POST /families/{id}/toggle-city
     */
    public function toggleCity(int $id): void
    {
        $this->request->validateCsrf();

        $group = $this->groupModel->findById($id);
        if (!$group) $this->abort(404, 'Family not found.');

        $this->groupModel->toggleCity($id);

        // Allow callers to specify where to go after the toggle
        $back = $this->request->post('redirect', '');
        $this->redirect($back === '/pdf/preview2' ? '/pdf/preview2' : '/families/' . $id);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    /**
     * Validate primary member fields; return $errors array (empty = valid).
     */
    private function validateMemberFields(array $data): array
    {
        $errors = [];

        if ($e = Validator::required($data['first_name'], 'First Name'))  $errors['first_name']      = $e;
        if ($e = Validator::required($data['last_name'], 'Last Name'))    $errors['last_name']       = $e;
        if ($e = Validator::dateFormat($data['dob']))                     $errors['dob']             = $e;
        if ($e = Validator::mobile($data['mobile']))                      $errors['mobile']          = $e;
        if ($e = Validator::email($data['email']))                        $errors['email']           = $e;

        // Validate gender_id
        if (empty($data['gender_id'])) {
            $errors['gender_id'] = 'Gender is required.';
        } else {
            $gm = new GenderModel();
            if (!$gm->isValidId((int) $data['gender_id'])) {
                $errors['gender_id'] = 'Please select a valid gender.';
            }
        }

        // Validate relationship_id
        if (empty($data['relationship_id'])) {
            $errors['relationship_id'] = 'Relationship is required.';
        } else {
            $rm = new RelationshipModel();
            if (!$rm->isValidId((int) $data['relationship_id'])) {
                $errors['relationship_id'] = 'Please select a valid relationship.';
            }
        }

        return $errors;
    }
}
