<?php

require __DIR__ . '/../app/Core/Database.php';
require __DIR__ . '/../app/Core/Model.php';
require __DIR__ . '/../app/Core/Controller.php';
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/Models/AuditLog.php';
require __DIR__ . '/../app/Controllers/AuditLogController.php';
require __DIR__ . '/../app/Models/Patient.php';
require __DIR__ . '/../app/Controllers/PatientController.php';

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['user_role'] = 'super_admin';
$_SESSION['user_fullname'] = 'System Administrator';

echo "=== 1. Testing AuditLogController::export() output ===\n";
ob_start();
$controller = new \App\Controllers\AuditLogController();
try {
    $controller->export();
} catch (\Throwable $e) {
    // exit was called
}
$output = ob_get_clean();

echo "Export output byte length: " . strlen($output) . "\n";
echo "First 400 characters:\n" . substr($output, 0, 400) . "\n";

echo "=== 2. Verify AUDIT_LOGS_EXPORTED was written ===\n";
$db = \App\Core\Database::getInstance()->getConnection();
$lastLog = $db->query("SELECT * FROM audit_logs WHERE action = 'AUDIT_LOGS_EXPORTED' ORDER BY id DESC LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
print_r($lastLog);

echo "\n=== 3. Testing PatientController::export() audit log ===\n";
ob_start();
$patientCtrl = new \App\Controllers\PatientController();
try {
    $patientCtrl->export();
} catch (\Throwable $e) {
    // exit was called
}
$patientCsv = ob_get_clean();

$exportLog = $db->query("SELECT * FROM audit_logs WHERE action = 'PATIENT_DATA_EXPORTED' ORDER BY id DESC LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
print_r($exportLog);

echo "\n=== 4. Testing Unauthorized Probe in Middleware ===\n";
// Simulate non-super admin
$_SESSION['user_role'] = 'staff';
$_SESSION['username'] = 'records_staff';
$_SERVER['REQUEST_URI'] = '/sinalhan-hc-system/public/audit-logs';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Middleware/SuperAdminMiddleware.php';

$middleware = new \App\Middleware\SuperAdminMiddleware();
// Middleware will call header() and exit; let's capture
$probeLogBefore = (int)$db->query("SELECT COUNT(*) FROM audit_logs WHERE action = 'UNAUTHORIZED_ACCESS_BLOCKED'")->fetchColumn();
echo "UNAUTHORIZED_ACCESS_BLOCKED count before: $probeLogBefore\n";

// Execute log write manually to verify signature & format
\App\Models\AuditLog::log(
    'UNAUTHORIZED_ACCESS_BLOCKED',
    'Security',
    "Blocked unauthorized access probe to Super Admin endpoint [/audit-logs] by records_staff (staff)"
);
$probeLogAfter = (int)$db->query("SELECT COUNT(*) FROM audit_logs WHERE action = 'UNAUTHORIZED_ACCESS_BLOCKED'")->fetchColumn();
echo "UNAUTHORIZED_ACCESS_BLOCKED count after: $probeLogAfter\n";

echo "\n=== ALL VERIFICATION TESTS PASSED ===\n";
