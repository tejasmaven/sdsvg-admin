<?php
/**
 * View: public/edit_member — prefilled correction form + captcha.
 * Variables: $member, $genders, $relationships, $errors, $old
 */
$ctrl = new BaseController();
function ph(mixed $v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function pe(array $errors, string $k): string {
    return isset($errors[$k])
        ? '<div class="invalid-feedback d-block">' . htmlspecialchars($errors[$k], ENT_QUOTES, 'UTF-8') . '</div>'
        : '';
}
$gid = (int)($member['group_id'] ?? 0);
?>

<div class="pub-card">
    <a href="<?= BASE_URL ?>/family/<?= $gid ?>" class="pub-sub"><i class="bi bi-arrow-left me-1"></i>Back to family</a>
    <h2 style="font-size:22px;font-weight:800" class="mt-2 mb-1">Request a Correction</h2>
    <div class="pub-sub mb-3">Edit anything that is wrong and submit. The committee will review it before it is saved.</div>

    <form action="<?= BASE_URL ?>/member/<?= (int)$member['member_id'] ?>/edit" method="POST" novalidate autocomplete="off">
        <?= $ctrl->csrfField() ?>

        <!-- Honeypot: must stay empty -->
        <div style="position:absolute;left:-9999px" aria-hidden="true">
            <label>Leave this field empty</label>
            <input type="text" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div class="row g-3">
            <div class="col-4">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" value="<?= ph($old['title'] ?? '') ?>" placeholder="Shri / Smt">
            </div>
            <div class="col-8">
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" class="form-control" value="<?= ph($old['first_name'] ?? '') ?>" required>
                <?= pe($errors,'first_name') ?>
            </div>
            <div class="col-6">
                <label class="form-label">Middle Name</label>
                <input type="text" name="middle_name" class="form-control" value="<?= ph($old['middle_name'] ?? '') ?>">
            </div>
            <div class="col-6">
                <label class="form-label">Last Name</label>
                <input type="text" name="last_name" class="form-control" value="<?= ph($old['last_name'] ?? '') ?>" required>
                <?= pe($errors,'last_name') ?>
            </div>

            <div class="col-12">
                <label class="form-label">Date of Birth</label>
                <input type="date" name="dob" class="form-control" value="<?= ph($old['dob'] ?? '') ?>">
                <div class="pub-field-note">Leave blank if you are not sure.</div>
                <?= pe($errors,'dob') ?>
            </div>

            <div class="col-6">
                <label class="form-label">Gender</label>
                <select name="gender_id" class="form-select">
                    <option value="">— Select —</option>
                    <?php foreach ($genders as $g): ?>
                    <option value="<?= (int)$g['gender_id'] ?>" <?= ((string)($old['gender_id'] ?? '') === (string)$g['gender_id']) ? 'selected' : '' ?>>
                        <?= ph($g['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?= pe($errors,'gender_id') ?>
            </div>
            <div class="col-6">
                <label class="form-label">Relationship</label>
                <select name="relationship_id" class="form-select">
                    <option value="">— Select —</option>
                    <?php foreach ($relationships as $r): ?>
                    <option value="<?= (int)$r['relationship_id'] ?>" <?= ((string)($old['relationship_id'] ?? '') === (string)$r['relationship_id']) ? 'selected' : '' ?>>
                        <?= ph($r['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?= pe($errors,'relationship_id') ?>
            </div>

            <div class="col-12">
                <label class="form-label">Mobile</label>
                <input type="text" name="mobile" class="form-control" value="<?= ph($old['mobile'] ?? '') ?>" placeholder="10-digit number">
                <?= pe($errors,'mobile') ?>
            </div>
            <div class="col-12">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= ph($old['email'] ?? '') ?>">
                <?= pe($errors,'email') ?>
            </div>
            <div class="col-12">
                <label class="form-label">Education</label>
                <input type="text" name="education" class="form-control" value="<?= ph($old['education'] ?? '') ?>">
            </div>
        </div>

        <hr class="my-4">

        <button type="submit" class="btn btn-primary btn-lg-block">
            <i class="bi bi-send me-2"></i>Submit for Review
        </button>
    </form>
</div>
