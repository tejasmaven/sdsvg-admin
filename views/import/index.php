<?php
/**
 * View: import/index
 * Variables: $preview (null or array from CsvParser)
 */
$ctrl = new BaseController();
function h(mixed $v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
?>

<?php if (!$preview): ?>
<!-- ══ Phase 1: Upload ══════════════════════════════════════════════════════ -->

<div class="card mb-4" style="max-width:680px">
    <div class="card-header bg-white">
        <strong><i class="bi bi-file-earmark-arrow-up me-2"></i>Bulk Import from CSV</strong>
    </div>
    <div class="card-body">
        <h6 class="mb-3">Instructions</h6>
        <ol class="mb-4" style="font-size:14px">
            <li>Download the CSV template below.</li>
            <li>Fill in your data — one row per person. Use the same Group number for family members.</li>
            <li>The first member with <strong>P</strong> in the P/S column is the Head. All others use <strong>S</strong>.</li>
            <li>DOB format: <code>YYYY-MM-DD</code> or <code>DD/MM/YYYY</code>.</li>
            <li>Gender and Relationship values must match exactly what is configured in Settings.</li>
            <li>Save the file as <strong>UTF-8 CSV</strong> and upload below.</li>
        </ol>

        <div class="mb-4">
            <a href="<?= BASE_URL ?>/import/template" class="btn btn-outline-secondary">
                <i class="bi bi-download me-2"></i>Download CSV Template
            </a>
        </div>

        <form action="<?= BASE_URL ?>/import/preview" method="POST" enctype="multipart/form-data"
              id="import-upload-form">
            <?= $ctrl->csrfField() ?>
            <div class="mb-3">
                <label for="csv_file" class="form-label fw-semibold">Select CSV File</label>
                <input type="file" id="csv_file" name="csv_file"
                       class="form-control" accept=".csv" required
                       data-max-mb="<?= UPLOAD_MAX_MB ?>">
                <div class="form-text">Maximum file size: <?= UPLOAD_MAX_MB ?> MB</div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-eye me-2"></i>Preview Import
            </button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ══ Phase 2: Preview ═════════════════════════════════════════════════════ -->

<?php
$valid    = $preview['valid']    ?? [];
$errors   = $preview['errors']   ?? [];
$warnings = $preview['warnings'] ?? [];
$total    = $preview['total']    ?? 0;
?>

<!-- Summary chips -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <span class="badge bg-secondary fs-6 fw-normal px-3 py-2">
        <i class="bi bi-hash me-1"></i><?= $total ?> total rows
    </span>
    <span class="badge bg-success fs-6 fw-normal px-3 py-2">
        <i class="bi bi-check2-circle me-1"></i><?= count($valid) ?> valid
    </span>
    <?php if (!empty($errors)): ?>
    <span class="badge bg-danger fs-6 fw-normal px-3 py-2">
        <i class="bi bi-x-circle me-1"></i><?= count($errors) ?> errors
    </span>
    <?php endif; ?>
    <?php if (!empty($warnings)): ?>
    <span class="badge bg-warning text-dark fs-6 fw-normal px-3 py-2">
        <i class="bi bi-exclamation-triangle me-1"></i><?= count($warnings) ?> warnings
    </span>
    <?php endif; ?>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
    <strong><?= count($errors) ?> row(s) have errors</strong> and will be skipped during import.
</div>
<?php endif; ?>

<!-- Preview table -->
<div class="card mb-4">
    <div class="card-header bg-white">
        <strong>Preview</strong>
        <small class="text-muted ms-2">Showing all <?= $total ?> rows</small>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0" style="font-size:12px">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Group</th>
                    <th>P/S</th>
                    <th>Title</th>
                    <th>First</th>
                    <th>Last</th>
                    <th>Gender</th>
                    <th>Relationship</th>
                    <th>DOB</th>
                    <th>Mobile</th>
                    <th>Email</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php
            // Merge valid + errors for display, keyed by row_number
            $allRows = [];
            foreach ($valid as $r)  $allRows[$r['row_number']] = ['data' => $r, 'status' => 'valid'];
            foreach ($errors as $r) $allRows[$r['row_number']] = ['data' => $r, 'status' => 'error'];
            ksort($allRows);
            ?>
            <?php foreach ($allRows as $rowNum => $entry): ?>
            <?php
            $r      = $entry['data'];
            $status = $entry['status'];
            $hasWarn = !empty($r['_warnings']);
            if ($status === 'valid' && $hasWarn) $status = 'warning';
            $rowClass = match($status) {
                'valid'   => 'sdsvg-import-valid',
                'error'   => 'sdsvg-import-error',
                'warning' => 'sdsvg-import-warn',
                default   => '',
            };
            ?>
            <tr class="<?= $rowClass ?>">
                <td><?= $rowNum ?></td>
                <td><?= h($r['group_id']) ?></td>
                <td><?= h($r['type'] ?? '') ?></td>
                <td><?= h($r['title'] ?? '') ?></td>
                <td><?= h($r['first_name'] ?? '') ?></td>
                <td><?= h($r['last_name'] ?? '') ?></td>
                <td><?= h($r['gender_id'] ?? $r['gender_name'] ?? '') ?></td>
                <td><?= h($r['relationship_id'] ?? $r['relationship_name'] ?? '') ?></td>
                <td><?= h($r['dob'] ?? '') ?></td>
                <td><?= h($r['mobile'] ?? '') ?></td>
                <td><?= h($r['email'] ?? '') ?></td>
                <td>
                    <?php if ($status === 'error'): ?>
                        <span class="text-danger fw-semibold">✗</span>
                        <ul class="mb-0 ps-3" style="font-size:11px">
                        <?php foreach ($r['errors'] as $err): ?>
                            <li><?= h($err) ?></li>
                        <?php endforeach; ?>
                        </ul>
                    <?php elseif ($status === 'warning'): ?>
                        <span class="text-warning fw-semibold">⚠</span>
                        <ul class="mb-0 ps-3" style="font-size:11px">
                        <?php foreach ($r['_warnings'] as $w): ?>
                            <li><?= h($w) ?></li>
                        <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <span class="text-success fw-semibold">✓</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Action buttons -->
<div class="d-flex gap-3 flex-wrap">
    <?php if (!empty($valid)): ?>
    <form action="<?= BASE_URL ?>/import/execute" method="POST">
        <?= $ctrl->csrfField() ?>
        <button type="submit" class="btn btn-success">
            <i class="bi bi-cloud-arrow-up me-2"></i>
            Confirm Import (<?= count($valid) ?> rows)
        </button>
    </form>
    <?php endif; ?>

    <!-- Re-upload: clears session preview and returns to upload form -->
    <a href="<?= BASE_URL ?>/import/clear" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-repeat me-1"></i>Re-upload
    </a>

    <!-- Cancel: clears session preview and goes to dashboard -->
    <a href="<?= BASE_URL ?>/import/clear?dest=dashboard" class="btn btn-outline-secondary">
        <i class="bi bi-x-lg me-1"></i>Cancel
    </a>
</div>

<?php endif; ?>
