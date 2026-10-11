<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use PDO;

class SettingController extends Controller {
    protected $settingModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (!is_admin()) {
            $_SESSION['error_message'] = 'Access Denied: System Settings are restricted to Administrators.';
            $this->redirect('/dashboard');
            exit;
        }

        $this->settingModel = new SystemSetting();
    }

    /**
     * Display settings hub.
     */
    public function index() {
        $groupedSettings = $this->settingModel->getAllGrouped();

        // System information and server environment diagnostics
        $db = \App\Core\Database::getInstance()->getConnection();
        $dbVersion = 'Unknown';
        try {
            $dbVersion = $db->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch (\Throwable $e) {}

        $activeTab = $_GET['tab'] ?? 'facility';
        if (!in_array($activeTab, ['facility', 'scheduling', 'security', 'system'], true)) {
            $activeTab = 'facility';
        }

        $systemInfo = [
            'php_version' => PHP_VERSION,
            'db_version' => $dbVersion,
            'server_os' => PHP_OS_FAMILY . ' (' . php_uname('s') . ' ' . php_uname('r') . ')',
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Apache',
            'database_name' => 'sinalhan_hc_system',
            'timezone' => date_default_timezone_get() . ' (UTC' . date('P') . ')',
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'app_environment' => config('app.env', 'production'),
            'current_time' => date('Y-m-d h:i:s A')
        ];

        return $this->view('settings/index', [
            'title' => 'System Settings',
            'breadcrumbs' => ['System Settings' => null],
            'settings' => $groupedSettings,
            'systemInfo' => $systemInfo,
            'activeTab' => $activeTab
        ]);
    }

    /**
     * Process updates to system settings by category.
     */
    public function update() {
        if (!verify_csrf_token()) {
            $_SESSION['error_message'] = 'Security validation failed (CSRF token mismatch). Please try again.';
            $this->redirect('/settings');
            return;
        }

        $category = trim($_POST['category'] ?? '');
        $userId = $_SESSION['user_id'] ?? null;
        $toSave = [];

        switch ($category) {
            case 'facility':
                $name = trim($_POST['facility_name'] ?? '');
                if (empty($name)) {
                    $_SESSION['error_message'] = 'Facility Name is required and cannot be empty.';
                    $this->redirect('/settings?tab=facility');
                    return;
                }

                $toSave = [
                    'facility_name' => $name,
                    'facility_address' => trim($_POST['facility_address'] ?? ''),
                    'facility_contact' => trim($_POST['facility_contact'] ?? ''),
                    'facility_email' => trim($_POST['facility_email'] ?? ''),
                    'facility_mho_name' => trim($_POST['facility_mho_name'] ?? ''),
                    'facility_phic_code' => trim($_POST['facility_phic_code'] ?? ''),
                    'facility_doh_code' => trim($_POST['facility_doh_code'] ?? '')
                ];
                break;

            case 'scheduling':
                $capacity = (int)($_POST['appointment_slot_capacity'] ?? 2);
                if ($capacity < 1 || $capacity > 20) {
                    $_SESSION['error_message'] = 'Appointment slot capacity must be between 1 and 20 patients per slot.';
                    $this->redirect('/settings?tab=scheduling');
                    return;
                }

                $toSave = [
                    'appointment_slot_capacity' => (string)$capacity,
                    'morning_opd_start' => trim($_POST['morning_opd_start'] ?? '08:30'),
                    'morning_opd_end' => trim($_POST['morning_opd_end'] ?? '12:00'),
                    'afternoon_opd_start' => trim($_POST['afternoon_opd_start'] ?? '13:00'),
                    'afternoon_opd_end' => trim($_POST['afternoon_opd_end'] ?? '17:00')
                ];
                break;

            case 'security':
                if (!is_super_admin()) {
                    $_SESSION['error_message'] = 'Access Denied: Only Super Administrators can alter security and session policies.';
                    $this->redirect('/settings?tab=security');
                    return;
                }

                $timeout = (int)($_POST['session_idle_timeout'] ?? 7200);
                $allowedTimeouts = [900, 1800, 3600, 7200, 14400];
                if (!in_array($timeout, $allowedTimeouts, true)) {
                    $timeout = 7200;
                }

                $maxAttempts = (int)($_POST['max_login_attempts'] ?? 5);
                $allowedAttempts = [3, 5, 10];
                if (!in_array($maxAttempts, $allowedAttempts, true)) {
                    $maxAttempts = 5;
                }

                $toSave = [
                    'session_idle_timeout' => (string)$timeout,
                    'max_login_attempts' => (string)$maxAttempts
                ];
                break;

            default:
                $_SESSION['error_message'] = 'Invalid settings category specified.';
                $this->redirect('/settings');
                return;
        }

        $success = $this->settingModel->updateBatch($toSave, $category, $userId);

        if ($success) {
            AuditLog::log(
                'SETTINGS_UPDATED',
                'Settings',
                "Updated {$category} settings: " . implode(', ', array_keys($toSave))
            );
            $_SESSION['success_message'] = ucfirst($category) . ' settings saved successfully.';
        } else {
            $_SESSION['error_message'] = 'Failed to save settings. Please try again.';
        }

        $this->redirect('/settings?tab=' . urlencode($category));
    }
}
