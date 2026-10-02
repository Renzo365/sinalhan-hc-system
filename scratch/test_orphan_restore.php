<?php
/**
 * Test orphan consultation restore behavior
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

// Ensure patient 97 and consultation 181 are marked soft-deleted
$db->prepare("UPDATE patients SET deleted_at = CURRENT_TIMESTAMP WHERE id = 97")->execute();
$db->prepare("UPDATE consultations SET deleted_at = CURRENT_TIMESTAMP WHERE id = 181")->execute();

register_shutdown_function(function() {
    global $db;
    $hasError = isset($_SESSION['error_message']) && strpos($_SESSION['error_message'], 'parent patient') !== false;
    $check181 = $db->query("SELECT deleted_at FROM consultations WHERE id = 181")->fetch(\PDO::FETCH_ASSOC);
    $stillDeleted = !empty($check181['deleted_at']);

    if ($hasError && $stillDeleted) {
        echo "ORPHAN_BLOCKED_SUCCESS\n";
    } else {
        echo "ORPHAN_CHECK_FAILED: " . ($_SESSION['error_message'] ?? 'none') . "\n";
    }
});

$consultationController = new \App\Controllers\ConsultationController();
$consultationController->restore(181);
