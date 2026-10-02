<?php
/**
 * Test patient restore redirect target
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'super_admin';
$_SESSION['role'] = 'super_admin';
$_SESSION['user'] = [
    'id' => 1,
    'first_name' => 'System',
    'last_name' => 'Administrator',
    'role' => 'super_admin'
];

require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();

$db = \App\Core\Database::getInstance()->getConnection();

// Ensure patient 97 is soft-deleted
$db->prepare("UPDATE patients SET deleted_at = CURRENT_TIMESTAMP WHERE id = 97")->execute();

register_shutdown_function(function() {
    $headers = headers_list();
    $redirectHeader = '';
    foreach ($headers as $h) {
        if (stripos($h, 'Location:') === 0) {
            $redirectHeader = $h;
        }
    }
    $hasSuccess = isset($_SESSION['success_message']) && strpos($_SESSION['success_message'], 'restored successfully') !== false;

    if (strpos($redirectHeader, '/archive?tab=patients') !== false && $hasSuccess) {
        echo "PATIENT_RESTORE_REDIRECT_SUCCESS\n";
    } else {
        echo "PATIENT_RESTORE_REDIRECT_FAILED: header={$redirectHeader}, success=" . ($_SESSION['success_message'] ?? 'none') . "\n";
    }
});

$patientController = new \App\Controllers\PatientController();
$patientController->restore(97);
