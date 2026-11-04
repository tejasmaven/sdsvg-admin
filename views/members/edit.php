<?php
/**
 * View: members/edit
 * Variables: $member, $group, $genders, $relationships, $old, $errors
 */
$ctrl = new BaseController();
function h(mixed $v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function err(array $errors, string $field): string {
    return isset($errors[$field]) ? '<div class="invalid-feedback d-block">' . h($errors[$field]) . '</div>' : '';
}
function cls(array $errors, string $field): string {
    return isset($errors[$field]) ? 'is-invalid' : '';
}
$isPrimary = ($member['type'] === 'P');
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/families">Families</a></li>
        <li class="breadcrumb-item">
            <a href="<?= BASE_URL ?>/families/<?= (int)$member['group_id'] ?>">
                Family #<?= (int)$member['group_id'] ?>
            </a>
        </li>
        <li class="breadcrumb-item active">Edit Member</li>
    </ol>
</nav>

<?php if ($isPrimary): ?>
<div class="alert alert-info d-flex gap-2 align-items-start mb-3">
    <i class="bi bi-info-circle-fill mt-1"></i>
    <div>
        <strong>Primary Member (Head of Family)</strong><br>
        <small>You are editing the head member. The family address is managed separately via
        <a href="<?= BASE_URL ?>/families/<?= (int)$member['group_id'] ?>/edit">Edit Address</a>.</small>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form action="<?= BASE_URL ?>/members/<?= (int)$member['member_id'] ?>/edit"
              method="POST" novalidate>
            <?= $ctrl->csrfField() ?>

            <div class="row g-3">
                <div class="col-md-2">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" id="title" name="title" class="form-control"
                           value="<?= h($old['title'] ?? '') ?>" maxlength="20">
                </div>
                <div class="col-md-3">
                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" id="first_name" name="first_name"
                           class="form-control <?= cls($errors, 'first_name') ?>"
                           value="<?= h($old['first_name'] ?? '') ?>" required maxlength="100">
                    <?= err($errors, 'first_name') ?>
                </div>
                <div class="col-md-3">
                    <label for="middle_name" class="form-label">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name" class="form-control"
                           value="<?= h($old['middle_name'] ?? '') ?>" maxlength="100">
                </div>
                <div class="col-md-4">
                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" id="last_name" name="last_name"
                           class="form-control <?= cls($errors, 'last_name') ?>"
                           value="<?= h($old['last_name'] ?? '') ?>" required maxlength="100">
                    <?= err($errors, 'last_name') ?>
                </div>

                <div class="col-md-3">
                    <label for="dob" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" id="dob" name="dob"
                           class="form-control <?= cls($errors, 'dob') ?>"
                           value="<?= h($old['dob'] ?? '') ?>" required max="<?= date('Y-m-d') ?>">
                    <?= err($errors, 'dob') ?>
                </div>
                <div class="col-md-3">
                    <label for="gender_id" class="form-label">Gender <span class="text-danger">*</span></label>
                    <select id="gender_id" name="gender_id"
                            class="form-select <?= cls($errors, 'gender_id') ?>" required>
                        <option value="">— Select —</option>
                        <?php foreach ($genders as $g): ?>
                        <option value="<?= (int)$g['gender_id'] ?>"
                            <?= ((int)($old['gender_id'] ?? 0) === (int)$g['gender_id']) ? 'selected' : '' ?>>
                            <?= h($g['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?= err($errors, 'gender_id') ?>
                </div>
                <div class="col-md-3">
                    <label for="relationship_id" class="form-label">Relationship <span class="text-danger">*</span></label>
                    <select id="relationship_id" name="relationship_id"
                            class="form-select <?= cls($errors, 'relationship_id') ?>" required>
                        <option value="">— Select —</option>
                        <?php foreach ($relationships as $r): ?>
                        <option value="<?= (int)$r['relationship_id'] ?>"
                            <?= ((int)($old['relationship_id'] ?? 0) === (int)$r['relationship_id']) ? 'selected' : '' ?>>
                            <?= h($r['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?= err($errors, 'relationship_id') ?>
                </div>

                <div class="col-md-3">
                    <label for="mobile" class="form-label">Mobile</label>
                    <input type="tel" id="mobile" name="mobile"
                           class="form-control <?= cls($errors, 'mobile') ?>"
                           value="<?= h($old['mobile'] ?? '') ?>" placeholder="e.g. 9876543210 or 9876543210, 9876543211" maxlength="100">
                    <?= err($errors, 'mobile') ?>
                </div>
                <div class="col-md-4">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email"
                           class="form-control <?= cls($errors, 'email') ?>"
                           value="<?= h($old['email'] ?? '') ?>" maxlength="150">
                    <?= err($errors, 'email') ?>
                </div>
                <div class="col-md-8">
                    <label for="education" class="form-label">Education</label>
                    <input type="text" id="education" name="education" class="form-control"
                           value="<?= h($old['education'] ?? '') ?>" maxlength="200">
                </div>
            </div>

            <div class="d-flex gap-3 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2 me-1"></i>Save Changes
                </button>
                <a href="<?= BASE_URL ?>/families/<?= (int)$member['group_id'] ?>"
                   class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
