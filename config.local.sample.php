<?php
/**
 * Local / server configuration overrides — COPY THIS FILE to `config.local.php`
 * and fill in real values. `config.local.php` is git-ignored, so secrets stay
 * off the repository.
 *
 * Any constant defined here wins over the defaults in config.php (first define()
 * wins). Define only what differs from the defaults for this environment.
 */

// ── Database (required on the server) ──────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

// ── Optional overrides ─────────────────────────────────────────────────────────
// Force the environment (otherwise auto-detected from the host):
// define('APP_ENV', 'production');   // enables secure session cookies

// Pin the base URL if auto-detection is wrong behind a proxy / custom routing:
// define('BASE_URL', 'https://directory.example.org');
