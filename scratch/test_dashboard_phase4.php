<?php
// Test Phase 4 Dashboard: Recent Clinical Encounters & Comprehensive Rendering

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/dashboard';
$_SESSION['user_id'] = 1;
$_SESSION['user'] = [
    'id' => 1,
    'first_name' => 'System',
    'last_name' => 'Administrator',
    'role' => 'Super Admin',
    'email' => 'admin@sinalhan.gov.ph'
];

require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Models/Patient.php';
require_once __DIR__ . '/../app/Models/Appointment.php';
require_once __DIR__ . '/../app/Models/QueueEntry.php';
require_once __DIR__ . '/../app/Models/AuditLog.php';
require_once __DIR__ . '/../app/Controllers/DashboardController.php';

$controller = new App\Controllers\DashboardController();

// Use reflection or output buffering to capture controller index()
ob_start();
try {
    $controller->index();
    $output = ob_get_clean();
} catch (\Throwable $e) {
    ob_end_clean();
    echo "FATAL ERROR during DashboardController::index(): " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

$topbarContent = file_get_contents(__DIR__ . '/../app/Views/layout/topbar.php');

$tests = [
    'Recent Clinical Encounters header present' => strpos($output, 'Recent Clinical Encounters') !== false,
    'Patient Records link present' => strpos($output, 'title="Open Full Patient Records Directory"') !== false,
    'Encounter table headers present' => strpos($output, 'Clinical Assessment &amp; Complaint') !== false && strpos($output, 'Attending Clinician') !== false,
    'Encounter view chart action present' => strpos($output, 'View Chart') !== false,
    'No badged patient IDs in encounters' => !preg_match('/<span[^>]*class="[^"]*badge[^"]*"[^>]*>\s*P-\d{4}-\d+\s*<\/span>/', $output),
    'De-badged monospace patient IDs present' => preg_match('/class="font-monospace[^"]*"[^>]*>\s*P-\d{4}-\d+/i', $output),
    'Clinician attribution displayed' => strpos($output, 'fw-medium text-dark small') !== false || strpos($output, 'Health Center Staff') !== false,
    'Maternal Delivery Radar still intact' => strpos($output, 'Maternal Delivery Radar') !== false,
    'Child Health EPI widget still intact' => strpos($output, 'Child Health &amp; Immunization (EPI)') !== false,
    // Header should not contain action buttons
    'No 4 action buttons in dashboard header' => strpos($output, 'id="dashboardQuickActions"') === false && strpos($output, 'Book Appointment') === false && strpos($output, 'View Reports') === false,
    'Live PST topbar date & clock in place' => strpos($topbarContent, 'topbarClock') !== false && strpos($topbarContent, 'topbarLiveDate') !== false,
    'No Super Admin role badge in dashboard header' => strpos($output, 'badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1') === false,
    'No PHP fatal errors or warnings' => strpos($output, 'Fatal error') === false && strpos($output, 'Notice:') === false && strpos($output, 'Warning:') === false
];

$allPassed = true;
echo "=== Dashboard Phase 4 Test Suite ===\n";
foreach ($tests as $desc => $passed) {
    if ($passed) {
        echo "[PASS] $desc\n";
    } else {
        echo "[FAIL] $desc\n";
        $allPassed = false;
    }
}

if ($allPassed) {
    echo "\nAll Phase 4 Dashboard tests PASSED successfully!\n";
    exit(0);
} else {
    echo "\nSome Phase 4 tests FAILED.\n";
    exit(1);
}
