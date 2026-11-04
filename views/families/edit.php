<?php
/**
 * View: families/edit
 * Variables: $group, $errors
 */
$ctrl = new BaseController();
function h(mixed $v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/families">Families</a></li>
        <li class="breadcrumb-item">
            <a href="<?= BASE_URL ?>/families/<?= (int)$group['group_id'] ?>">
                Family #<?= (int)$group['group_id'] ?>
            </a>
        </li>
        <li class="breadcrumb-item active">Edit Address</li>
    </ol>
</nav>

<div class="card" style="max-width:600px">
    <div class="card-body">
        <form action="<?= BASE_URL ?>/families/<?= (int)$group['group_id'] ?>/edit" method="POST" novalidate>
            <?= $ctrl->csrfField() ?>

            <div class="mb-3">
                <label for="address" class="form-label fw-semibold">Home Address</label>
                <textarea id="address" name="address" class="form-control" rows="3"
                          placeholder="House/flat, street, city, pin"><?= h($group['address']) ?></textarea>
                <div class="form-text">Shared across all family members.</div>
            </div>

            <div class="mb-4">
                <label for="communication_address" class="form-label fw-semibold">
                    Communication Address <span class="text-muted fw-normal">(optional)</span>
                </label>
                <textarea id="communication_address" name="communication_address" class="form-control" rows="3"
                          placeholder="Mailing / contact address if different from home address"><?= h($group['communication_address'] ?? '') ?></textarea>
                <div class="form-text">Used for correspondence. Leave blank if same as home address.</div>
            </div>

            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2 me-1"></i>Save Addresses
                </button>
                <a href="<?= BASE_URL ?>/families/<?= (int)$group['group_id'] ?>" class="btn btn-outline-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
