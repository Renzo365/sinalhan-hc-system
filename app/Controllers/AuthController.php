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
        
        // One-time session timeout notification via secure server session flash
        $timeoutMessage = null;
        if (!empty($_SESSION['session_timed_out'])) {
            $timeoutMessage = "You've been logged out due to inactivity. Please sign in again.";
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
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->setLoginError('Username and password are required.', $username);
            $this->redirect('/login');
            return;
        }

        $user = $this->userModel->findByUsername($username);

        if ($user) {
            // Check manual status deactivation
            if ($user['status'] === 'inactive') {
                AuditLog::log('LOGIN_FAILED', 'Auth', "Blocked login attempt: inactive account ({$username})");
                $this->setLoginError('This account is inactive. Please contact an administrator.', $username);
                $this->redirect('/login');
                return;
            }

            // Check temporary 15-minute lockout cooldown
            $lockoutStatus = $this->userModel->isLockedOut($user);
            if ($lockoutStatus['is_locked']) {
                AuditLog::log('LOGIN_BLOCKED_LOCKOUT', 'Auth', "Blocked login attempt during temporary lockout for username: {$username}");
                $this->setLoginError('Too many failed login attempts. Please try again later or contact your administrator.', $username);
                $this->redirect('/login');
                return;
            }

            if (password_verify($password, $user['password_hash'])) {
                // Setup Session
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                
                session_regenerate_id(true);

                // Update database: reset attempts and update timestamp
                $this->userModel->updateLoginTimestamp($user['id']);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_fullname'] = trim($user['first_name'] . ' ' . $user['last_name']);
                $_SESSION['must_change_password'] = (int)$user['must_change_password'];
                $_SESSION['last_activity'] = time();

                // Log successful login
                AuditLog::log('LOGIN_SUCCESS', 'Auth', "User successfully logged in.");

                if ($_SESSION['must_change_password'] === 1) {
                    $this->redirect('/change-password');
                } else {
                    $_SESSION['login_welcome'] = true;
                    $this->redirect('/dashboard');
                }
            } else {
                // Password incorrect - increment attempts
                $attempts = $this->userModel->incrementFailedAttempts($username);
                AuditLog::log('LOGIN_FAILED', 'Auth', "Failed login attempt (password mismatch) for username: {$username}. Failed attempts count: {$attempts}/5");

                if ($attempts >= 5) {
                    AuditLog::log('ACCOUNT_LOCKED', 'Auth', "Account temporarily locked for 15 minutes due to 5 consecutive failed attempts: {$username}");
                    $error = 'Too many failed login attempts. Please try again later or contact your administrator.';
                } else {
                    $error = 'Invalid username or password.';
                }
                
                $this->setLoginError($error, $username);
                $this->redirect('/login');
            }
        } else {
            // User does not exist
            AuditLog::log('LOGIN_FAILED', 'Auth', "Failed login attempt (non-existent username) for: " . $username);
            $this->setLoginError('Invalid username or password.', $username);
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

        $isTimeout = isset($_GET['timeout']) || (isset($_GET['reason']) && $_GET['reason'] === 'timeout');

        if (isset($_SESSION['user_id'])) {
            if ($isTimeout) {
                AuditLog::log('SESSION_TIMEOUT', 'Auth', "Session expired due to client inactivity for user: " . ($_SESSION['username'] ?? 'User'));
            } else {
                AuditLog::log('LOGOUT', 'Auth', "User logged out.");
            }
        }

        // Clear session variables
        $_SESSION = [];

        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destroy session
        session_destroy();

        // If timed out, flash one-time notification flag into a clean new session
        if ($isTimeout) {
            session_start();
            $_SESSION['session_timed_out'] = true;
        }

        // Redirect to login cleanly without query parameters
        $this->redirect('/login');
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
        }

        $this->view('auth/change_password', ['disable_layout' => true]);
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
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [];

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errors[] = 'All fields are required.';
        }

        $user = $this->userModel->findById($_SESSION['user_id']);

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            $errors[] = 'Incorrect current password.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New password and confirmation password do not match.';
        }

        if (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters long.';
        }

        if ($newPassword === $currentPassword) {
            $errors[] = 'New password cannot be the same as the current password.';
        }

        if (!empty($errors)) {
            $this->view('auth/change_password', ['errors' => $errors, 'disable_layout' => true]);
            return;
        }

        // Update password
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        if ($this->userModel->updatePassword($user['id'], $newHash)) {
            $_SESSION['must_change_password'] = 0;
            
            AuditLog::log('PASSWORD_CHANGED', 'Auth', "User updated their password on first login.");

            $_SESSION['success_message'] = 'Password changed successfully! Welcome to the system.';
            $this->redirect('/dashboard');
        } else {
            $this->view('auth/change_password', ['errors' => ['Failed to update password. Please try again.'], 'disable_layout' => true]);
        }
    }
}
