<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class User extends Model {
    /**
     * Find a user by their username (excluding soft-deleted).
     * 
     * @param string $username
     * @return array|false
     */
    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username AND deleted_at IS NULL LIMIT 1");
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
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1");
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
                must_change_password = 0,
                failed_attempts = 0,
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = :id
        ");
        return $stmt->execute([
            'password_hash' => $newPasswordHash,
            'id' => $userId
        ]);
    }

    /**
     * Get all users (excluding soft-deleted ones) with optional filters.
     * 
     * @param array $filters
     * @return array
     */
    public function all($filters = []) {
        $sql = "SELECT * FROM users WHERE deleted_at IS NULL";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (username LIKE :search_user OR first_name LIKE :search_first OR last_name LIKE :search_last OR email LIKE :search_email)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params['search_user'] = $searchTerm;
            $params['search_first'] = $searchTerm;
            $params['search_last'] = $searchTerm;
            $params['search_email'] = $searchTerm;
        }

        if (!empty($filters['role'])) {
            $sql .= " AND role = :role";
            $params['role'] = $filters['role'];
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
            INSERT INTO users (username, password_hash, role, first_name, middle_name, last_name, email, contact_no, job_title, status, must_change_password)
            VALUES (:username, :password_hash, :role, :first_name, :middle_name, :last_name, :email, :contact_no, :job_title, :status, :must_change_password)
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
            'status' => $data['status'] ?? 'active',
            'must_change_password' => isset($data['must_change_password']) ? (int)$data['must_change_password'] : 1
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
            'role = :role',
            'status = :status',
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
            'role' => $data['role'],
            'status' => $data['status']
        ];

        if (isset($data['must_change_password'])) {
            $fields[] = 'must_change_password = :must_change_password';
            $params['must_change_password'] = (int)$data['must_change_password'];
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Soft-delete a user.
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $stmt = $this->db->prepare("UPDATE users SET deleted_at = CURRENT_TIMESTAMP, status = 'inactive' WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Increment failed attempts for a username.
     * 
     * @param string $username
     * @return int New count of failed attempts
     */
    public function incrementFailedAttempts($username) {
        $stmt = $this->db->prepare("UPDATE users SET failed_attempts = failed_attempts + 1 WHERE username = :username AND deleted_at IS NULL");
        $stmt->execute(['username' => $username]);

        $stmt = $this->db->prepare("SELECT failed_attempts, id, status FROM users WHERE username = :username AND deleted_at IS NULL LIMIT 1");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user) {
            // Auto suspend if attempts >= 5
            if ($user['failed_attempts'] >= 5 && $user['status'] !== 'suspended') {
                $suspendStmt = $this->db->prepare("UPDATE users SET status = 'suspended' WHERE id = :id");
                $suspendStmt->execute(['id' => $user['id']]);
                return 5;
            }
            return (int)$user['failed_attempts'];
        }
        return 0;
    }

    /**
     * Reset failed attempts back to 0.
     * 
     * @param int $id
     * @return bool
     */
    public function resetFailedAttempts($id) {
        $stmt = $this->db->prepare("UPDATE users SET failed_attempts = 0 WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Update last login timestamp.
     * 
     * @param int $id
     * @return bool
     */
    public function updateLoginTimestamp($id) {
        $stmt = $this->db->prepare("UPDATE users SET last_login_at = CURRENT_TIMESTAMP, failed_attempts = 0 WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Check if username is unique.
     */
    public function isUsernameUnique($username, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM users WHERE username = :username AND deleted_at IS NULL";
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
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email AND deleted_at IS NULL";
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
