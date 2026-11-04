<?php
/**
 * BaseController — shared rendering, redirection, flash, and CSRF helpers
 */
class BaseController
{
    protected Request $request;

    public function __construct()
    {
        $this->request = new Request();
    }

    // ── Rendering ─────────────────────────────────────────────────────────────

    /**
     * Render a view inside a layout.
     *
     * @param string $view   e.g. 'dashboard/index'
     * @param array  $data   Variables extracted into the view scope
     * @param string $layout 'main' or 'auth'
     */
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        // Make data available as variables
        extract($data, EXTR_SKIP);

        // Capture the inner view
        ob_start();
        $viewFile = __DIR__ . '/../../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new RuntimeException("View not found: {$viewFile}");
        }
        require $viewFile;
        $content = ob_get_clean();

        // Render inside layout
        $layoutFile = __DIR__ . '/../../views/layouts/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            throw new RuntimeException("Layout not found: {$layoutFile}");
        }
        require $layoutFile;
    }

    // ── Redirect ──────────────────────────────────────────────────────────────

    /**
     * Redirect to a URL relative to BASE_URL.
     */
    protected function redirect(string $url): never
    {
        header('Location: ' . BASE_URL . $url);
        exit;
    }

    // ── Flash messages ────────────────────────────────────────────────────────

    /**
     * Store a flash message in session.
     *
     * @param string $type    'success' | 'error' | 'warning' | 'info'
     * @param string $message
     */
    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    /**
     * Retrieve and clear the flash message.
     */
    public function getFlash(): ?array
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    // ── CSRF ──────────────────────────────────────────────────────────────────

    /**
     * Generate or return the session-lifetime CSRF token.
     */
    public function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Output a hidden CSRF input field.
     */
    public function csrfField(): string
    {
        $token = $this->csrfToken();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Abort with an HTTP status code and optional message.
     */
    protected function abort(int $code, string $message = ''): never
    {
        http_response_code($code);
        echo "<h1>{$code}</h1><p>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        exit;
    }
}
