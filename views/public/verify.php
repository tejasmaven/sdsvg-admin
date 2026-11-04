<?php
/**
 * View: public/verify — knowledge gate before a family's details are shown.
 * Variables: $group, $challenge (['type'=>'mobile'|'name']), $error
 */
$ctrl = new BaseController();
function ph(mixed $v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$gid      = (int)($group['group_id'] ?? 0);
$isMobile = ($challenge['type'] ?? 'mobile') === 'mobile';
$headName = trim(($group['head_last_name'] ?? '') . ' family');
?>

<div class="pub-card">
    <a href="<?= BASE_URL ?>/" class="pub-sub"><i class="bi bi-arrow-left me-1"></i>Back to search</a>

    <h2 style="font-size:22px;font-weight:800" class="mt-2 mb-1">
        <i class="bi bi-shield-lock me-1"></i>Verify it's your family
    </h2>
    <div class="pub-sub mb-3">
        To protect privacy, please confirm one detail about the
        <strong><?= ph($group['head_last_name'] ?? '') ?></strong> family before viewing
        or correcting their details.
    </div>

    <?php if (!empty($error)): ?>
    <div class="alert alert-danger" style="font-size:17px" role="alert"><?= ph($error) ?></div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/family/<?= $gid ?>/verify" method="POST" novalidate autocomplete="off">
        <?= $ctrl->csrfField() ?>

        <!-- Honeypot: must stay empty -->
        <div style="position:absolute;left:-9999px" aria-hidden="true">
            <label>Leave this field empty</label>
            <input type="text" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div class="mb-3">
            <?php if ($isMobile): ?>
                <label for="answer" class="form-label">
                    Enter the <strong>last 4 digits</strong> of any mobile number registered for this family
                </label>
                <input type="text" id="answer" name="answer" class="form-control"
                       inputmode="numeric" maxlength="12" placeholder="e.g. 6303" required>
                <div class="pub-field-note">Example: if the number is 98251&nbsp;36303, enter 6303.</div>
            <?php else: ?>
                <label for="answer" class="form-label">
                    Enter the <strong>first name</strong> of the head of this family
                </label>
                <input type="text" id="answer" name="answer" class="form-control"
                       maxlength="100" placeholder="First name" required>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary btn-lg-block">
            <i class="bi bi-unlock me-2"></i>Continue
        </button>
    </form>
</div>
