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
            'role' => trim($_GET['role'] ?? ''),
            'status' => trim($_GET['status'] ?? '')
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

        // Verify CSRF Token
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals(csrf_token(), $token)) {
            AuditLog::log('SECURITY_VIOLATION', 'Users', 'CSRF token verification failed during user account creation.');
            $_SESSION['error_message'] = 'Security validation failed (CSRF token mismatch). Please refresh and try again.';
            $_SESSION['old_input'] = $_POST;
            $this->redirect('/users/create');
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'staff';
        
        // Privilege Escalation Guard: Non-Main Admin cannot create admin accounts
        if ($_SESSION['user_id'] != 1 && $role === 'admin') {
            $role = 'staff';
            $_POST['role'] = 'staff';
        }
        
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contactNo = trim($_POST['contact_no'] ?? '');
        $employeeId = trim($_POST['employee_id'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $status = $_POST['status'] ?? 'active';

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

        // Verify CSRF Token
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals(csrf_token(), $token)) {
            AuditLog::log('SECURITY_VIOLATION', 'Users', "CSRF token verification failed during user account update for user ID {$id}.");
            $_SESSION['error_message'] = 'Security validation failed (CSRF token mismatch). Please refresh and try again.';
            $_SESSION['old_input'] = $_POST;
            $this->redirect("/users/{$id}/edit");
            return;
        }

        $user = $this->userModel->findById($id);
        if (!$user) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        // Peer Admin Protection: Only the Primary Admin or the users themselves can modify an administrator account
        if ($user['role'] === 'admin' && $_SESSION['user_id'] != 1 && $_SESSION['user_id'] != $id) {
            AuditLog::log('SECURITY_VIOLATION', 'Users', "Blocked attempt by user ID {$_SESSION['user_id']} to modify administrator account ID {$id} details.");
            $_SESSION['error_message'] = 'Access Denied: Only the Primary Administrator can modify other administrator accounts.';
            $this->redirect('/users');
            return;
        }

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contactNo = trim($_POST['contact_no'] ?? '');
        $role = $_POST['role'] ?? $user['role'];

        // Role Modification Guard: Only the Primary Admin can change access privileges (roles)
        if ($_SESSION['user_id'] != 1 && $role !== $user['role']) {
            AuditLog::log('SECURITY_VIOLATION', 'Users', "Blocked attempt by user ID {$_SESSION['user_id']} to modify role of account ID {$id} from {$user['role']} to {$role}.");
            $_SESSION['error_message'] = 'Access Denied: Only the Primary Administrator can modify user roles.';
            $this->redirect('/users');
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

        // Verify CSRF Token
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals(csrf_token(), $token)) {
            AuditLog::log('SECURITY_VIOLATION', 'Users', "CSRF token verification failed during password reset for user ID {$id}.");
            $_SESSION['error_message'] = 'Security validation failed (CSRF token mismatch). Please refresh and try again.';
            $this->redirect('/users');
            return;
        }

        $user = $this->userModel->findById($id);
        if (!$user) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        // Peer Admin Protection: Only the Primary Admin or the users themselves can reset an administrator's password
        if ($user['role'] === 'admin' && $_SESSION['user_id'] != 1 && $_SESSION['user_id'] != $id) {
            AuditLog::log('SECURITY_VIOLATION', 'Users', "Blocked attempt by user ID {$_SESSION['user_id']} to reset administrator account ID {$id} password.");
            $_SESSION['error_message'] = 'Access Denied: Only the Primary Administrator can reset the password of other administrator accounts.';
            $this->redirect('/users');
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
     * Toggle status between active and inactive.
     */
    public function toggleStatus($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/users');
            return;
        }

        // Verify CSRF Token
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals(csrf_token(), $token)) {
            AuditLog::log('SECURITY_VIOLATION', 'Users', "CSRF token verification failed during status toggle for user ID {$id}.");
            $_SESSION['error_message'] = 'Security validation failed (CSRF token mismatch). Please refresh and try again.';
            $this->redirect('/users');
            return;
        }

        $user = $this->userModel->findById($id);
        if (!$user) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        // Primary Admin protection rule: Primary Admin cannot be deactivated
        if ($id == 1) {
            AuditLog::log('SECURITY_VIOLATION', 'Users', "Blocked attempt by user ID {$_SESSION['user_id']} to deactivate primary administrator.");
            $_SESSION['error_message'] = 'Access Denied: The Primary Administrator account cannot be deactivated.';
            $this->redirect('/users');
            return;
        }

        // Self-exclusion prevention
        if ($id == $_SESSION['user_id']) {
            $_SESSION['error_message'] = 'Access Denied: You cannot deactivate your own active session account.';
            $this->redirect('/users');
            return;
        }

        // Peer Admin Protection: Only the Primary Admin can deactivate another administrator account
        if ($user['role'] === 'admin' && $_SESSION['user_id'] != 1) {
            AuditLog::log('SECURITY_VIOLATION', 'Users', "Blocked attempt by user ID {$_SESSION['user_id']} to toggle administrator account ID {$id} status.");
            $_SESSION['error_message'] = 'Access Denied: Only the Primary Administrator can change the status of other administrator accounts.';
            $this->redirect('/users');
            return;
        }

        $newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';

        if ($this->userModel->setStatus($id, $newStatus)) {
            $actionText = ($newStatus === 'active') ? 'USER_ACTIVATED' : 'USER_DEACTIVATED';
            AuditLog::log($actionText, 'Users', "Toggled status of user account {$user['username']} to {$newStatus}.");
            
            $statusLabel = ($newStatus === 'active') ? 'activated' : 'deactivated';
            $_SESSION['success_message'] = "User account {$user['username']} has been successfully {$statusLabel}.";
        } else {
            $_SESSION['error_message'] = 'Failed to update user account status. Please try again.';
        }

        $this->redirect('/users');
    }

    /**
     * Clear login lockout and reset failed attempt counter for a user.
     */
    public function resetLockout($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/users');
            return;
        }

        // Verify CSRF Token
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals(csrf_token(), $token)) {
            AuditLog::log('SECURITY_VIOLATION', 'Users', "CSRF token verification failed during lockout reset for user ID {$id}.");
            $_SESSION['error_message'] = 'Security validation failed (CSRF token mismatch). Please refresh and try again.';
            $this->redirect('/users');
            return;
        }

        $user = $this->userModel->findById($id);
        if (!$user) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        // Peer Admin Protection: Only Primary Admin can clear lockouts for other admins
        if ($user['role'] === 'admin' && $_SESSION['user_id'] != 1 && $_SESSION['user_id'] != $id) {
            AuditLog::log('SECURITY_VIOLATION', 'Users', "Blocked attempt by user ID {$_SESSION['user_id']} to clear lockout for administrator account ID {$id}.");
            $_SESSION['error_message'] = 'Access Denied: Only the Primary Administrator can clear lockouts for other administrator accounts.';
            $this->redirect('/users');
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
