<?php
/**
 * View: auth/login
 * Variables: $notice, $error, $attempts, $username
 */
$ctrl = new BaseController();
?>

<?php if ($notice): ?>
<div class="alert alert-info py-2 mb-3" style="font-size:13px">
    <?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger py-2 mb-3" style="font-size:13px">
    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<?php if (isset($attempts) && $attempts >= 3 && !$error): ?>
<div class="alert alert-warning py-2 mb-3" style="font-size:13px">
    <?= $attempts ?> of <?= MAX_LOGIN_ATTEMPTS ?> attempts used.
</div>
<?php endif; ?>

<form action="<?= BASE_URL ?>/admin" method="POST" novalidate>
    <?= $ctrl->csrfField() ?>

    <div class="mb-3">
        <label for="username" class="form-label fw-semibold" style="font-size:13px">Username</label>
        <div class="input-group">
            <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
            <input type="text" id="username" name="username"
                   class="form-control"
                   value="<?= htmlspecialchars($username ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   autocomplete="username"
                   required autofocus>
        </div>
    </div>

    <div class="mb-4">
        <label for="password" class="form-label fw-semibold" style="font-size:13px">Password</label>
        <div class="input-group">
            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
            <input type="password" id="password" name="password"
                   class="form-control"
                   autocomplete="current-password"
                   required>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 fw-semibold">
        <i class="bi bi-box-arrow-in-right me-2"></i>Log In
    </button>
</form>
