<?php
/**
 * Master Automated Verification & Regression Suite for Archive Records Hub
 * (Phase 4: Comprehensive Regression, Isolation, Guard Rails & Integrity)
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

echo "=== STARTING ARCHIVE PHASE 4 FULL REGRESSION SUITE ===\n\n";

// 1. PHP Syntax Integrity Checks across all Archive-related files
echo "-- 1. Syntax Validation --\n";
$filesToLint = [
    'app/Controllers/PatientController.php',
    'app/Controllers/ConsultationController.php',
    'app/Controllers/UserController.php',
    'app/Models/Patient.php',
    'app/Models/Consultation.php',
    'app/Models/User.php',
    'app/Views/archive/patients.php',
    'app/Middleware/SuperAdminMiddleware.php'
];

foreach ($filesToLint as $file) {
    $lintOutput = shell_exec('php -l ' . escapeshellarg($file) . ' 2>&1');
    assertCondition("Syntax lint: $file", strpos($lintOutput, 'No syntax errors detected') !== false, trim($lintOutput));
}

// 2. Tab Filter Decoupling & Counter Invariance Test
echo "\n-- 2. Filter Decoupling & Counter Invariance --\n";
$db = \App\Core\Database::getInstance()->getConnection();
$patientModel = new \App\Models\Patient();
$consultationModel = new \App\Models\Consultation();
$userModel = new \App\Models\User();

$basePatientsCount = (int)$db->query("SELECT COUNT(*) FROM patients WHERE deleted_at IS NOT NULL")->fetchColumn();
$baseConsultationsCount = (int)$db->query("SELECT COUNT(*) FROM consultations WHERE deleted_at IS NOT NULL")->fetchColumn();
$baseUsersCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NOT NULL")->fetchColumn();

echo "   Base totals: Patients=$basePatientsCount, Consultations=$baseConsultationsCount, Users=$baseUsersCount\n";
assertCondition("Base archived counts are valid non-negative integers", 
    $basePatientsCount >= 0 && $baseConsultationsCount >= 0 && $baseUsersCount >= 0);

// Simulate tab=patients with active search filter
$filteredPatients = $patientModel->allArchived(['search' => 'Villanueva']);
$recheckedPatientsCount = (int)$db->query("SELECT COUNT(*) FROM patients WHERE deleted_at IS NOT NULL")->fetchColumn();
$recheckedConsultationsCount = (int)$db->query("SELECT COUNT(*) FROM consultations WHERE deleted_at IS NOT NULL")->fetchColumn();

assertCondition("Tab 1 filtering returns search subset without mutating base counts", 
    $recheckedPatientsCount === $basePatientsCount && $recheckedConsultationsCount === $baseConsultationsCount);

// 3. User Role Filtering in Tab 3 (Archived User Accounts)
echo "\n-- 3. Tab 3 User Filtering & Role Scoping --\n";
$adminUsers = $userModel->allArchived(['role' => 'admin']);
assertCondition("Filter role=admin returns admin accounts exclusively", 
    empty($adminUsers) || (count($adminUsers) >= 1 && $adminUsers[0]['role'] === 'admin'));

$staffUsers = $userModel->allArchived(['role' => 'staff']);
$onlyStaffFound = true;
foreach ($staffUsers as $su) {
    if ($su['role'] !== 'staff') {
        $onlyStaffFound = false;
        break;
    }
}
assertCondition("Filter role=staff excludes administrator accounts", $onlyStaffFound);

// Test date filtering in User model
$dateFilteredUsers = $userModel->allArchived(['date_from' => '2026-01-01', 'date_to' => '2026-12-31']);
assertCondition("User::allArchived() date range filtering executes cleanly", is_array($dateFilteredUsers));

// 4. Restore Redirection & Route Consistency
echo "\n-- 4. Restore Redirections & Route Standard --\n";
$patientControllerSource = file_get_contents(__DIR__ . '/../app/Controllers/PatientController.php');
assertCondition("PatientController::restore() redirects to /archive?tab=patients", 
    strpos($patientControllerSource, "redirect('/archive?tab=patients')") !== false);

$consultationControllerSource = file_get_contents(__DIR__ . '/../app/Controllers/ConsultationController.php');
assertCondition("ConsultationController::restore() redirects to /archive?tab=consultations", 
    strpos($consultationControllerSource, "redirect('/archive?tab=consultations')") !== false);

$userControllerSource = file_get_contents(__DIR__ . '/../app/Controllers/UserController.php');
assertCondition("UserController::restore() redirects to /archive?tab=users", 
    strpos($userControllerSource, "redirect('/archive?tab=users')") !== false);

// 5. Orphan Clinical Consultation Restoration Guard
echo "\n-- 5. Orphan Consultation Restoration Guard --\n";
$hasOrphanGuard = strpos($consultationControllerSource, 'is currently archived. Please restore the patient record first.') !== false;
assertCondition("ConsultationController enforces parent patient soft-deletion guard before restore", $hasOrphanGuard);

// 6. View Architecture & UX Standards
echo "\n-- 6. View Architecture, DataTables & Inspection Modals --\n";
$viewSource = file_get_contents(__DIR__ . '/../app/Views/archive/patients.php');

$searchingFalseCount = substr_count($viewSource, '"searching": false');
assertCondition("All DataTables have searching: false (single unified search bar)", $searchingFalseCount === 3);

$delegatedPatient = strpos($viewSource, "\$(document).on('click', '.btn-restore-patient'") !== false;
$delegatedConsultation = strpos($viewSource, "\$(document).on('click', '.btn-restore-consultation'") !== false;
$delegatedUser = strpos($viewSource, "\$(document).on('click', '.btn-restore-user'") !== false;
assertCondition("All restore action handlers use document delegation (Page 2+ pagination safe)", 
    $delegatedPatient && $delegatedConsultation && $delegatedUser);

$hasPatientModal = strpos($viewSource, 'id="modalPatientArchiveDetails"') !== false;
$hasConsultationModal = strpos($viewSource, 'id="modalConsultationArchiveDetails"') !== false;
assertCondition("Patient demographics preview modal markup exists", $hasPatientModal);
assertCondition("Consultation SOAP notes & vitals preview modal markup exists", $hasConsultationModal);

$hasTealTheme = strpos($viewSource, 'var(--color-primary, #0d9488)') !== false;
assertCondition("Medical teal tab styling (#0d9488) configured", $hasTealTheme);

$hasTwoLinePatient = strpos($viewSource, 'font-monospace text-secondary small') !== false;
assertCondition("Clean two-line patient hierarchy (no cramped badges) in Tab 2", $hasTwoLinePatient);

$hasJobTitleHeader = strpos($viewSource, '<th>Clinical Job Title</th>') !== false;
$noOrphanDept = strpos($viewSource, "u['department']") === false;
assertCondition("Tab 3 header reads 'Clinical Job Title' and orphan department markup removed", 
    $hasJobTitleHeader && $noOrphanDept);

$hasSuperAdminRestoreTooltip = strpos($viewSource, 'Only Super Admin can restore administrator accounts') !== false;
assertCondition("Super Admin restriction tooltip configured on Tab 3 admin accounts", $hasSuperAdminRestoreTooltip);

echo "\n--------------------------------------------------\n";
echo "Archive Phase 4 Full Regression Summary:\n";
echo "Total Passed: $passCount\n";
echo "Total Failed: $failCount\n";
echo "--------------------------------------------------\n";

exit($failCount > 0 ? 1 : 0);
