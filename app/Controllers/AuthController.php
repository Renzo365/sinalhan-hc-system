<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\AuditLog;

class AuthController extends Controller {
    protected $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * Show the login page.
     */
    public function showLogin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $error = $_SESSION['login_error'] ?? null;
        $username = $_SESSION['login_username'] ?? '';
        
        // One-time session timeout notification via secure server session flash or query parameter
        $timeoutMessage = null;
        if (!empty($_SESSION['session_timed_out']) || isset($_GET['timeout'])) {
            $timeoutMessage = "You have been logged out due to inactivity. Please sign in again to continue.";
            unset($_SESSION['session_timed_out']);
        }
        
        // Clear flash values so they don't persist on subsequent GET requests/refreshes
        unset($_SESSION['login_error']);
        unset($_SESSION['login_username']);
        unset($_SESSION['timeout_message']);

        $this->view('auth/login', [
            'error' => $error,
            'username' => $username,
            'timeoutMessage' => $timeoutMessage
        ]);
    }

    /**
     * Handle the login request.
     */
    public function login() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals(csrf_token(), $token)) {
            AuditLog::log('SECURITY_VIOLATION', 'Auth', 'CSRF token mismatch on login attempt.');
            $this->setLoginError('Invalid session token. Please refresh and try again.', trim($_POST['username'] ?? ''));
            $this->redirect('/login');
            return;
        }

        $identifier = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $this->setLoginError('Username or Employee ID and password are required.', $identifier);
            $this->redirect('/login');
            return;
        }

        $user = $this->userModel->findByLoginIdentifier($identifier);

        if ($user) {
            // Check manual status deactivation
            if ($user['status'] === 'inactive') {
                AuditLog::log('LOGIN_FAILED', 'Auth', "Blocked login attempt: inactive account ({$user['username']} / {$identifier})");
                $this->setLoginError('This account is inactive. Please contact an administrator.', $identifier);
                $this->redirect('/login');
                return;
            }

            // Check temporary 15-minute lockout cooldown
            $lockoutStatus = $this->userModel->isLockedOut($user);
            if ($lockoutStatus['is_locked']) {
                AuditLog::log('LOGIN_BLOCKED_LOCKOUT', 'Auth', "Blocked login attempt during temporary lockout for: {$user['username']} ({$identifier})");
                $this->setLoginError('Too many failed login attempts. Please try again later or contact your administrator.', $identifier);
                $this->redirect('/login');
                return;
            }

            if (password_verify($password, $user['password_hash'])) {
                // Setup Session
                if (!headers_sent()) {
                    session_regenerate_id(true);
                }

                // Update database: reset attempts and update timestamp
                $this->userModel->updateLoginTimestamp($user['id']);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_fullname'] = trim($user['first_name'] . ' ' . $user['last_name']);
                $_SESSION['must_change_password'] = (int)$user['must_change_password'];
                $_SESSION['last_activity'] = time();

                // Log successful login
                AuditLog::log('LOGIN_SUCCESS', 'Auth', "User successfully logged in ({$user['username']}).");

                if ($_SESSION['must_change_password'] === 1) {
                    $this->redirect('/change-password');
                } else {
                    $_SESSION['login_welcome'] = true;
                    $this->redirect('/dashboard');
                }
            } else {
                // Password incorrect - increment attempts
                $attempts = $this->userModel->incrementFailedAttempts($user['username']);
                AuditLog::log('LOGIN_FAILED', 'Auth', "Failed login attempt (password mismatch) for: {$user['username']} ({$identifier}). Failed attempts count: {$attempts}/5");

                if ($attempts >= 5) {
                    AuditLog::log('ACCOUNT_LOCKED', 'Auth', "Account temporarily locked for 15 minutes due to 5 consecutive failed attempts: {$user['username']}");
                    $error = 'Too many failed login attempts. Please try again later or contact your administrator.';
                } else {
                    $error = 'Invalid username or password.';
                }
                
                $this->setLoginError($error, $identifier);
                $this->redirect('/login');
            }
        } else {
            // User does not exist
            AuditLog::log('LOGIN_FAILED', 'Auth', "Failed login attempt (non-existent account) for: " . $identifier);
            $this->setLoginError('Invalid username or password.', $identifier);
            $this->redirect('/login');
        }
    }

    /**
     * Helper to set login flash session variables.
     */
    private function setLoginError($error, $username) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['login_error'] = $error;
        $_SESSION['login_username'] = $username;
    }

    /**
     * Handle logout.
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (empty($token) || !hash_equals(csrf_token(), $token)) {
                AuditLog::log('SECURITY_VIOLATION', 'Auth', 'CSRF token mismatch on logout attempt.');
                $this->redirect('/login');
                return;
            }
        }

        $isTimeout = isset($_GET['timeout']) || (isset($_GET['reason']) && $_GET['reason'] === 'timeout');

        if (isset($_SESSION['user_id'])) {
            if ($isTimeout) {
                AuditLog::log('SESSION_TIMEOUT', 'Auth', "Session expired due to client inactivity for user: " . ($_SESSION['username'] ?? 'User'));
            } else {
                AuditLog::log('LOGOUT', 'Auth', "User logged out.");
            }
        }

        // Clear session variables and destroy session
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => 'Lax'
            ]);
        }

        session_destroy();

        // Redirect to login with timeout parameter if timed out
        if ($isTimeout) {
            $this->redirect('/login?timeout=1');
        } else {
            $this->redirect('/login');
        }
    }

    /**
     * Show password change form.
     */
    public function showChangePassword() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Only allow if they are flagged to change password
        if (!isset($_SESSION['must_change_password']) || $_SESSION['must_change_password'] !== 1) {
            $this->redirect('/dashboard');
            return;
        }

        $user = $this->userModel->findById($_SESSION['user_id']);
        if (!$user) {
            $this->redirect('/login');
            return;
        }

        $this->view('auth/change_password', [
            'user' => $user,
            'disable_layout' => true
        ]);
    }

    /**
     * Process password change.
     */
    public function changePassword() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['must_change_password']) || $_SESSION['must_change_password'] !== 1) {
            $this->redirect('/dashboard');
            return;
        }

        $user = $this->userModel->findById($_SESSION['user_id']);
        if (!$user) {
            $this->redirect('/login');
            return;
        }

        // Verify CSRF Token
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals(csrf_token(), $token)) {
            AuditLog::log('SECURITY_VIOLATION', 'Auth', 'CSRF token mismatch on password change attempt.');
            $this->view('auth/change_password', [
                'user' => $user,
                'errors' => ['Security validation failed (CSRF mismatch). Please try again.'],
                'disable_layout' => true
            ]);
            return;
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [];

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errors[] = 'All fields are required.';
        } else {
            if (!password_verify($currentPassword, $user['password_hash'])) {
                $errors[] = 'Incorrect current temporary password.';
            }

            if (strlen($newPassword) < 8) {
                $errors[] = 'New password must be at least 8 characters long.';
            }

            if (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword)) {
                $errors[] = 'New password must contain both uppercase and lowercase letters.';
            }

            if (!preg_match('/[0-9]/', $newPassword) || !preg_match('/[^A-Za-z0-9]/', $newPassword)) {
                $errors[] = 'New password must contain at least 1 digit and 1 special symbol (@$!%*?&).';
            }

            if ($newPassword === $currentPassword) {
                $errors[] = 'New password must be different from your current temporary password.';
            }

            if ($newPassword !== $confirmPassword) {
                $errors[] = 'New password and confirmation password do not match.';
            }
        }

        if (!empty($errors)) {
            $this->view('auth/change_password', [
                'user' => $user,
                'errors' => $errors,
                'disable_layout' => true
            ]);
            return;
        }

        // Update password in database and clear must_change_password flag
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        if ($this->userModel->updatePassword($user['id'], $newHash, 0)) {
            // Regenerate session ID to prevent session fixation attacks
            if (!headers_sent()) {
                session_regenerate_id(true);
            }

            $_SESSION['must_change_password'] = 0;
            
            AuditLog::log('PASSWORD_CHANGED', 'Auth', "User {$user['username']} updated their password on first login.");

            $_SESSION['success_message'] = 'Password changed successfully! Welcome to the Sinalhan Health Center system.';
            $this->redirect('/dashboard');
        } else {
            $this->view('auth/change_password', [
                'user' => $user,
                'errors' => ['Failed to update password. Please try again.'],
                'disable_layout' => true
            ]);
        }
    }

    /**
     * Session heartbeat endpoint to refresh idle timer and keep session alive.
     */
    public function ping() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'unauthenticated', 'message' => 'Session expired']);
            exit;
        }

        $_SESSION['last_activity'] = time();
        $lastActivity = $_SESSION['last_activity'];
        session_write_close();

        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok', 'last_activity' => $lastActivity]);
        exit;
    }
}
