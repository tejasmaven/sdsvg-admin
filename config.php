<?php
/**
 * SDSVG Admin Panel — Application Configuration
 *
 * This file is environment-aware and safe to commit: it contains only local
 * development defaults. Real server credentials and any per-deployment overrides
 * live in `config.local.php`, which is NOT committed to git (see .gitignore).
 *
 *  - On local/dev: works as-is with the WAMP defaults below.
 *  - On the server: create `config.local.php` (copy from `config.local.sample.php`)
 *    and define DB_* there. Those definitions win because they load first.
 *
 * BASE_URL is derived automatically from the incoming request, so the app works
 * under any host or sub-path without editing — just keep `.htaccess` RewriteBase
 * matching the folder it is deployed in.
 */

// 1. Load untracked local/server overrides first (first define() wins).
$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
    require $localConfig;
}

// 2. Detect environment from the host (overridable in config.local.php).
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocalHost = (bool) preg_match('#^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$#i', $host);

defined('APP_ENV') || define('APP_ENV', $isLocalHost ? 'development' : 'production');

// 3. Database — override these in config.local.php on the server.
defined('DB_HOST') || define('DB_HOST', 'localhost');
defined('DB_NAME') || define('DB_NAME', '');
defined('DB_USER') || define('DB_USER', '');
defined('DB_PASS') || define('DB_PASS', '');

// 4. Base URL — auto-derived from the request (override in config.local.php if needed).
if (!defined('BASE_URL')) {
    $https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
          || (($_SERVER['SERVER_PORT'] ?? null) == 443)
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme  = $https ? 'https' : 'http';
    $baseDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // Normalise "/", ".", "" (e.g. app at domain root, or CLI) to an empty prefix.
    $baseDir = in_array($baseDir, ['/', '.', '', '\\'], true) ? '' : rtrim($baseDir, '/');
    define('BASE_URL', $scheme . '://' . $host . $baseDir);
}

// 5. Application settings (sane defaults; override in config.local.php if desired).
defined('SESSION_NAME')        || define('SESSION_NAME',        'SDSVG_SESS');
defined('SESSION_LIFETIME')    || define('SESSION_LIFETIME',    3600);   // 60 minutes
defined('MAX_LOGIN_ATTEMPTS')  || define('MAX_LOGIN_ATTEMPTS',  5);
defined('LOGIN_BLOCK_MINUTES') || define('LOGIN_BLOCK_MINUTES', 15);
defined('UPLOAD_MAX_MB')       || define('UPLOAD_MAX_MB',       10);
