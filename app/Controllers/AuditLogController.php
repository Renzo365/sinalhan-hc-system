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

        if (!is_super_admin()) {
            $_SESSION['error_message'] = 'Access Denied: Security audit logs are restricted to Super Administrators.';
            $this->redirect('/dashboard');
            exit;
        }

        $filters = [
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to' => trim($_GET['date_to'] ?? ''),
            'user_id' => trim($_GET['user_id'] ?? ''),
            'role' => trim($_GET['role'] ?? ''),
            'action' => trim($_GET['action'] ?? ''),
            'module' => trim($_GET['module'] ?? '')
        ];

        // Validate date range logic
        if (!empty($filters['date_from']) && !empty($filters['date_to']) && $filters['date_from'] > $filters['date_to']) {
            $_SESSION['error_message'] = 'Invalid date range: "Date From" (' . $filters['date_from'] . ') cannot be after "Date To" (' . $filters['date_to'] . '). Date filter was reset.';
            $filters['date_from'] = '';
            $filters['date_to'] = '';
        }

        // Fetch logs with safety default cap
        $logs = $this->auditModel->allFiltered($filters);

        // Fetch all active users + historical users who performed audited actions
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->query("
                SELECT id, username, first_name, last_name, role, status 
                FROM users 
                WHERE status = 'active' OR id IN (SELECT DISTINCT user_id FROM audit_logs WHERE user_id IS NOT NULL)
                ORDER BY status DESC, last_name ASC
            ");
            $users = $stmt->fetchAll() ?: [];
        } catch (\Exception $e) {
            $users = [];
        }

        // Fetch unique action keys and modules for dropdowns
        $actions = $this->auditModel->getUniqueActions();
        $modules = $this->auditModel->getUniqueModules();

        $this->view('audit-logs/index', [
            'logs' => $logs,
            'filters' => $filters,
            'users' => $users,
            'actions' => $actions,
            'modules' => $modules
        ]);
    }

    /**
     * Export filtered audit logs to CSV for regulatory compliance and external auditing.
     */
    public function export() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!is_super_admin()) {
            $_SESSION['error_message'] = 'Access Denied: Security audit logs export is restricted to Super Administrators.';
            $this->redirect('/dashboard');
            exit;
        }

        $filters = [
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to' => trim($_GET['date_to'] ?? ''),
            'user_id' => trim($_GET['user_id'] ?? ''),
            'role' => trim($_GET['role'] ?? ''),
            'action' => trim($_GET['action'] ?? ''),
            'module' => trim($_GET['module'] ?? ''),
            'limit' => 0 // Unlimited for official export
        ];

        if (!empty($filters['date_from']) && !empty($filters['date_to']) && $filters['date_from'] > $filters['date_to']) {
            $filters['date_from'] = '';
            $filters['date_to'] = '';
        }

        $logs = $this->auditModel->allFiltered($filters);

        $filename = 'audit_logs_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Write UTF-8 BOM for Microsoft Excel compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $generatedBy = $_SESSION['user_fullname'] ?? $_SESSION['username'] ?? 'Super Administrator';
        
        $scopeParts = [];
        if (!empty($filters['date_from'])) $scopeParts[] = 'From: ' . $filters['date_from'];
        if (!empty($filters['date_to'])) $scopeParts[] = 'To: ' . $filters['date_to'];
        if (!empty($filters['action'])) $scopeParts[] = 'Action: ' . $filters['action'];
        if (!empty($filters['module'])) $scopeParts[] = 'Module: ' . $filters['module'];
        if (!empty($filters['role'])) $scopeParts[] = 'Role: ' . $filters['role'];
        $filterSummary = !empty($scopeParts) ? implode(', ', $scopeParts) : 'Full Audit Trail Scope';

        // Official header metadata block
        fputcsv($output, ['BARANGAY SINALHAN HEALTH CENTER - CITY HEALTH OFFICE OF SANTA ROSA']);
        fputcsv($output, ['System Security & Compliance Audit Log Report']);
        fputcsv($output, ['Filter Scope', $filterSummary]);
        fputcsv($output, ['Total Audit Records', count($logs)]);
        fputcsv($output, ['Exported By', $generatedBy, 'Timestamp', date('Y-m-d H:i:s')]);
        fputcsv($output, []); // Blank separator row

        // CSV Column Headers
        fputcsv($output, [
            'Log ID',
            'Timestamp',
            'User Account',
            'Full Name',
            'User Role',
            'Action Type',
            'System Module',
            'IP Address',
            'Operation Details'
        ]);

        foreach ($logs as $log) {
            $formattedTime = !empty($log['created_at']) ? date('Y-m-d H:i:s', strtotime($log['created_at'])) : '-';
            $userRoleLabel = !empty($log['user_role']) ? ucfirst(str_replace('_', ' ', $log['user_role'])) : '-';
            $userName = !empty($log['username']) ? $log['username'] : (!empty($log['user_fullname']) ? $log['user_fullname'] : 'Unauthenticated');

            fputcsv($output, [
                $log['id'],
                $formattedTime,
                $userName,
                $log['user_fullname'] ?: '-',
                $userRoleLabel,
                $log['action'],
                $log['module'],
                $log['ip_address'],
                $log['details'] ?? ''
            ]);
        }

        fclose($output);

        // Record the export in audit logs
        AuditLog::log(
            'AUDIT_LOGS_EXPORTED',
            'Security',
            "Exported security audit logs to CSV ({$filterSummary}). Total entries: " . count($logs)
        );

        exit;
    }
}
