<?php
/**
 * Auth — session-based authentication guard
 */
class Auth
{
    /**
     * Enforce authentication. Call at the top of every protected route.
     * Redirects to login on failure or session expiry.
     */
    public static function check(): void
    {
        if (empty($_SESSION['admin_id'])) {
            header('Location: ' . BASE_URL . '/admin?auth=required');
            exit;
        }

        // Idle-timeout check
        if (isset($_SESSION['last_activity']) &&
            (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
            session_unset();
            session_destroy();
            header('Location: ' . BASE_URL . '/admin?expired=1');
            exit;
        }

        // Refresh activity timestamp
        $_SESSION['last_activity'] = time();
    }

    /**
     * Return true if the admin is currently logged in (non-redirecting check).
     */
    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['admin_id']);
    }

    /**
     * Return the logged-in admin's ID, or null.
     */
    public static function id(): ?int
    {
        return isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
    }

    /**
     * Return the logged-in admin's username, or empty string.
     */
    public static function username(): string
    {
        return $_SESSION['admin_username'] ?? '';
    }
}
