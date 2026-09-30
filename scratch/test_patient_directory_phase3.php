<?php
// Test Suite for Patient Directory Phase 3: Fast-Track Clinic Intake, Action Ergonomics & Master CSV Export

echo "=== PHASE 3 TEST SUITE: PATIENT DIRECTORY INTAKE & CSV EXPORT ===\n\n";

$passed = 0;
$failed = 0;

function assertCondition($cond, $desc) {
    global $passed, $failed;
    if ($cond) {
        echo " [PASS] {$desc}\n";
        $passed++;
    } else {
        echo " [FAIL] {$desc}\n";
        $failed++;
    }
}

// 1. Syntax checks
$phpLint1 = shell_exec('php -l app/Controllers/PatientController.php');
assertCondition(strpos($phpLint1, 'No syntax errors detected') !== false, "Syntax check: app/Controllers/PatientController.php");

$phpLint2 = shell_exec('php -l config/routes.php');
assertCondition(strpos($phpLint2, 'No syntax errors detected') !== false, "Syntax check: config/routes.php");

$phpLint3 = shell_exec('php -l app/Views/patients/index.php');
assertCondition(strpos($phpLint3, 'No syntax errors detected') !== false, "Syntax check: app/Views/patients/index.php");

// 2. Route registration check
$routesContent = file_get_contents('config/routes.php');
$exportPos = strpos($routesContent, "'/patients/export'");
$showPos = strpos($routesContent, "'/patients/{id}'");
assertCondition($exportPos !== false, "Route /patients/export is registered");
assertCondition($exportPos < $showPos, "Route /patients/export is registered BEFORE /patients/{id}");

// 3. View check: Header Export Button & Action Dropdown
$viewContent = file_get_contents('app/Views/patients/index.php');
assertCondition(strpos($viewContent, '/patients/export?') !== false, "Header contains Export CSV button linking to /patients/export with active filters");
assertCondition(strpos($viewContent, 'data-bs-toggle="dropdown"') !== false, "Action column contains dropdown toggle button");
assertCondition(strpos($viewContent, '/queue?patient_id=') !== false, "Dropdown contains direct Check In to Queue shortcut");
assertCondition(strpos($viewContent, '/appointments/create?patient_id=') !== false, "Dropdown contains direct Book Appointment shortcut");
assertCondition(strpos($viewContent, 'Check In to Queue') !== false, "Dropdown item text 'Check In to Queue' present");
assertCondition(strpos($viewContent, 'Book Appointment') !== false, "Dropdown item text 'Book Appointment' present");

// 4. Controller check: export() method
$controllerContent = file_get_contents('app/Controllers/PatientController.php');
assertCondition(strpos($controllerContent, 'function export()') !== false, "PatientController contains export() method");
assertCondition(strpos($controllerContent, 'BARANGAY SINALHAN HEALTH CENTER - CITY HEALTH OFFICE OF SANTA ROSA') !== false, "export() writes official facility preamble");
assertCondition(strpos($controllerContent, 'Master Patient Directory & Census') !== false, "export() writes report title");
assertCondition(strpos($controllerContent, 'chr(0xEF) . chr(0xBB) . chr(0xBF)') !== false, "export() outputs UTF-8 BOM for Microsoft Excel");
assertCondition(strpos($controllerContent, "'Full Address'") !== false, "export() header uses 'Full Address'");
assertCondition(strpos($controllerContent, "'PhilHealth Status'") !== false, "export() header includes 'PhilHealth Status'");

// 5. Zero traces of barangay field in export columns or patient index
assertCondition(strpos($controllerContent, "'Barangay'") === false, "Zero traces of separate 'Barangay' column in CSV export");
assertCondition(stripos($viewContent, 'barangay') === false, "Zero traces of 'barangay' field in patient index view");

// 6. Test CSV Generation Execution via sub-process
$exportTestScript = __DIR__ . '/_run_export_test.php';
file_put_contents($exportTestScript, <<<'PHP'
<?php
$_SESSION = [
    'user_id' => 1,
    'user_fullname' => 'Test Medical Officer',
    'user_role' => 'Doctor'
];
$_GET = [];

require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Models/Patient.php';
require_once __DIR__ . '/../app/Models/VitalSigns.php';
require_once __DIR__ . '/../app/Models/AuditLog.php';
require_once __DIR__ . '/../app/Controllers/PatientController.php';

$controller = new App\Controllers\PatientController();
// Controller calls exit, so we invoke it directly
$controller->export();
PHP
);

$csvOutput = shell_exec('php ' . escapeshellarg($exportTestScript));
@unlink($exportTestScript);

assertCondition(!empty($csvOutput), "CSV export executed and produced output");
assertCondition(strpos($csvOutput, 'BARANGAY SINALHAN HEALTH CENTER') !== false, "CSV output contains official health center header");
assertCondition(strpos($csvOutput, 'Master Patient Directory & Census') !== false, "CSV output contains report title");
assertCondition(strpos($csvOutput, 'Total Records Exported') !== false, "CSV output contains records exported summary");
assertCondition(strpos($csvOutput, 'Patient No.') !== false && strpos($csvOutput, 'Full Address') !== false, "CSV output contains valid column header row");

// Count data rows (excluding preamble)
$lines = explode("\n", trim($csvOutput));
assertCondition(count($lines) > 10, "CSV output contains metadata and data rows (total lines: " . count($lines) . ")");

echo "\n--------------------------------------------------\n";
echo "Phase 3 Automated Verification Results:\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "--------------------------------------------------\n";

if ($failed > 0) {
    exit(1);
}
