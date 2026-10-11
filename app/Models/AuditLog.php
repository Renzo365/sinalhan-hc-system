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
     * @param mixed $extra Optional record ID or supplementary detail
     * @param string|null $actorUsername Explicit username for unauthenticated or system operations
     * @return bool
     */
    public static function log($action, $module, $details = null, $extra = null, ?string $actorUsername = null) {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? null;

        // Use explicit actor username if provided (e.g. for failed logins or unauthenticated security events)
        if (!empty($actorUsername)) {
            $username = trim($actorUsername);
        }

        // Handle 4-parameter calls where 3rd is target/record ID and 4th is details text
        if ($extra !== null) {
            if (is_string($extra)) {
                $details = ($details !== null && $details !== '') ? "Target #{$details}: {$extra}" : $extra;
            } else {
                $details = (string)$details;
            }
        }

        // Resolve username from database if user_id is set but session username is absent
        if ($username === null && $userId !== null) {
            try {
                $db = Database::getInstance()->getConnection();
                $uStmt = $db->prepare("SELECT username FROM users WHERE id = :id LIMIT 1");
                $uStmt->execute([':id' => $userId]);
                $username = $uStmt->fetchColumn() ?: null;
            } catch (\Throwable $e) {}
        }
        
        // Retrieve client IP with reverse-proxy support
        $ipAddress = '127.0.0.1';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ipAddress = trim($parts[0]);
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = trim($_SERVER['HTTP_CLIENT_IP']);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = trim($_SERVER['REMOTE_ADDR']);
        }
        $ipAddress = substr($ipAddress, 0, 45);

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
        } catch (\Throwable $e) {
            // Silently ignore log errors in production to avoid crashing, but log to error_log
            error_log("Failed to write audit log: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve all audit logs based on filters.
     * 
     * @param array $filters Filter terms (date_from, date_to, user_id, role, action, module, limit)
     * @return array List of matching audit logs
     */
    public function allFiltered($filters = []) {
        $sql = "SELECT a.*, u.role AS user_role, u.id AS user_account_id, 
                       TRIM(CONCAT_WS(' ', u.first_name, u.last_name)) AS user_fullname 
                FROM audit_logs a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE 1=1";
        $params = [];

        // Sargable datetime range queries to utilize idx_audit_logs_search index
        if (!empty($filters['date_from'])) {
            $sql .= " AND a.created_at >= :date_from";
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND a.created_at <= :date_to";
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND a.user_id = :user_id";
            $params['user_id'] = (int)$filters['user_id'];
        }

        if (!empty($filters['role'])) {
            $sql .= " AND u.role = :role";
            $params['role'] = $filters['role'];
        }

        if (!empty($filters['action'])) {
            $sql .= " AND a.action = :action";
            $params['action'] = $filters['action'];
        }

        if (!empty($filters['module'])) {
            $sql .= " AND a.module = :module";
            $params['module'] = $filters['module'];
        }

        $sql .= " ORDER BY a.created_at DESC";

        // Apply safety record cap when no explicit date range or unlimited export flag is given
        $limit = isset($filters['limit']) ? (int)$filters['limit'] : (empty($filters['date_from']) && empty($filters['date_to']) && empty($filters['action']) && empty($filters['user_id']) && empty($filters['module']) ? 500 : 0);
        if ($limit > 0) {
            $sql .= " LIMIT " . $limit;
        }

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
        $stmt = $this->db->query("SELECT DISTINCT action FROM audit_logs WHERE action IS NOT NULL AND action != '' ORDER BY action ASC");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Fetch list of unique modules for filter dropdown.
     * 
     * @return array
     */
    public function getUniqueModules() {
        $stmt = $this->db->query("SELECT DISTINCT module FROM audit_logs WHERE module IS NOT NULL AND module != '' ORDER BY module ASC");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }
}
