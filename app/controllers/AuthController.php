<?php
/**
 * AuthController — login / logout
 */
class AuthController extends BaseController
{
    private AdminUserModel   $userModel;
    private LoginAttemptModel $attemptModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel    = new AdminUserModel();
        $this->attemptModel = new LoginAttemptModel();
    }

    /**
     * GET / — show login form
     */
    public function showLogin(): void
    {
        // Already logged in → dashboard
        if (Auth::isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        $notice = '';
        if (!empty($_GET['auth']))       $notice = 'Please log in to access this page.';
        elseif (!empty($_GET['expired'])) $notice = 'Your session has expired. Please log in again.';
        elseif (!empty($_GET['logged_out'])) $notice = 'You have been logged out successfully.';

        $this->render('auth/login', [
            'notice'   => $notice,
            'error'    => '',
            'attempts' => 0,
            'username' => '',
        ], 'auth');
    }

    /**
     * POST / — process login credentials
     */
    public function processLogin(): void
    {
        $this->request->validateCsrf();

        $username = $this->request->post('username', '');
        $password = $this->request->post('password', '');
        $ip       = $this->request->ip();

        // Brute-force block check
        $recentCount = $this->attemptModel->countRecent($ip, LOGIN_BLOCK_MINUTES);
        if ($recentCount >= MAX_LOGIN_ATTEMPTS) {
            $unblockTime = $this->attemptModel->blockExpiresAt($ip);
            $this->render('auth/login', [
                'notice'   => '',
                'error'    => 'Too many failed attempts. This IP is blocked for ' . LOGIN_BLOCK_MINUTES . ' minutes. Try again after ' . $unblockTime . '.',
                'attempts' => $recentCount,
                'username' => htmlspecialchars($username, ENT_QUOTES, 'UTF-8'),
            ], 'auth');
            return;
        }

        $user = $this->userModel->findByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->attemptModel->record($ip);
            $newCount = $recentCount + 1;

            $errorMsg = 'Invalid username or password.';
            if ($newCount >= 3) {
                $errorMsg .= " ({$newCount} of " . MAX_LOGIN_ATTEMPTS . " attempts used.)";
            }

            $this->render('auth/login', [
                'notice'   => '',
                'error'    => $errorMsg,
                'attempts' => $newCount,
                'username' => htmlspecialchars($username, ENT_QUOTES, 'UTF-8'),
            ], 'auth');
            return;
        }

        // ── Success ───────────────────────────────────────────────────────────
        $this->attemptModel->clearForIp($ip);
        session_regenerate_id(true);

        $_SESSION['admin_id']       = (int) $user['admin_id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['last_activity']  = time();
        // Generate fresh CSRF token on login
        $_SESSION['csrf_token']     = bin2hex(random_bytes(32));

        $this->userModel->updateLastLogin((int) $user['admin_id']);

        $this->redirect('/dashboard');
    }

    /**
     * GET /logout — destroy session and redirect
     */
    public function logout(): void
    {
        // CSRF check via POST form — but logout is GET in the spec, so we allow both
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        header('Location: ' . BASE_URL . '/admin?logged_out=1');
        exit;
    }
}
