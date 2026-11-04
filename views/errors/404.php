<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>404 — Not Found</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
<div class="text-center">
    <h1 style="font-size:6rem;color:#dee2e6;font-weight:700">404</h1>
    <h4 class="mb-3">Page Not Found</h4>
    <p class="text-muted">The page you're looking for doesn't exist.</p>
    <a href="<?= defined('BASE_URL') ? BASE_URL : '/' ?>/dashboard" class="btn btn-primary">
        Back to Dashboard
    </a>
</div>
</body>
</html>
