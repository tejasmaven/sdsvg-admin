<?php
/**
 * SDSVG Admin Panel — Front Controller
 */

require_once __DIR__ . '/config.php';

// Session configuration (must be before session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure',   (APP_ENV === 'production') ? 1 : 0);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
session_name(SESSION_NAME);
session_start();

// Autoloader — maps class names to file paths
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/app/core/'        . $class . '.php',
        __DIR__ . '/app/controllers/' . $class . '.php',
        __DIR__ . '/app/models/'      . $class . '.php',
        __DIR__ . '/app/helpers/'     . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Bootstrap and dispatch
$router = new Router();

// ── Public portal (no auth middleware) ────────────────────────────────────────
$router->addRoute('GET',  '/',                          'PublicController', 'landing',        false);
$router->addRoute('POST', '/search',                    'PublicController', 'search',         false);
$router->addRoute('GET',  '/family/{id}',               'PublicController', 'viewFamily',     false);
$router->addRoute('POST', '/family/{id}/verify',        'PublicController', 'verifyFamily',   false);
$router->addRoute('GET',  '/family/{id}/edit-address',  'PublicController', 'editAddressForm',false);
$router->addRoute('POST', '/family/{id}/edit-address',  'PublicController', 'submitAddress',  false);
$router->addRoute('GET',  '/member/{id}/edit',          'PublicController', 'editMemberForm', false);
$router->addRoute('POST', '/member/{id}/edit',          'PublicController', 'submitMember',   false);
$router->addRoute('GET',  '/captcha',                   'PublicController', 'captcha',        false);

// ── Auth routes (no auth middleware) ──────────────────────────────────────────
$router->addRoute('GET',  '/admin',  'AuthController', 'showLogin',     false);
$router->addRoute('POST', '/admin',  'AuthController', 'processLogin',  false);
$router->addRoute('GET',  '/logout', 'AuthController', 'logout',        true);

// ── Dashboard ─────────────────────────────────────────────────────────────────
$router->addRoute('GET', '/dashboard', 'DashboardController', 'index');

// ── Families ──────────────────────────────────────────────────────────────────
$router->addRoute('GET',  '/families',              'FamilyController', 'index');
$router->addRoute('GET',  '/families/create',       'FamilyController', 'create');
$router->addRoute('POST', '/families',              'FamilyController', 'store');
$router->addRoute('GET',  '/families/{id}',         'FamilyController', 'show');
$router->addRoute('GET',  '/families/{id}/edit',    'FamilyController', 'edit');
$router->addRoute('POST', '/families/{id}/edit',    'FamilyController', 'update');
$router->addRoute('POST', '/families/{id}/delete',      'FamilyController', 'destroy');
$router->addRoute('POST', '/families/{id}/toggle-city', 'FamilyController', 'toggleCity');

// ── Members ───────────────────────────────────────────────────────────────────
$router->addRoute('GET',  '/families/{id}/members/add', 'MemberController', 'create');
$router->addRoute('POST', '/families/{id}/members/add', 'MemberController', 'store');
$router->addRoute('GET',  '/members/{id}/edit',         'MemberController', 'edit');
$router->addRoute('POST', '/members/{id}/edit',         'MemberController', 'update');
$router->addRoute('POST', '/members/{id}/delete',       'MemberController', 'destroy');
$router->addRoute('POST', '/members/{id}/move-up',      'MemberController', 'moveUp');
$router->addRoute('POST', '/members/{id}/move-down',    'MemberController', 'moveDown');
$router->addRoute('POST', '/members/{id}/promote',      'MemberController', 'promote');

// ── Import ────────────────────────────────────────────────────────────────────
$router->addRoute('GET',  '/import',          'ImportController', 'index');
$router->addRoute('GET',  '/import/clear',    'ImportController', 'clearPreview');
$router->addRoute('GET',  '/import/template', 'ImportController', 'downloadTemplate');
$router->addRoute('POST', '/import/preview',  'ImportController', 'preview');
$router->addRoute('POST', '/import/execute',  'ImportController', 'execute');

// ── PDF ───────────────────────────────────────────────────────────────────────
$router->addRoute('POST', '/pdf/generate',  'PdfController', 'generate');
$router->addRoute('GET',  '/pdf/preview2',  'PdfController', 'preview2');
$router->addRoute('POST', '/pdf/generate2', 'PdfController', 'generate2');

// ── Edit requests (admin review of public submissions) ─────────────────────────
$router->addRoute('GET',  '/reviews',              'ReviewController', 'index');
$router->addRoute('GET',  '/reviews/{id}',         'ReviewController', 'show');
$router->addRoute('POST', '/reviews/{id}/approve', 'ReviewController', 'approve');
$router->addRoute('POST', '/reviews/{id}/reject',  'ReviewController', 'reject');

// ── Settings ──────────────────────────────────────────────────────────────────
$router->addRoute('GET',  '/settings',                          'SettingsController', 'index');
$router->addRoute('POST', '/settings',                          'SettingsController', 'update');
$router->addRoute('POST', '/settings/cover-image',              'SettingsController', 'uploadCoverImage');
$router->addRoute('POST', '/settings/relationships',            'SettingsController', 'storeRelationship');
$router->addRoute('POST', '/settings/relationships/{id}/delete','SettingsController', 'destroyRelationship');
$router->addRoute('POST', '/settings/genders',                  'SettingsController', 'storeGender');
$router->addRoute('POST', '/settings/genders/{id}/delete',      'SettingsController', 'destroyGender');

$router->dispatch();
