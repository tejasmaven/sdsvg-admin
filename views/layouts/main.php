<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'SDSVG Admin', ENT_QUOTES, 'UTF-8') ?> — SDSVG</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?php $cssFile = __DIR__ . '/../../public/css/style.css'; $cssVer = @filemtime($cssFile) ?: time(); ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=<?= $cssVer ?>">
</head>
<body>
<?php
    // Flash message (retrieved once here for all views)
    $flash = (new BaseController())->getFlash();
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $basePath = parse_url(BASE_URL, PHP_URL_PATH) ?? '';
    $relPath  = str_starts_with($currentPath, $basePath)
                ? substr($currentPath, strlen($basePath))
                : $currentPath;
    function isActive(string $prefix, string $current): string {
        return str_starts_with($current, $prefix) ? 'sdsvg-sidebar-active' : '';
    }
    // Pending edit-request count for the sidebar badge
    $pendingReviews = 0;
    try { $pendingReviews = (new EditRequestModel())->countPending(); } catch (Throwable $e) { $pendingReviews = 0; }
?>

<div class="d-flex" style="min-height:100vh">
    <!-- ══ Sidebar ════════════════════════════════════════════════════════════ -->
    <nav class="sdsvg-sidebar d-flex flex-column" style="width:240px;min-width:240px">
        <!-- Brand -->
        <div class="p-3 border-bottom border-secondary">
            <div class="d-flex align-items-center gap-2">
                <div style="width:38px;height:38px;background:#3c8dbc;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;flex-shrink:0">S</div>
                <div>
                    <div style="color:#fff;font-weight:700;font-size:15px;line-height:1.2">SDSVG</div>
                    <div style="color:#aaa;font-size:11px;line-height:1.2">Community Directory</div>
                </div>
            </div>
        </div>

        <!-- Nav -->
        <ul class="nav flex-column flex-grow-1 pt-2">
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/dashboard"
                   class="nav-link sdsvg-nav-link <?= isActive('/dashboard', $relPath) ?>">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/families"
                   class="nav-link sdsvg-nav-link <?= isActive('/families', $relPath) ?>">
                    <i class="bi bi-people me-2"></i> Families
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/import"
                   class="nav-link sdsvg-nav-link <?= isActive('/import', $relPath) ?>">
                    <i class="bi bi-upload me-2"></i> Bulk Import
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/reviews"
                   class="nav-link sdsvg-nav-link d-flex align-items-center <?= isActive('/reviews', $relPath) ?>">
                    <i class="bi bi-inbox me-2"></i> Edit Requests
                    <?php if ($pendingReviews > 0): ?>
                    <span class="badge bg-danger ms-auto"><?= (int)$pendingReviews ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/settings"
                   class="nav-link sdsvg-nav-link <?= isActive('/settings', $relPath) ?>">
                    <i class="bi bi-gear me-2"></i> Settings
                </a>
            </li>

            <!-- Generate PDF -->
            <li class="nav-item mt-2">
                <form action="<?= BASE_URL ?>/pdf/generate" method="POST">
                    <?= (new BaseController())->csrfField() ?>
                    <button type="submit" class="nav-link sdsvg-nav-link w-100 text-start border-0 bg-transparent">
                        <i class="bi bi-file-earmark-pdf me-2"></i> Generate PDF
                    </button>
                </form>
            </li>

            <!-- PDF 2 preview page -->
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/pdf/preview2"
                   class="nav-link sdsvg-nav-link <?= isActive('/pdf/preview2', $relPath) ?>">
                    <i class="bi bi-file-earmark-richtext me-2"></i> Generate PDF 2
                </a>
            </li>
        </ul>

        <!-- Logout -->
        <div class="p-3 border-top border-secondary">
            <a href="<?= BASE_URL ?>/logout" class="nav-link sdsvg-nav-link text-danger">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </div>
    </nav>

    <!-- ══ Main area ══════════════════════════════════════════════════════════ -->
    <div class="flex-grow-1 d-flex flex-column" style="min-width:0">

        <!-- Top header bar -->
        <header class="bg-white border-bottom d-flex align-items-center px-4" style="height:56px;flex-shrink:0">
            <div class="flex-grow-1">
                <h6 class="mb-0 fw-semibold"><?= htmlspecialchars($pageTitle ?? '', ENT_QUOTES, 'UTF-8') ?></h6>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted" style="font-size:13px">
                    <i class="bi bi-person-circle me-1"></i>
                    Admin: <?= htmlspecialchars(Auth::username(), ENT_QUOTES, 'UTF-8') ?>
                </span>
                <a href="<?= BASE_URL ?>/logout" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </a>
            </div>
        </header>

        <!-- Flash message -->
        <?php if ($flash): ?>
        <?php
            $alertClass = match($flash['type']) {
                'success' => 'alert-success',
                'error'   => 'alert-danger',
                'warning' => 'alert-warning',
                default   => 'alert-info',
            };
        ?>
        <div class="alert <?= $alertClass ?> alert-dismissible rounded-0 mb-0 border-0 border-bottom" role="alert" style="flex-shrink:0">
            <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Page content -->
        <div class="content p-4 flex-grow-1">
            <?= $content ?>
        </div>

    </div><!-- /main area -->
</div><!-- /d-flex -->

<!-- Session expiry banner (injected by app.js) -->
<div id="sdsvg-session-warning" class="sdsvg-session-warning d-none">
    <i class="bi bi-clock-history me-2"></i>
    Your session will expire in 5 minutes due to inactivity.
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.SDSVG_CONFIG = {
        sessionLifetime: <?= SESSION_LIFETIME ?>,
        uploadMaxMb:     <?= UPLOAD_MAX_MB ?>
    };
</script>
<?php $jsFile = __DIR__ . '/../../public/js/app.js'; $jsVer = @filemtime($jsFile) ?: time(); ?>
<script src="<?= BASE_URL ?>/public/js/app.js?v=<?= $jsVer ?>"></script>
</body>
</html>
