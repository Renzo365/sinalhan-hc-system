<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AuditLog;
use PDO;

class AuditLogController extends Controller {
    protected $auditModel;

    public function __construct() {
        $this->auditModel = new AuditLog();
    }

    /**
     * Display filtered audit logs list.
     */
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $filters = [
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to' => trim($_GET['date_to'] ?? ''),
            'user_id' => trim($_GET['user_id'] ?? ''),
            'role' => trim($_GET['role'] ?? ''),
            'action' => trim($_GET['action'] ?? '')
        ];

        // Fetch logs
        $logs = $this->auditModel->allFiltered($filters);

        // Fetch all active users for dropdown
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT id, username, first_name, last_name, role FROM users WHERE status = 'active' ORDER BY last_name ASC");
            $users = $stmt->fetchAll() ?: [];
        } catch (\Exception $e) {
            $users = [];
        }

        // Fetch unique action keys for dropdown
        $actions = $this->auditModel->getUniqueActions();

        $this->view('audit-logs/index', [
            'logs' => $logs,
            'filters' => $filters,
            'users' => $users,
            'actions' => $actions
        ]);
    }
}
