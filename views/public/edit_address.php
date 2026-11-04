<?php
/**
 * View: public/edit_address — prefilled address correction form + captcha.
 * Variables: $group, $errors, $old
 */
$ctrl = new BaseController();
function ph(mixed $v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function pe(array $errors, string $k): string {
    return isset($errors[$k])
        ? '<div class="invalid-feedback d-block">' . htmlspecialchars($errors[$k], ENT_QUOTES, 'UTF-8') . '</div>'
        : '';
}
$gid       = (int)($group['group_id'] ?? 0);
$isInSurat = (string)($old['is_in_surat'] ?? '1') === '1';
?>

<div class="pub-card">
    <a href="<?= BASE_URL ?>/family/<?= $gid ?>" class="pub-sub"><i class="bi bi-arrow-left me-1"></i>Back to family</a>
    <h2 style="font-size:22px;font-weight:800" class="mt-2 mb-1">Request an Address Correction</h2>
    <div class="pub-sub mb-3">The committee will review your change before it is saved.</div>

    <form action="<?= BASE_URL ?>/family/<?= $gid ?>/edit-address" method="POST" novalidate autocomplete="off">
        <?= $ctrl->csrfField() ?>

        <!-- Honeypot: must stay empty -->
        <div style="position:absolute;left:-9999px" aria-hidden="true">
            <label>Leave this field empty</label>
            <input type="text" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="3"><?= ph($old['address'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Communication Address</label>
            <textarea name="communication_address" class="form-control" rows="3"><?= ph($old['communication_address'] ?? '') ?></textarea>
            <div class="pub-field-note">Leave blank if same as the address above.</div>
        </div>

        <div class="mb-3">
            <label class="form-label d-block">Do you live in Surat?</label>
            <div class="form-check form-check-inline" style="font-size:19px">
                <input class="form-check-input" type="radio" name="is_in_surat" id="surat-yes" value="1" <?= $isInSurat ? 'checked' : '' ?>>
                <label class="form-check-label" for="surat-yes">Yes</label>
            </div>
            <div class="form-check form-check-inline" style="font-size:19px">
                <input class="form-check-input" type="radio" name="is_in_surat" id="surat-no" value="0" <?= !$isInSurat ? 'checked' : '' ?>>
                <label class="form-check-label" for="surat-no">No</label>
            </div>
        </div>

        <hr class="my-4">

        <button type="submit" class="btn btn-primary btn-lg-block">
            <i class="bi bi-send me-2"></i>Submit for Review
        </button>
    </form>
</div>
