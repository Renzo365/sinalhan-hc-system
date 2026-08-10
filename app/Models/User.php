<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class User extends Model {
    /**
     * Find a user by their username.
     * 
     * @param string $username
     * @return array|false
     */
    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        return $stmt->fetch();
    }

    /**
     * Find a user by their ID.
     * 
     * @param int $id
     * @return array|false
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Update a user's password.
     * 
     * @param int $userId
     * @param string $newPasswordHash
     * @return bool
     */
    public function updatePassword($userId, $newPasswordHash) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET password_hash = :password_hash, 
                failed_attempts = 0,
                last_failed_login_at = NULL,
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = :id
        ");
        return $stmt->execute([
            'password_hash' => $newPasswordHash,
            'id' => $userId
        ]);
    }

    /**
     * Get all users with optional filters.
     * 
     * @param array $filters
     * @return array
     */
    public function all($filters = []) {
        $sql = "SELECT * FROM users WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (username LIKE :search_user OR first_name LIKE :search_first OR last_name LIKE :search_last OR email LIKE :search_email OR employee_id LIKE :search_emp OR department LIKE :search_dept)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params['search_user'] = $searchTerm;
            $params['search_first'] = $searchTerm;
            $params['search_last'] = $searchTerm;
            $params['search_email'] = $searchTerm;
            $params['search_emp'] = $searchTerm;
            $params['search_dept'] = $searchTerm;
        }

        if (!empty($filters['role'])) {
            if ($filters['role'] === 'main_admin') {
                $sql .= " AND role = 'admin' AND id = 1";
            } elseif ($filters['role'] === 'co_admin') {
                $sql .= " AND role = 'admin' AND id != 1";
            } elseif ($filters['role'] === 'admin') {
                $sql .= " AND role = 'admin'";
            } else {
                $sql .= " AND role = :role";
                $params['role'] = $filters['role'];
            }
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }

        $sql .= " ORDER BY id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Create a new user.
     * 
     * @param array $data
     * @return bool
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO users (username, password_hash, role, first_name, middle_name, last_name, email, contact_no, job_title, employee_id, department, status)
            VALUES (:username, :password_hash, :role, :first_name, :middle_name, :last_name, :email, :contact_no, :job_title, :employee_id, :department, :status)
        ");
        return $stmt->execute([
            'username' => trim($data['username']),
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'role' => $data['role'],
            'first_name' => trim($data['first_name']),
            'middle_name' => !empty($data['middle_name']) ? trim($data['middle_name']) : null,
            'last_name' => trim($data['last_name']),
            'email' => !empty($data['email']) ? trim($data['email']) : null,
            'contact_no' => !empty($data['contact_no']) ? trim($data['contact_no']) : null,
            'job_title' => !empty($data['job_title']) ? trim($data['job_title']) : null,
            'employee_id' => !empty($data['employee_id']) ? trim($data['employee_id']) : null,
            'department' => !empty($data['department']) ? trim($data['department']) : null,
            'status' => $data['status'] ?? 'active'
        ]);
    }

    /**
     * Update user details.
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        $fields = [
            'first_name = :first_name',
            'middle_name = :middle_name',
            'last_name = :last_name',
            'email = :email',
            'contact_no = :contact_no',
            'job_title = :job_title',
            'employee_id = :employee_id',
            'department = :department',
            'role = :role',
            'updated_at = CURRENT_TIMESTAMP'
        ];
        
        $params = [
            'id' => $id,
            'first_name' => trim($data['first_name']),
            'middle_name' => !empty($data['middle_name']) ? trim($data['middle_name']) : null,
            'last_name' => trim($data['last_name']),
            'email' => !empty($data['email']) ? trim($data['email']) : null,
            'contact_no' => !empty($data['contact_no']) ? trim($data['contact_no']) : null,
            'job_title' => !empty($data['job_title']) ? trim($data['job_title']) : null,
            'employee_id' => !empty($data['employee_id']) ? trim($data['employee_id']) : null,
            'department' => !empty($data['department']) ? trim($data['department']) : null,
            'role' => $data['role']
        ];

        if (isset($data['status'])) {
            $fields[] = 'status = :status';
            $params['status'] = $data['status'];
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Toggle status between active and inactive.
     * 
     * @param int $id
     * @param string $status 'active' or 'inactive'
     * @return bool
     */
    public function setStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE users SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }

    /**
     * Increment failed attempts for a username.
     * 
     * @param string $username
     * @return int New count of failed attempts
     */
    /**
     * Increment failed login attempts for a given username.
     * 
     * @param string $username
     * @return int New count of failed attempts
     */
    public function incrementFailedAttempts($username) {
        $resetWindow = 900; // 15 minutes in seconds

        $stmt = $this->db->prepare("
            SELECT id, failed_attempts, status,
                   (last_failed_login_at IS NULL OR TIMESTAMPDIFF(SECOND, last_failed_login_at, NOW()) > :reset_window) as window_expired 
            FROM users 
            WHERE username = :username 
            LIMIT 1
        ");
        $stmt->execute(['username' => $username, 'reset_window' => $resetWindow]);
        $user = $stmt->fetch();

        if ($user) {
            $id = $user['id'];
            
            // If the window has expired, reset current attempts count to 0
            $currentAttempts = $user['window_expired'] ? 0 : (int)$user['failed_attempts'];
            $newAttempts = $currentAttempts + 1;

            $updateStmt = $this->db->prepare("
                UPDATE users 
                SET failed_attempts = :attempts, 
                    last_failed_login_at = NOW() 
                WHERE id = :id
            ");
            $updateStmt->execute(['attempts' => $newAttempts, 'id' => $id]);
            return $newAttempts;
        }
        return 0;
    }

    /**
     * Check if a user account is currently in a 15-minute temporary lockout.
     * 
     * @param array $user User data array containing failed_attempts, last_failed_login_at, id
     * @return array Array containing is_locked (bool), remaining_seconds (int), and remaining_formatted (string)
     */
    public function isLockedOut($user) {
        if (!$user) {
            return ['is_locked' => false, 'remaining_seconds' => 0, 'remaining_formatted' => ''];
        }

        $attempts = (int)($user['failed_attempts'] ?? 0);
        $lastFailed = $user['last_failed_login_at'] ?? null;

        if ($attempts < 5 || empty($lastFailed)) {
            return ['is_locked' => false, 'remaining_seconds' => 0, 'remaining_formatted' => ''];
        }

        $lockoutDuration = 900; // 15 minutes in seconds

        // Calculate elapsed seconds directly in MySQL to prevent PHP/DB timezone mismatches
        $stmt = $this->db->prepare("
            SELECT TIMESTAMPDIFF(SECOND, last_failed_login_at, NOW()) AS elapsed_seconds
            FROM users
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $user['id']]);
        $res = $stmt->fetch();
        $elapsed = (int)($res['elapsed_seconds'] ?? 0);

        if ($elapsed >= 0 && $elapsed < $lockoutDuration) {
            $remaining = $lockoutDuration - $elapsed;
            $mins = floor($remaining / 60);
            $secs = $remaining % 60;
            
            $formatted = '';
            if ($mins > 0) {
                $formatted .= "{$mins} minute(s) ";
            }
            $formatted .= "{$secs} second(s)";

            return [
                'is_locked' => true,
                'remaining_seconds' => $remaining,
                'remaining_formatted' => trim($formatted)
            ];
        }

        return ['is_locked' => false, 'remaining_seconds' => 0, 'remaining_formatted' => ''];
    }

    /**
     * Clear login lockout and reset failed attempts to 0.
     * 
     * @param int $id
     * @return bool
     */
    public function clearLockout($id) {
        $stmt = $this->db->prepare("UPDATE users SET failed_attempts = 0, last_failed_login_at = NULL WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Reset failed attempts back to 0.
     * 
     * @param int $id
     * @return bool
     */
    public function resetFailedAttempts($id) {
        $stmt = $this->db->prepare("UPDATE users SET failed_attempts = 0, last_failed_login_at = NULL WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Update last login timestamp.
     * 
     * @param int $id
     * @return bool
     */
    public function updateLoginTimestamp($id) {
        $stmt = $this->db->prepare("UPDATE users SET last_login_at = CURRENT_TIMESTAMP, failed_attempts = 0, last_failed_login_at = NULL WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Check if username is unique.
     */
    public function isUsernameUnique($username, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM users WHERE username = :username";
        $params = ['username' => trim($username)];
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() == 0;
    }

    /**
     * Check if email is unique.
     */
    public function isEmailUnique($email, $excludeId = null) {
        if (empty($email)) return true;
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        $params = ['email' => trim($email)];
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() == 0;
    }
}
