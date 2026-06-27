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
        $this->view('auth/login');
    }

    /**
     * Handle the login request.
     */
    public function login() {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Username and password are required.';
            $this->view('auth/login', ['error' => $error, 'username' => $username]);
            return;
        }

        $user = $this->userModel->findByUsername($username);

        if ($user) {
            // Check status first
            if ($user['status'] === 'suspended') {
                AuditLog::log('LOGIN_FAILED', 'Auth', "Blocked login attempt: suspended account ({$username})");
                $error = 'This account has been suspended due to security lockout. Please contact an administrator.';
                $this->view('auth/login', ['error' => $error, 'username' => $username]);
                return;
            }

            if ($user['status'] === 'inactive') {
                AuditLog::log('LOGIN_FAILED', 'Auth', "Blocked login attempt: inactive account ({$username})");
                $error = 'This account is inactive. Please contact an administrator.';
                $this->view('auth/login', ['error' => $error, 'username' => $username]);
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
                    $error = 'This account has been suspended due to too many failed login attempts.';
                } else {
                    $remaining = 5 - $attempts;
                    $error = "Invalid username or password. (Failed attempts: {$attempts}/5. {$remaining} attempt(s) remaining before suspension)";
                }
                
                $this->view('auth/login', ['error' => $error, 'username' => $username]);
            }
        } else {
            // Log failed login
            AuditLog::log('LOGIN_FAILED', 'Auth', "Failed login attempt (non-existent username) for: " . $username);
            
            $error = 'Invalid username or password.';
            $this->view('auth/login', ['error' => $error, 'username' => $username]);
        }
    }

    /**
     * Handle logout.
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user_id'])) {
            AuditLog::log('LOGOUT', 'Auth', "User logged out.");
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

        // Redirect to login
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
