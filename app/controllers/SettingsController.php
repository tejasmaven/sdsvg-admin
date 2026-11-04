<?php
/**
 * SettingsController — PDF settings, relationships, genders
 */
class SettingsController extends BaseController
{
    private SettingModel      $settingModel;
    private RelationshipModel $relModel;
    private GenderModel       $genderModel;

    public function __construct()
    {
        parent::__construct();
        $this->settingModel = new SettingModel();
        $this->relModel     = new RelationshipModel();
        $this->genderModel  = new GenderModel();
    }

    /**
     * GET /settings
     */
    public function index(): void
    {
        $this->render('settings/index', [
            'settings'      => $this->settingModel->getAll(),
            'relationships' => $this->relModel->getAll(),
            'genders'       => $this->genderModel->getAll(),
            'pageTitle'     => 'Settings',
        ]);
    }

    /**
     * POST /settings — update PDF content settings
     */
    public function update(): void
    {
        $this->request->validateCsrf();

        // Each settings tab submits its own form with only its own fields.
        // Only persist keys that are actually present in this POST so one tab's
        // save never clears another tab's stored values.
        $fields = ['community_full_name', 'about_text', 'edition_date_override'];
        foreach ($fields as $field) {
            $value = $this->request->post($field, null);
            if ($value !== null) {
                $this->settingModel->set($field, $value);
            }
        }

        // Committee rows come as JSON from JS serialisation (Committee tab only)
        $committeeJson = $this->request->post('committee_json', null);
        if ($committeeJson !== null) {
            $this->settingModel->set('committee_text', $committeeJson);
        }

        // Front-matter visibility toggles. Each tab form pairs a hidden "0" with
        // a checkbox "1", so the flag is always present when its form submits.
        // Stored '1' = show in PDF, '0' = hidden (default).
        $flags = ['pdf_show_cover', 'pdf_show_intro', 'pdf_show_committee'];
        foreach ($flags as $flag) {
            $value = $this->request->post($flag, null);
            if ($value !== null) {
                $this->settingModel->set($flag, $value === '1' ? '1' : '0');
            }
        }

        $this->flash('success', 'Settings saved.');
        $this->redirect('/settings');
    }

    /**
     * POST /settings/cover-image — upload cover image
     */
    public function uploadCoverImage(): void
    {
        $this->request->validateCsrf();

        $file = $this->request->file('cover_image');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'No file uploaded or upload error.');
            $this->redirect('/settings');
        }

        // Validate size ≤ 5 MB
        if ($file['size'] > 5 * 1024 * 1024) {
            $this->flash('error', 'Cover image must be ≤ 5 MB.');
            $this->redirect('/settings');
        }

        // Validate MIME type server-side
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mimeType, $allowed)) {
            $this->flash('error', 'Only JPG, PNG, or GIF images are allowed.');
            $this->redirect('/settings');
        }

        $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
        $ext    = $extMap[$mimeType];

        $uploadDir  = __DIR__ . '/../../public/uploads/';
        $destFile   = $uploadDir . 'cover_image.' . $ext;
        $publicPath = 'uploads/cover_image.' . $ext;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $destFile)) {
            $this->flash('error', 'Failed to save the uploaded image.');
            $this->redirect('/settings');
        }

        $this->settingModel->set('cover_image_path', $publicPath);
        $this->flash('success', 'Cover image updated.');
        $this->redirect('/settings');
    }

    /**
     * POST /settings/relationships — add a relationship
     */
    public function storeRelationship(): void
    {
        $this->request->validateCsrf();

        $name = trim($this->request->post('name', ''));
        if ($name === '') {
            $this->flash('error', 'Relationship name is required.');
            $this->redirect('/settings#relationships');
        }

        try {
            $this->relModel->create($name);
            $this->flash('success', "Relationship '{$name}' added.");
        } catch (PDOException $e) {
            // Unique constraint violation
            $this->flash('error', "Relationship '{$name}' already exists.");
        }

        $this->redirect('/settings#relationships');
    }

    /**
     * POST /settings/relationships/{id}/delete
     */
    public function destroyRelationship(int $id): void
    {
        $this->request->validateCsrf();
        $this->relModel->deactivate($id);
        $this->flash('success', 'Relationship deactivated.');
        $this->redirect('/settings#relationships');
    }

    /**
     * POST /settings/genders — add a gender
     */
    public function storeGender(): void
    {
        $this->request->validateCsrf();

        $name = trim($this->request->post('name', ''));
        if ($name === '') {
            $this->flash('error', 'Gender name is required.');
            $this->redirect('/settings#genders');
        }

        try {
            $this->genderModel->create($name);
            $this->flash('success', "Gender '{$name}' added.");
        } catch (PDOException $e) {
            $this->flash('error', "Gender '{$name}' already exists.");
        }

        $this->redirect('/settings#genders');
    }

    /**
     * POST /settings/genders/{id}/delete
     */
    public function destroyGender(int $id): void
    {
        $this->request->validateCsrf();
        $this->genderModel->deactivate($id);
        $this->flash('success', 'Gender deactivated.');
        $this->redirect('/settings#genders');
    }
}
