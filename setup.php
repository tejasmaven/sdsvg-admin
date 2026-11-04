<?php
/**
 * SDSVG Admin Panel — One-Time Setup Script
 *
 * ⚠️  DELETE THIS FILE IMMEDIATELY AFTER FIRST USE  ⚠️
 *
 * Run this once to create the initial superadmin account.
 * Accessible via browser on shared hosting, or via CLI.
 */

define('SETUP_ALLOWED', true); // Remove this line to disable

if (!defined('SETUP_ALLOWED')) {
    http_response_code(403);
    die('Setup is disabled. Delete this file.');
}

require_once __DIR__ . '/config.php';

$message = '';
$error   = '';

if (php_sapi_name() === 'cli') {
    // ── CLI mode ──────────────────────────────────────────────────────────────
    echo "=== SDSVG Admin Setup ===\n";
    echo "Username: ";
    $username = trim(fgets(STDIN));
    echo "Password (will not echo): ";

    // Try to disable echo on Unix-like systems
    if (strncasecmp(PHP_OS, 'WIN', 3) !== 0) {
        system('stty -echo');
        $password = trim(fgets(STDIN));
        system('stty echo');
        echo "\n";
    } else {
        $password = trim(fgets(STDIN));
    }
    echo "Confirm password: ";
    if (strncasecmp(PHP_OS, 'WIN', 3) !== 0) {
        system('stty -echo');
        $confirm = trim(fgets(STDIN));
        system('stty echo');
        echo "\n";
    } else {
        $confirm = trim(fgets(STDIN));
    }

    if ($password !== $confirm) {
        echo "ERROR: Passwords do not match.\n";
        exit(1);
    }
    if (strlen($username) < 3) {
        echo "ERROR: Username must be at least 3 characters.\n";
        exit(1);
    }
    if (strlen($password) < 8) {
        echo "ERROR: Password must be at least 8 characters.\n";
        exit(1);
    }

    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (:u, :h)");
        $stmt->execute([':u' => $username, ':h' => $hash]);
        echo "SUCCESS: Admin account '{$username}' created.\n";
        echo "DELETE setup.php now: rm setup.php\n";
    } catch (PDOException $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        exit(1);
    }
    exit(0);
}

// ── Browser mode ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');

    if (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (:u, :h)");
            $stmt->execute([':u' => $username, ':h' => $hash]);
            $message = "Admin account '{$username}' created successfully!";
        } catch (PDOException $e) {
            $error = 'Database error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SDSVG Setup</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
<div class="card" style="width:380px;box-shadow:0 4px 20px rgba(0,0,0,.15)">
    <div class="card-header bg-danger text-white">
        <strong>⚠️ SDSVG Admin Setup</strong>
    </div>
    <div class="card-body">
        <div class="alert alert-warning mb-3" style="font-size:13px">
            <strong>Security Warning:</strong> Delete this file (<code>setup.php</code>) immediately after creating the admin account.
        </div>

        <?php if ($message): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            <hr>
            <strong>Next steps:</strong>
            <ol class="mb-0 mt-1">
                <li>Delete this file immediately.</li>
                <li><a href="<?= BASE_URL ?>/">Log in to the admin panel</a>.</li>
            </ol>
        </div>
        <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if (!$message): ?>
        <form method="POST" novalidate>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control"
                       value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       required minlength="3">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required minlength="8">
                <div class="form-text">At least 8 characters.</div>
            </div>
            <div class="mb-4">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-danger w-100">Create Admin Account</button>
        </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
