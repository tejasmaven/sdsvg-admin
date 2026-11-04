# SDSVG Community Directory — Admin Panel

A single-admin, server-rendered PHP/MySQL back office for maintaining a community
family directory and generating a print-ready PDF address book.

> **Note:** Detailed internal documentation (architecture, database schema, routes,
> security design, setup specifics) is kept in a private file that is **not** committed
> to this repository. Maintainers: see `PROJECT_UNDERSTANDING.md` locally.

## Tech stack

- **PHP 8.1+** (no framework), **MySQL 8**, PDO prepared statements
- **Bootstrap 5.3** (CDN), vanilla JS — no build step
- **TCPDF** for PDF generation (installed manually under `lib/`, not vendored here)

## Requirements

- PHP 8.1 or newer with PDO MySQL, GD, and cURL extensions enabled
- MySQL 8.0 or newer
- Apache with `mod_rewrite` (e.g. WAMP/XAMPP locally, or standard shared hosting)

## Quick start

1. Clone the repository into your web root.
2. Create a MySQL database and import the schema and seed data from `database/`.
3. Copy `config.local.sample.php` to `config.local.php` and fill in your database
   credentials. `config.php` auto-detects local vs. server and derives the base URL
   from the request, so no other edits are normally needed.
4. Install TCPDF into `lib/tcpdf/` (see the private setup notes).
5. Create the admin account, then open the site in your browser.

## License

Private project — not for public distribution.
