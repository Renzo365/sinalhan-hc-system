<?php

namespace App\Validators;

use App\Models\User;

class UserValidator extends BaseValidator {
    /**
     * @var User|null
     */
    protected $userModel;

    public function __construct(User $userModel = null) {
        $this->userModel = $userModel ?: new User();
    }

    /**
     * Validate inputs for user creation.
     * 
     * @param array $input
     * @return array
     */
    public function validateCreate(array $input) {
        $this->clearErrors();

        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        $firstName = trim($input['first_name'] ?? '');
        $lastName = trim($input['last_name'] ?? '');
        $email = trim($input['email'] ?? '');
        $contactNo = trim($input['contact_no'] ?? '');

        if (empty($username) || empty($password) || empty($firstName) || empty($lastName)) {
            $this->addError('Username, Password, First Name, and Last Name are required.');
        }

        if (!empty($username) && !preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            $this->addError('Username must be 3 to 20 alphanumeric characters (underscores allowed).');
        }

        if (strlen($password) < 8) {
            $this->addError('Password must be at least 8 characters long.');
        }

        if (!empty($username) && $this->userModel && !$this->userModel->isUsernameUnique($username)) {
            $this->addError('Username is already taken by another account.');
        }

        if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addError('Invalid email address format.');
            } elseif ($this->userModel && !$this->userModel->isEmailUnique($email)) {
                $this->addError('Email is already registered by another account.');
            }
        }

        if (!empty($contactNo) && !preg_match('/^09\d{9}$/', $contactNo)) {
            $this->addError('Contact number must be an 11-digit Philippine mobile number starting with 09 (e.g., 09171234567).');
        }

        return $this->errors;
    }

    /**
     * Validate inputs for user updating.
     * 
     * @param int $id
     * @param array $input
     * @param int $currentUserId
     * @return array
     */
    public function validateUpdate($id, array $input, $currentUserId) {
        $this->clearErrors();

        $firstName = trim($input['first_name'] ?? '');
        $lastName = trim($input['last_name'] ?? '');
        $email = trim($input['email'] ?? '');
        $contactNo = trim($input['contact_no'] ?? '');
        $role = $input['role'] ?? 'staff';

        if (empty($firstName) || empty($lastName)) {
            $this->addError('First Name and Last Name are required.');
        }

        if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addError('Invalid email address format.');
            } elseif ($this->userModel && !$this->userModel->isEmailUnique($email, $id)) {
                $this->addError('Email is already registered by another account.');
            }
        }

        if (!empty($contactNo) && !preg_match('/^09\d{9}$/', $contactNo)) {
            $this->addError('Contact number must be an 11-digit Philippine mobile number starting with 09 (e.g., 09171234567).');
        }

        if ($id == $currentUserId && !in_array($role, ['admin', 'super_admin'], true)) {
            $this->addError('You cannot revoke your own administrator privilege.');
        }

        return $this->errors;
    }

    /**
     * Validate password reset inputs.
     * 
     * @param array $user Target user row
     * @param array $input
     * @param int $currentAdminId
     * @return array
     */
    public function validatePasswordReset(array $user, array $input, $currentAdminId) {
        $this->clearErrors();

        $adminPassword = $input['admin_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';
        $confirmPassword = $input['confirm_password'] ?? '';

        if (empty($adminPassword) || empty($newPassword) || empty($confirmPassword)) {
            $this->addError('All fields are required.');
        }

        if ($newPassword !== $confirmPassword) {
            $this->addError('New password and confirmation password do not match.');
        }

        if (strlen($newPassword) < 8) {
            $this->addError('New temporary password must be at least 8 characters long.');
        }

        if (!empty($newPassword) && !empty($user['password_hash']) && password_verify($newPassword, $user['password_hash'])) {
            $this->addError("The new temporary password cannot be the same as the user's current password.");
        }

        if ($this->userModel) {
            $admin = $this->userModel->findById($currentAdminId);
            if (!$admin || !password_verify($adminPassword, $admin['password_hash'])) {
                $this->addError('Incorrect administrator authorization password.');
            }
        }

        return $this->errors;
    }
}
