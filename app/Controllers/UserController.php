<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\AuditLog;
use PDO;

class UserController extends Controller {
    protected $userModel;

    public function __construct() {
        $this->userModel = new User();
        
        // Safety lock: ensure only admin users can access this controller
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            $_SESSION['error_message'] = 'Unauthorized access to user management panel.';
            $this->redirect('/dashboard');
            exit;
        }
    }

    /**
     * Display a listing of user accounts.
     */
    public function index() {
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'role' => trim($_GET['role'] ?? '')
        ];

        $users = $this->userModel->all($filters);

        $this->view('users/index', [
            'users' => $users,
            'filters' => $filters
        ]);
    }

    /**
     * Create a new user account.
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/users');
            return;
        }


        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'staff';
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $status = $_POST['status'] ?? 'active';

        $errors = [];

        if (empty($username) || empty($password) || empty($firstName) || empty($lastName)) {
            $errors[] = 'Username, Password, First Name, and Last Name are required.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        // Validate username uniqueness
        if (!$this->userModel->isUsernameUnique($username)) {
            $errors[] = 'Username is already taken by another account.';
        }

        // Validate email uniqueness
        if (!empty($email) && !$this->userModel->isEmailUnique($email)) {
            $errors[] = 'Email is already registered by another account.';
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            $this->redirect('/users');
            return;
        }

        $data = $_POST;
        // Force reset password flag for new accounts so they are forced to change it on first login
        $data['must_change_password'] = 1;

        if ($this->userModel->create($data)) {
            AuditLog::log('USER_CREATED', 'Users', "Created new user account: {$username} ({$firstName} {$lastName}) with role: {$role}");
            $_SESSION['success_message'] = "User account successfully created! Password change will be required on their first login.";
        } else {
            $_SESSION['error_message'] = 'Failed to create user account. Please try again.';
        }

        $this->redirect('/users');
    }

    /**
     * Show edit form for user account.
     */
    public function edit($id) {
        $user = $this->userModel->findById($id);
        if (!$user) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $this->view('users/edit', [
            'user' => $user
        ]);
    }

    /**
     * Update user details.
     */
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/users');
            return;
        }


        $user = $this->userModel->findById($id);
        if (!$user) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? $user['role'];
        $status = $_POST['status'] ?? $user['status'];

        $errors = [];

        if (empty($firstName) || empty($lastName)) {
            $errors[] = 'First Name and Last Name are required.';
        }

        // Validate email uniqueness excluding current user
        if (!empty($email) && !$this->userModel->isEmailUnique($email, $id)) {
            $errors[] = 'Email is already registered by another account.';
        }

        // Prevent self-deactivation or self-role change to maintain at least one active admin
        if ($id == $_SESSION['user_id']) {
            if ($status !== 'active') {
                $errors[] = 'You cannot suspend or deactivate your own active session account.';
            }
            if ($role !== 'admin') {
                $errors[] = 'You cannot revoke your own administrator privilege.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $this->redirect("/users/{$id}/edit");
            return;
        }

        $data = $_POST;
        // Preserve must_change_password status unless modified
        $data['must_change_password'] = isset($_POST['must_change_password']) ? (int)$_POST['must_change_password'] : $user['must_change_password'];

        if ($this->userModel->update($id, $data)) {
            AuditLog::log('USER_UPDATED', 'Users', "Updated user account settings for: {$user['username']}. Name: {$firstName} {$lastName}, Role: {$role}, Status: {$status}");
            $_SESSION['success_message'] = 'User account updated successfully.';
            $this->redirect('/users');
        } else {
            $_SESSION['error_message'] = 'Failed to update user account. Please try again.';
            $this->redirect("/users/{$id}/edit");
        }
    }

    /**
     * Reset user password to default.
     */
    public function resetPassword($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/users');
            return;
        }


        $user = $this->userModel->findById($id);
        if (!$user) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        // Default temporary password
        $defaultPassword = 'SinalhanStaff@123';
        $hashedPassword = password_hash($defaultPassword, PASSWORD_BCRYPT);

        if ($this->userModel->updatePassword($id, $hashedPassword)) {
            // Force reset flag back to 1
            try {
                $db = \App\Core\Database::getInstance()->getConnection();
                $stmt = $db->prepare("UPDATE users SET must_change_password = 1 WHERE id = :id");
                $stmt->execute(['id' => $id]);
            } catch (\Exception $e) {
                // Silently ignore or log
            }

            AuditLog::log('USER_PASSWORD_RESET', 'Users', "Administrative password reset for account: {$user['username']}");
            $_SESSION['success_message'] = "Password for user {$user['username']} has been reset to default: <strong>{$defaultPassword}</strong>. They will be forced to change it on their next login.";
        } else {
            $_SESSION['error_message'] = 'Failed to reset password. Please try again.';
        }

        $this->redirect('/users');
    }

    /**
     * Delete/Archive a user account (Soft-Delete).
     */
    public function toggleStatus($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/users');
            return;
        }


        $user = $this->userModel->findById($id);
        if (!$user) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        if ($id == $_SESSION['user_id']) {
            $_SESSION['error_message'] = 'You cannot deactivate or delete your own active administrator account.';
            $this->redirect('/users');
            return;
        }

        if ($this->userModel->delete($id)) {
            AuditLog::log('USER_DELETED', 'Users', "Soft-deleted user account: {$user['username']}");
            $_SESSION['success_message'] = "User account {$user['username']} has been deactivated and removed from the active directory.";
        } else {
            $_SESSION['error_message'] = 'Failed to deactivate user account. Please try again.';
        }

        $this->redirect('/users');
    }
}
