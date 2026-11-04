<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SDSVG Admin — Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">
    <style>
        body {
            background: #222d32;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .sdsvg-auth-card {
            width: 370px;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,.4);
        }
        .sdsvg-auth-header {
            background: #3c8dbc;
            padding: 24px 20px 20px;
            text-align: center;
            color: #fff;
        }
        .sdsvg-auth-logo {
            width: 60px; height: 60px;
            background: rgba(255,255,255,.2);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .sdsvg-auth-card .card-body { background: #fff; padding: 28px 24px; }
        .sdsvg-auth-footer {
            background: #f4f4f4;
            border-top: 1px solid #ddd;
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
<div class="sdsvg-auth-card">
    <div class="sdsvg-auth-header">
        <div class="sdsvg-auth-logo">S</div>
        <h4 class="mb-1 fw-bold">SDSVG Admin</h4>
        <p class="mb-0 opacity-75" style="font-size:13px">Community Directory Management</p>
    </div>
    <div class="card-body">
        <?= $content ?>
    </div>
    <div class="sdsvg-auth-footer">SDSVG Admin Panel &copy; <?= date('Y') ?></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
