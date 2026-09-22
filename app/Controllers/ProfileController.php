<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\AuditLog;

class ProfileController extends Controller {
    protected $userModel;

    public function __construct() {
        $this->userModel = new User();

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        // Guard: ensure user is authenticated
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
            exit;
        }
    }

    /**
     * Display the current user's profile overview and settings.
     */
    public function index() {
        $userId = $_SESSION['user_id'];
        $user = $this->userModel->findById($userId);

        if (!$user) {
            $_SESSION['error_message'] = 'User record could not be found.';
            $this->redirect('/login');
            return;
        }

        $this->view('profile/index', [
            'title' => 'My Profile',
            'user' => $user
        ]);
    }

    /**
     * Update self-service contact details (First Name, Middle Name, Last Name, Email, Contact No).
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/profile');
            return;
        }



        $userId = (int)$_SESSION['user_id'];
        $firstName = trim($_POST['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contactNo = trim($_POST['contact_no'] ?? '');

        $errors = [];

        // Required fields validation
        if (empty($firstName) || empty($lastName)) {
            $errors[] = 'First Name and Last Name are required fields.';
        }

        // Email validation & uniqueness check
        if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            } elseif (!$this->userModel->isEmailUnique($email, $userId)) {
                $errors[] = 'The email address is already registered to another account.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['profile_errors'] = $errors;
            $_SESSION['old_profile'] = $_POST;
            $this->redirect('/profile');
            return;
        }

        $updateData = [
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'email' => $email,
            'contact_no' => $contactNo
        ];

        if ($this->userModel->updateProfile($userId, $updateData)) {
            // Synchronize active session full name immediately
            $_SESSION['user_fullname'] = trim($firstName . ' ' . $lastName);

            AuditLog::log('PROFILE_UPDATED', 'users', $userId, 'Updated personal profile contact details');
            $_SESSION['success_message'] = 'Your profile details have been successfully updated.';
        } else {
            $_SESSION['error_message'] = 'Failed to update profile details. Please try again.';
            $_SESSION['old_profile'] = $_POST;
        }

        $this->redirect('/profile');
    }

    /**
     * Handle voluntary password change for the current authenticated user.
     */
    public function updatePassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/profile#password-settings');
            return;
        }



        $userId = (int)$_SESSION['user_id'];
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [];

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errors[] = 'Current password, new password, and confirmation password are all required.';
        }

        $user = $this->userModel->findById($userId);
        if (!$user) {
            $_SESSION['error_message'] = 'Account record not found.';
            $this->redirect('/login');
            return;
        }

        if (!empty($currentPassword) && !password_verify($currentPassword, $user['password_hash'])) {
            $errors[] = 'The current password you entered is incorrect.';
        }

        if (!empty($newPassword)) {
            if (strlen($newPassword) < 8) {
                $errors[] = 'New password must be at least 8 characters long.';
            }

            if ($newPassword !== $confirmPassword) {
                $errors[] = 'The new password and confirmation password do not match.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['password_errors'] = $errors;
            $this->redirect('/profile#password-settings');
            return;
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);

        if ($this->userModel->updatePassword($userId, $newHash, 0)) {
            // Defend against session fixation / hijacking after credential modification
            if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
                session_regenerate_id(true);
            }

            AuditLog::log('USER_PASSWORD_CHANGED', 'users', $userId, 'Voluntarily updated account password');
            $_SESSION['success_message'] = 'Your password has been changed successfully.';
        } else {
            $_SESSION['error_message'] = 'Failed to update your password. Please try again.';
        }

        $this->redirect('/profile#password-settings');
    }
}
