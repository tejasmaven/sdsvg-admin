<?php
/**
 * MemberController — member CRUD within a family
 */
class MemberController extends BaseController
{
    private MemberModel       $memberModel;
    private GroupModel        $groupModel;
    private GenderModel       $genderModel;
    private RelationshipModel $relModel;

    public function __construct()
    {
        parent::__construct();
        $this->memberModel = new MemberModel();
        $this->groupModel  = new GroupModel();
        $this->genderModel = new GenderModel();
        $this->relModel    = new RelationshipModel();
    }

    /**
     * GET /families/{id}/members/add
     */
    public function create(int $groupId): void
    {
        $group = $this->groupModel->findById($groupId);
        if (!$group) $this->abort(404, 'Family not found.');

        $this->render('members/create', [
            'group'         => $group,
            'genders'       => $this->genderModel->getActive(),
            'relationships' => $this->relModel->getActiveExcludingSelf(),
            'old'           => [],
            'errors'        => [],
            'pageTitle'     => 'Add Member',
        ]);
    }

    /**
     * POST /families/{id}/members/add
     */
    public function store(int $groupId): void
    {
        $this->request->validateCsrf();

        $group = $this->groupModel->findById($groupId);
        if (!$group) $this->abort(404, 'Family not found.');

        $data = $this->collectMemberData();
        $errors = $this->validateMemberFields($data, false);

        if (!empty($errors)) {
            $this->render('members/create', [
                'group'         => $group,
                'genders'       => $this->genderModel->getActive(),
                'relationships' => $this->relModel->getActiveExcludingSelf(),
                'old'           => $data,
                'errors'        => $errors,
                'pageTitle'     => 'Add Member',
            ]);
            return;
        }

        $this->memberModel->create($groupId, 'S', $data);
        $this->flash('success', 'Member added successfully.');
        $this->redirect('/families/' . $groupId);
    }

    /**
     * GET /members/{id}/edit
     */
    public function edit(int $id): void
    {
        $member = $this->memberModel->findById($id);
        if (!$member) $this->abort(404, 'Member not found.');

        $group         = $this->groupModel->findById($member['group_id']);
        $relationships = $member['type'] === 'P'
            ? $this->relModel->getActive()
            : $this->relModel->getActiveExcludingSelf();

        $this->render('members/edit', [
            'member'        => $member,
            'group'         => $group,
            'genders'       => $this->genderModel->getActive(),
            'relationships' => $relationships,
            'old'           => $member,
            'errors'        => [],
            'pageTitle'     => 'Edit Member',
        ]);
    }

    /**
     * POST /members/{id}/edit
     */
    public function update(int $id): void
    {
        $this->request->validateCsrf();

        $member = $this->memberModel->findById($id);
        if (!$member) $this->abort(404, 'Member not found.');

        $data   = $this->collectMemberData();
        $isPrimary = ($member['type'] === 'P');
        $errors = $this->validateMemberFields($data, $isPrimary);

        if (!empty($errors)) {
            $group         = $this->groupModel->findById($member['group_id']);
            $relationships = $isPrimary
                ? $this->relModel->getActive()
                : $this->relModel->getActiveExcludingSelf();

            $this->render('members/edit', [
                'member'        => $member,
                'group'         => $group,
                'genders'       => $this->genderModel->getActive(),
                'relationships' => $relationships,
                'old'           => array_merge($member, $data),
                'errors'        => $errors,
                'pageTitle'     => 'Edit Member',
            ]);
            return;
        }

        $this->memberModel->update($id, $data);
        $this->flash('success', 'Member updated successfully.');
        $this->redirect('/families/' . $member['group_id']);
    }

    /**
     * POST /members/{id}/delete
     */
    public function destroy(int $id): void
    {
        $this->request->validateCsrf();

        $member = $this->memberModel->findById($id);
        if (!$member) $this->abort(404, 'Member not found.');

        $groupId = $member['group_id'];
        $this->memberModel->delete($id);
        $this->flash('success', 'Member deleted.');
        $this->redirect('/families/' . $groupId);
    }

    /**
     * POST /members/{id}/move-up
     */
    public function moveUp(int $id): void
    {
        $this->request->validateCsrf();
        $member = $this->memberModel->findById($id);
        if (!$member) $this->abort(404, 'Member not found.');

        $this->memberModel->moveUp($id, (int) $member['group_id']);
        $this->redirect('/families/' . $member['group_id']);
    }

    /**
     * POST /members/{id}/move-down
     */
    public function moveDown(int $id): void
    {
        $this->request->validateCsrf();
        $member = $this->memberModel->findById($id);
        if (!$member) $this->abort(404, 'Member not found.');

        $this->memberModel->moveDown($id, (int) $member['group_id']);
        $this->redirect('/families/' . $member['group_id']);
    }

    /**
     * POST /members/{id}/promote
     */
    public function promote(int $id): void
    {
        $this->request->validateCsrf();

        $member = $this->memberModel->findById($id);
        if (!$member) $this->abort(404, 'Member not found.');

        $this->memberModel->promoteToHead($id, (int) $member['group_id']);
        $this->flash('success', 'Member promoted to Head of Family.');
        $this->redirect('/families/' . $member['group_id']);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function collectMemberData(): array
    {
        return [
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
    }

    private function validateMemberFields(array $data, bool $allowSelf): array
    {
        $errors = [];

        if ($e = Validator::required($data['first_name'], 'First Name')) $errors['first_name'] = $e;
        if ($e = Validator::required($data['last_name'],  'Last Name'))  $errors['last_name']  = $e;
        if ($e = Validator::dateFormat($data['dob']))                    $errors['dob']         = $e;
        if ($e = Validator::mobile($data['mobile']))                     $errors['mobile']      = $e;
        if ($e = Validator::email($data['email']))                       $errors['email']       = $e;

        if (empty($data['gender_id'])) {
            $errors['gender_id'] = 'Gender is required.';
        } else {
            $gm = new GenderModel();
            if (!$gm->isValidId((int) $data['gender_id'])) {
                $errors['gender_id'] = 'Please select a valid gender.';
            }
        }

        if (empty($data['relationship_id'])) {
            $errors['relationship_id'] = 'Relationship is required.';
        } else {
            $rm = new RelationshipModel();
            if (!$rm->isValidId((int) $data['relationship_id'])) {
                $errors['relationship_id'] = 'Please select a valid relationship.';
            }
            // Secondary members cannot have 'Self' relationship
            if (!$allowSelf) {
                $selfId = $rm->getSelfId();
                if ($selfId && (int) $data['relationship_id'] === $selfId) {
                    $errors['relationship_id'] = "Secondary members cannot have 'Self' relationship.";
                }
            }
        }

        return $errors;
    }
}
