<?php
/**
 * Automated Verification Suite for Phase 1: Controller Isolation & Backend Bug Remediation
 */

$_SERVER['REQUEST_METHOD'] = 'GET';
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

$passCount = 0;
$failCount = 0;

function assertCondition($testName, $condition, $details = '') {
    global $passCount, $failCount;
    if ($condition) {
        echo " [PASS] $testName\n";
        $passCount++;
    } else {
        echo " [FAIL] $testName" . ($details ? " - $details" : "") . "\n";
        $failCount++;
    }
}

echo "=== STARTING ARCHIVE PHASE 1 VERIFICATION SUITE ===\n\n";

// 1. Syntax checks
$phpLint1 = shell_exec('php -l app/Controllers/PatientController.php');
assertCondition("Syntax: PatientController.php", strpos($phpLint1, 'No syntax errors detected') !== false);

$phpLint2 = shell_exec('php -l app/Controllers/ConsultationController.php');
assertCondition("Syntax: ConsultationController.php", strpos($phpLint2, 'No syntax errors detected') !== false);

$phpLint3 = shell_exec('php -l app/Models/User.php');
assertCondition("Syntax: User.php", strpos($phpLint3, 'No syntax errors detected') !== false);

$phpLint4 = shell_exec('php -l app/Views/archive/patients.php');
assertCondition("Syntax: archive/patients.php", strpos($phpLint4, 'No syntax errors detected') !== false);

// 2. Test User::allArchived() role filter
$userModel = new \App\Models\User();
$adminUsers = $userModel->allArchived(['role' => 'admin']);
assertCondition("User::allArchived(['role' => 'admin']) returns admin", count($adminUsers) >= 1 && $adminUsers[0]['role'] === 'admin');

$staffUsers = $userModel->allArchived(['role' => 'staff']);
assertCondition("User::allArchived(['role' => 'staff']) excludes admins", count($staffUsers) === 0);

// 3. Test User::allArchived() date range filters
$dateFiltered = $userModel->allArchived(['date_from' => '2026-01-01', 'date_to' => '2026-12-31']);
assertCondition("User::allArchived() date range filtering executes successfully", is_array($dateFiltered) && count($dateFiltered) >= 1);

$futureDateFiltered = $userModel->allArchived(['date_from' => '2028-01-01']);
assertCondition("User::allArchived() future date filtering returns empty", count($futureDateFiltered) === 0);

// 4. Test Cross-Tab Filter Decoupling in PatientController::archivedIndex()
$_GET['tab'] = 'patients';
$_GET['search'] = 'Villanueva';
$_GET['date_from'] = '';
$_GET['date_to'] = '';
$_GET['role'] = '';

$patientController = new \App\Controllers\PatientController();

ob_start();
$patientController->archivedIndex();
$outputPatientsTab = ob_get_clean();

// Check that tabCounts are intact
assertCondition("Decoupled tab pill for Archived Consultations retains non-zero count", strpos($outputPatientsTab, 'Archived Consultations') !== false && preg_match('/Archived Consultations\s*<span[^>]*>\s*([1-9]\d*)\s*<\/span>/', $outputPatientsTab));
assertCondition("Decoupled tab pill for Archived Staff Accounts retains non-zero count", strpos($outputPatientsTab, 'Archived Staff Accounts') !== false && preg_match('/Archived Staff Accounts\s*<span[^>]*>\s*([1-9]\d*)\s*<\/span>/', $outputPatientsTab));

// 5. Test Staff Accounts role filtering through PatientController
$_GET['tab'] = 'users';
$_GET['search'] = '';
$_GET['role'] = 'staff';

ob_start();
$patientController->archivedIndex();
$outputStaffTab = ob_get_clean();
assertCondition("Archived Staff tab with role=staff shows empty message", strpos($outputStaffTab, 'No archived staff accounts match the criteria') !== false);

$_GET['role'] = 'admin';
ob_start();
$patientController->archivedIndex();
$outputAdminTab = ob_get_clean();
assertCondition("Archived Staff tab with role=admin shows jdoe account", strpos($outputAdminTab, 'jdoe') !== false);

// 6. Test Orphan Consultation Restoration Guard via sub-process
$orphanOutput = shell_exec('php scratch/test_orphan_restore.php 2>&1');
assertCondition("Orphan Consultation Guard blocks restoration when parent patient is deleted", strpos($orphanOutput, 'ORPHAN_BLOCKED_SUCCESS') !== false);

// 7. Verify Redirect Targets in Controller code
$patientCtrlCode = file_get_contents(__DIR__ . '/../app/Controllers/PatientController.php');
assertCondition("PatientController::restore redirects to /archive?tab=patients", strpos($patientCtrlCode, '$this->redirect(\'/archive?tab=patients\');') !== false);

$consultationCtrlCode = file_get_contents(__DIR__ . '/../app/Controllers/ConsultationController.php');
assertCondition("ConsultationController::restore redirects to /archive?tab=consultations", strpos($consultationCtrlCode, '$this->redirect(\'/archive?tab=consultations\');') !== false);

echo "\n--------------------------------------------------\n";
echo "Phase 1 Automated Verification Results:\n";
echo "Passed: $passCount\n";
echo "Failed: $failCount\n";
echo "--------------------------------------------------\n";

if ($failCount === 0) {
    echo "ALL PHASE 1 TESTS PASSED SUCCESSFULLY!\n";
    exit(0);
} else {
    echo "SOME TESTS FAILED!\n";
    exit(1);
}
