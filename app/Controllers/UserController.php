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
        
        if (!is_admin()) {
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

        // Precompute and attach lockout information for each user
        foreach ($users as &$u) {
            $u['lockout_info'] = $this->userModel->isLockedOut($u);
        }
        unset($u);

        $this->view('users/index', [
            'users' => $users,
            'filters' => $filters
        ]);
    }

    /**
     * Show create user form page.
     */
    public function create() {
        $this->view('users/create');
    }

    /**
     * Create a new user account.
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/users/create');
            return;
        }



        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'staff';
        
        if (!in_array($role, ['admin', 'staff'], true)) {
            $role = 'staff';
            $_POST['role'] = 'staff';
        }
        
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contactNo = trim($_POST['contact_no'] ?? '');
        $employeeId = trim($_POST['employee_id'] ?? '');
        $department = trim($_POST['department'] ?? '');

        $validator = new \App\Validators\UserValidator($this->userModel);
        $errors = $validator->validateCreate($_POST);

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            $this->redirect('/users/create');
            return;
        }

        $data = $_POST;

        if ($this->userModel->create($data)) {
            AuditLog::log('USER_CREATED', 'Users', "Created new user account: {$username} ({$firstName} {$lastName}) with role: {$role}");
            $_SESSION['success_message'] = "User account {$username} successfully created!";
            $this->redirect('/users');
        } else {
            $_SESSION['error_message'] = 'Failed to create user account. Please try again.';
            $_SESSION['old_input'] = $_POST;
            $this->redirect('/users/create');
        }
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
        $contactNo = trim($_POST['contact_no'] ?? '');
        $role = $_POST['role'] ?? $user['role'];

        if (!in_array($role, ['admin', 'staff'], true)) {
            $role = $user['role'];
            $_POST['role'] = $role;
        }

        // Prevent admin from demoting their own account
        if ($id == $_SESSION['user_id'] && $role !== 'admin') {
            $_SESSION['error_message'] = 'You cannot revoke your own administrator privilege.';
            $this->redirect("/users/{$id}/edit");
            return;
        }

        $validator = new \App\Validators\UserValidator($this->userModel);
        $errors = $validator->validateUpdate($id, $_POST, (int)$_SESSION['user_id']);

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            $this->redirect("/users/{$id}/edit");
            return;
        }

        $data = $_POST;

        if ($this->userModel->update($id, $data)) {
            // Synchronize session full name in real time if editing current user
            if ($id == $_SESSION['user_id']) {
                $_SESSION['user_fullname'] = trim($firstName . ' ' . $lastName);
                if ($role !== $_SESSION['user_role']) {
                    $_SESSION['user_role'] = $role;
                }
            }

            AuditLog::log('USER_UPDATED', 'Users', "Updated user account settings for: {$user['username']}. Name: {$firstName} {$lastName}, Role: {$role}");
            $_SESSION['success_message'] = 'User account updated successfully.';
            $this->redirect('/users');
        } else {
            $_SESSION['error_message'] = 'Failed to update user account. Please try again.';
            $_SESSION['old_input'] = $_POST;
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



        $adminPassword = $_POST['admin_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $validator = new \App\Validators\UserValidator($this->userModel);
        $errors = $validator->validatePasswordReset($user, $_POST, (int)$_SESSION['user_id']);

        if (!empty($errors)) {
            $_SESSION['error_message'] = implode(' ', $errors);
            $this->redirect('/users');
            return;
        }

        // Proceed to update password and enforce password change on next login
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        if ($this->userModel->updatePassword($id, $hashedPassword, 1)) {
            AuditLog::log('USER_PASSWORD_RESET', 'Users', "Administrative password reset for account: {$user['username']}");
            $_SESSION['success_message'] = "Password for user {$user['username']} has been successfully reset. They will be prompted to set a new password on their next login.";
        } else {
            $_SESSION['error_message'] = 'Failed to reset password. Please try again.';
        }

        $this->redirect('/users');
    }

    /**
     * Archive (soft-delete) user account.
     */
    public function archive($id) {
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

        // Self-exclusion prevention
        if ($id == $_SESSION['user_id']) {
            $_SESSION['error_message'] = 'Access Denied: You cannot archive your own active session account.';
            $this->redirect('/users');
            return;
        }

        if ($this->userModel->archive($id)) {
            AuditLog::log('USER_ARCHIVED', 'Users', "Archived user account: {$user['username']} ({$user['first_name']} {$user['last_name']}).");
            $_SESSION['success_message'] = "User account {$user['username']} has been archived and moved to the Archived Records Hub.";
        } else {
            $_SESSION['error_message'] = 'Failed to archive user account. Please try again.';
        }

        $this->redirect('/users');
    }

    /**
     * Restore an archived user account.
     */
    public function restore($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/archive?tab=users');
            return;
        }



        $user = $this->userModel->findById($id);
        if (!$user) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }



        if ($this->userModel->restore($id)) {
            AuditLog::log('USER_RESTORED', 'Users', "Restored user account: {$user['username']} ({$user['first_name']} {$user['last_name']}).");
            $_SESSION['success_message'] = "User account {$user['username']} has been restored successfully and is now active.";
        } else {
            $_SESSION['error_message'] = 'Failed to restore user account. Please try again.';
        }

        $this->redirect('/archive?tab=users');
    }

    /**
     * Clear login lockout and reset failed attempt counter for a user.
     */
    public function resetLockout($id) {
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



        if ($this->userModel->clearLockout($id)) {
            AuditLog::log('USER_LOCKOUT_RESET', 'Users', "Administrative lockout override executed for user: {$user['username']}. Failed attempts reset to 0.");
            $_SESSION['success_message'] = "Login lockout for user {$user['username']} has been cleared successfully.";
        } else {
            $_SESSION['error_message'] = 'Failed to clear login lockout. Please try again.';
        }

        $this->redirect('/users');
    }
}
