<?php
/**
 * View: families/create
 * Variables: $genders, $relationships, $selfId, $old, $errors
 */
$ctrl = new BaseController();
function h(mixed $v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function err(array $errors, string $field): string {
    return isset($errors[$field]) ? '<div class="invalid-feedback d-block">' . h($errors[$field]) . '</div>' : '';
}
function cls(array $errors, string $field): string {
    return isset($errors[$field]) ? 'is-invalid' : '';
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/families">Families</a></li>
        <li class="breadcrumb-item active">Add Family</li>
    </ol>
</nav>

<form action="<?= BASE_URL ?>/families" method="POST" novalidate>
    <?= $ctrl->csrfField() ?>

    <!-- ── Section 1: Address ───────────────────────────────────────────────── -->
    <div class="card mb-4">
        <div class="card-body">
            <p class="sdsvg-section-title">Family Address</p>
            <div class="mb-3">
                <label for="address" class="form-label">Home Address <span class="text-muted fw-normal">(optional)</span></label>
                <textarea id="address" name="address" class="form-control" rows="3"
                          placeholder="House/flat, street, city, pin"><?= h($old['address'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label for="communication_address" class="form-label">Communication Address <span class="text-muted fw-normal">(optional)</span></label>
                <textarea id="communication_address" name="communication_address" class="form-control" rows="3"
                          placeholder="Mailing / contact address if different from home address"><?= h($old['communication_address'] ?? '') ?></textarea>
                <div class="form-text">Used for correspondence. Leave blank if same as home address.</div>
            </div>
        </div>
    </div>

    <!-- ── Section 2: Primary Member ───────────────────────────────────────── -->
    <div class="card mb-4">
        <div class="card-body">
            <p class="sdsvg-section-title">Primary Member (Head of Family)</p>

            <div class="row g-3">
                <!-- Title -->
                <div class="col-md-2">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" id="title" name="title" class="form-control"
                           value="<?= h($old['title'] ?? '') ?>" placeholder="Shri, Dr…" maxlength="20">
                </div>
                <!-- First Name -->
                <div class="col-md-3">
                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" id="first_name" name="first_name"
                           class="form-control <?= cls($errors, 'first_name') ?>"
                           value="<?= h($old['first_name'] ?? '') ?>" required maxlength="100">
                    <?= err($errors, 'first_name') ?>
                </div>
                <!-- Middle Name -->
                <div class="col-md-3">
                    <label for="middle_name" class="form-label">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name" class="form-control"
                           value="<?= h($old['middle_name'] ?? '') ?>" maxlength="100">
                </div>
                <!-- Last Name -->
                <div class="col-md-4">
                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" id="last_name" name="last_name"
                           class="form-control <?= cls($errors, 'last_name') ?>"
                           value="<?= h($old['last_name'] ?? '') ?>" required maxlength="100">
                    <?= err($errors, 'last_name') ?>
                </div>

                <!-- DOB -->
                <div class="col-md-3">
                    <label for="dob" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" id="dob" name="dob"
                           class="form-control <?= cls($errors, 'dob') ?>"
                           value="<?= h($old['dob'] ?? '') ?>" required max="<?= date('Y-m-d') ?>">
                    <?= err($errors, 'dob') ?>
                </div>
                <!-- Gender -->
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
                <!-- Relationship — locked to Self -->
                <div class="col-md-3">
                    <label for="relationship_id" class="form-label">Relationship</label>
                    <select id="relationship_id" name="relationship_id" class="form-select" disabled>
                        <option value="<?= (int)$selfId ?>">Self</option>
                    </select>
                    <!-- Hidden input to submit the value since select is disabled -->
                    <input type="hidden" name="relationship_id" value="<?= (int)$selfId ?>">
                    <div class="form-text">Head of family is always "Self"</div>
                </div>

                <!-- Mobile -->
                <div class="col-md-3">
                    <label for="mobile" class="form-label">Mobile</label>
                    <input type="tel" id="mobile" name="mobile"
                           class="form-control <?= cls($errors, 'mobile') ?>"
                           value="<?= h($old['mobile'] ?? '') ?>"
                           placeholder="e.g. 9876543210 or 9876543210, 9876543211" maxlength="100">
                    <?= err($errors, 'mobile') ?>
                </div>

                <!-- Email -->
                <div class="col-md-4">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email"
                           class="form-control <?= cls($errors, 'email') ?>"
                           value="<?= h($old['email'] ?? '') ?>" maxlength="150">
                    <?= err($errors, 'email') ?>
                </div>

                <!-- Education -->
                <div class="col-md-8">
                    <label for="education" class="form-label">Education</label>
                    <input type="text" id="education" name="education" class="form-control"
                           value="<?= h($old['education'] ?? '') ?>" maxlength="200"
                           placeholder="e.g. B.Com, M.A.">
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-3">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2 me-1"></i>Create Family
        </button>
        <a href="<?= BASE_URL ?>/families" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
