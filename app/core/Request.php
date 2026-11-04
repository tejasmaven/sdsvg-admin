<?php
/**
 * Request — safe HTTP input wrapper
 */
class Request
{
    /**
     * Sanitised GET parameter.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return isset($_GET[$key]) ? $this->sanitize($_GET[$key]) : $default;
    }

    /**
     * Sanitised POST parameter.
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return isset($_POST[$key]) ? $this->sanitize($_POST[$key]) : $default;
    }

    /**
     * Validate the CSRF token.  Sends 403 and terminates on mismatch.
     */
    public function validateCsrf(): void
    {
        $token        = $_POST['_token'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';

        if (empty($token) || empty($sessionToken) || !hash_equals($sessionToken, $token)) {
            http_response_code(403);
            echo '<h1>403 Forbidden — CSRF token mismatch.</h1>';
            exit;
        }
    }

    /**
     * Return an uploaded file array from $_FILES, or null.
     */
    public function file(string $key): ?array
    {
        return (isset($_FILES[$key]) && $_FILES[$key]['error'] !== UPLOAD_ERR_NO_FILE)
            ? $_FILES[$key]
            : null;
    }

    /**
     * Return the client IP address.
     */
    public function ip(): string
    {
        // Prefer real IP behind proxies, falling back to REMOTE_ADDR
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                // X-Forwarded-For can be a list; take the first
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function sanitize(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        return trim((string) $value);
    }
}
