<?php
/**
 * ImportController — bulk CSV import
 */
class ImportController extends BaseController
{
    /**
     * GET /import — upload phase
     */
    public function index(): void
    {
        $preview = $_SESSION['import_preview'] ?? null;
        $this->render('import/index', [
            'preview'   => $preview,
            'pageTitle' => 'Bulk Import',
        ]);
    }

    /**
     * GET /import/clear — discard the current preview and redirect
     * Used by Re-upload and Cancel buttons.
     * Optional query param: ?dest=/dashboard (defaults to /import)
     */
    public function clearPreview(): void
    {
        unset($_SESSION['import_preview']);
        $dest = match($_GET['dest'] ?? '') {
            'dashboard' => '/dashboard',
            default     => '/import',
        };
        $this->redirect($dest);
    }

    /**
     * GET /import/template — stream the CSV template
     */
    public function downloadTemplate(): void
    {
        $filename = 'SDSVG_Import_Template.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // BOM for Excel UTF-8 compatibility
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'Group', 'P/S', 'Title', 'First Name', 'Middle Name', 'Last Name',
            'Gender', 'Relationship', 'DOB', 'Education', 'Mobile', 'Email', 'Address',
        ]);

        // Sample rows
        fputcsv($out, ['1', 'P', 'Shri', 'Ramesh',  '', 'Shah', 'Male', 'Self',   '1960-01-15', 'B.Com', '9876543210', 'ramesh@example.com', '12, Patel Nagar, Surat']);
        fputcsv($out, ['1', 'S', 'Smt',  'Sunita',  '', 'Shah', 'Female', 'Wife', '1963-03-22', 'HSC',  '9876543211', 'sunita@example.com', '']);
        fputcsv($out, ['2', 'P', 'Shri', 'Mahesh',  '', 'Patel', 'Male', 'Self',  '1955-07-04', 'M.A.', '9988776655', '', '45, Gandhi Chowk, Surat']);

        fclose($out);
        exit;
    }

    /**
     * POST /import/preview — parse uploaded CSV and store in session
     */
    public function preview(): void
    {
        $this->request->validateCsrf();

        $file = $this->request->file('csv_file');
        if (!$file) {
            $this->flash('error', 'Please select a CSV file to upload.');
            $this->redirect('/import');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'File upload error (code ' . $file['error'] . ').');
            $this->redirect('/import');
        }

        $maxBytes = UPLOAD_MAX_MB * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            $this->flash('error', 'File exceeds the ' . UPLOAD_MAX_MB . ' MB limit.');
            $this->redirect('/import');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            $this->flash('error', 'Only .csv files are accepted.');
            $this->redirect('/import');
        }

        $result = CsvParser::parse($file['tmp_name']);
        $_SESSION['import_preview'] = $result;

        $this->redirect('/import');
    }

    /**
     * POST /import/execute — commit valid rows from session into the DB
     */
    public function execute(): void
    {
        $this->request->validateCsrf();

        if (empty($_SESSION['import_preview']['valid'])) {
            $this->flash('error', 'No valid rows to import. Please re-upload the file.');
            $this->redirect('/import');
        }

        $rows = $_SESSION['import_preview']['valid'];

        $db          = Database::getInstance();
        $groupModel  = new GroupModel();
        $memberModel = new MemberModel();

        // Map CSV group identifiers to actual DB group_ids
        $groupMap       = [];
        $familiesCreated = 0;
        $membersImported = 0;
        $skipped         = 0;

        $db->beginTransaction();
        try {
            foreach ($rows as $row) {
                $csvGroupId = $row['group_id'];

                // Create group once per CSV Group value, using address from the P row
                if (!isset($groupMap[$csvGroupId])) {
                    $address = ($row['type'] === 'P') ? ($row['address'] ?: null) : null;
                    $dbGroupId = $groupModel->create($address);
                    $groupMap[$csvGroupId] = $dbGroupId;
                    $familiesCreated++;
                } else {
                    // If this is the P row and group already created, update address
                    if ($row['type'] === 'P' && !empty($row['address'])) {
                        $groupModel->update($groupMap[$csvGroupId], $row['address']);
                    }
                }

                $dbGroupId = $groupMap[$csvGroupId];

                try {
                    $memberModel->create($dbGroupId, $row['type'], [
                        'title'           => $row['title'],
                        'first_name'      => $row['first_name'],
                        'middle_name'     => $row['middle_name'],
                        'last_name'       => $row['last_name'],
                        'dob'             => $row['dob'],
                        'gender_id'       => $row['gender_id'],
                        'relationship_id' => $row['relationship_id'],
                        'mobile'          => $row['mobile'] ?: null,
                        'email'           => $row['email']  ?: null,
                        'education'       => $row['education'] ?: null,
                    ]);
                    $membersImported++;
                } catch (Throwable $e) {
                    $skipped++;
                }
            }
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            $this->flash('error', 'Import failed: ' . $e->getMessage());
            $this->redirect('/import');
        }

        unset($_SESSION['import_preview']);

        $this->flash(
            'success',
            "{$familiesCreated} families created, {$membersImported} members imported" .
            ($skipped > 0 ? ", {$skipped} rows skipped." : ".")
        );
        $this->redirect('/dashboard');
    }
}
