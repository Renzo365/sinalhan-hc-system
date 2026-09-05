<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class AuditLog extends Model {
    /**
     * Write an audit log entry.
     * 
     * @param string $action Action name (e.g. 'LOGIN_SUCCESS')
     * @param string $module System module (e.g. 'Auth')
     * @param string|null $details Extra text description or JSON data
     * @return bool
     */
    public static function log($action, $module, $details = null) {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? null;
        
        // Retrieve client details
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, module, ip_address, user_agent, details)
                VALUES (:user_id, :username, :action, :module, :ip_address, :user_agent, :details)
            ");
            
            return $stmt->execute([
                'user_id' => $userId,
                'username' => $username,
                'action' => $action,
                'module' => $module,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'details' => $details
            ]);
        } catch (\PDOException $e) {
            // Silently ignore log errors in production to avoid crashing, but log to error_log
            error_log("Failed to write audit log: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve all audit logs based on filters.
     * 
     * @param array $filters Filter terms (date_from, date_to, user_id, action)
     * @return array List of matching audit logs
     */
    public function allFiltered($filters = []) {
        $sql = "SELECT a.*, u.role AS user_role, u.id AS user_account_id, CONCAT(u.first_name, ' ', u.last_name) AS user_fullname 
                FROM audit_logs a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(a.created_at) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(a.created_at) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND a.user_id = :user_id";
            $params['user_id'] = (int)$filters['user_id'];
        }

        if (!empty($filters['role'])) {
            if ($filters['role'] === 'main_admin') {
                $sql .= " AND (u.role = 'admin' AND u.id = 1)";
            } elseif ($filters['role'] === 'co_admin') {
                $sql .= " AND (u.role = 'admin' AND u.id != 1)";
            } elseif ($filters['role'] === 'staff') {
                $sql .= " AND u.role = 'staff'";
            }
        }

        if (!empty($filters['action'])) {
            $sql .= " AND a.action = :action";
            $params['action'] = $filters['action'];
        }

        $sql .= " ORDER BY a.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Fetch list of unique actions for filter dropdown.
     * 
     * @return array
     */
    public function getUniqueActions() {
        $stmt = $this->db->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }
}
