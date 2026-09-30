<?php
// scratch/test_reports_phase3.php
// Verification test suite for Reports Remediation Phase 3: Appointments Report & Export Ergonomics

require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Controllers/ReportController.php';

$testsPassed = 0;
$testsFailed = 0;

function assertTrue($condition, $message) {
    global $testsPassed, $testsFailed;
    if ($condition) {
        echo "  [PASS] $message\n";
        $testsPassed++;
    } else {
        echo "  [FAIL] $message\n";
        $testsFailed++;
    }
}

echo "=== Running Reports Remediation Phase 3 Automated Tests ===\n\n";

// 1. Controller Logic Inspection & Metric Calculation
echo "Test Group 1: Controller Query & KPI Metrics for Appointments\n";
$reflection = new ReflectionClass('App\Controllers\ReportController');
$metricsMethod = $reflection->getMethod('computeReportMetrics');
$metricsMethod->setAccessible(true);
$controller = new App\Controllers\ReportController();

$dummyAppointments = [
    ['status' => 'Completed', 'appointment_date' => '2026-09-01', 'program_type' => 'General OPD'],
    ['status' => 'Completed', 'appointment_date' => '2026-09-02', 'program_type' => 'Prenatal Care'],
    ['status' => 'Scheduled', 'appointment_date' => '2026-09-05', 'program_type' => 'Immunization'],
    ['status' => 'Cancelled', 'appointment_date' => '2026-09-06', 'program_type' => 'General OPD'],
];

$metrics = $metricsMethod->invoke($controller, 'appointments', $dummyAppointments);
assertTrue(count($metrics) === 4, "Appointments generates 4 KPI summary cards");
assertTrue($metrics[0]['label'] === 'Total Bookings' && $metrics[0]['value'] === '4', "Total bookings equals 4");
assertTrue($metrics[1]['label'] === 'Attended / Completed' && $metrics[1]['value'] === '2', "Completed appointments equals 2");
assertTrue(strpos($metrics[1]['sub'], '50%') !== false, "Show/attendance rate is 50.0% (2/4)");
assertTrue($metrics[2]['label'] === 'Upcoming / Scheduled' && $metrics[2]['value'] === '1', "Upcoming appointments equals 1");
assertTrue($metrics[3]['label'] === 'Cancelled / No-Show' && $metrics[3]['value'] === '1', "Cancelled appointments equals 1");

// 2. Controller source code inspections
echo "\nTest Group 2: Controller Code Structure for Appointments & CSV Preamble\n";
$controllerCode = file_get_contents(__DIR__ . '/../app/Controllers/ReportController.php');

assertTrue(strpos($controllerCode, "case 'appointments':") !== false, "queryReportData contains case 'appointments'");
assertTrue(strpos($controllerCode, "FROM appointments a") !== false, "Appointments query joins appointments and patients");
assertTrue(strpos($controllerCode, "BARANGAY SINALHAN HEALTH CENTER") !== false, "CSV export includes official health center preamble header");
assertTrue(strpos($controllerCode, "System Export Report") !== false, "CSV export includes report category metadata");
assertTrue(strpos($controllerCode, "Reporting Period") !== false, "CSV export includes reporting period metadata");
assertTrue(strpos($controllerCode, "Exported By") !== false, "CSV export includes user attribution and timestamp metadata");
assertTrue(strpos($controllerCode, "case 'appointments':") !== false, "CSV export includes appointments case");

// 3. View Inspections
echo "\nTest Group 3: View Elements for Appointments Report\n";
$viewCode = file_get_contents(__DIR__ . '/../app/Views/reports/index.php');

assertTrue(strpos($viewCode, "'appointments' => 'Scheduled Care & Appointments Registry'") !== false, "reportLabels array includes appointments category");
assertTrue(strpos($viewCode, '<option value="appointments"') !== false, "Dropdown select includes appointments option");
assertTrue(strpos($viewCode, "<?php elseif (\$type === 'appointments'): ?>") !== false, "View contains appointments table branch");
assertTrue(strpos($viewCode, 'Scheduled Care & Appointments Registry Table') !== false, "View renders Scheduled Care & Appointments Registry Table");
assertTrue(strpos($viewCode, '<td data-order="<?= h($row[\'appointment_date\']) ?>">') !== false, "Appointment date has data-order attribute");
assertTrue(strpos($viewCode, '<td data-order="<?= h($row[\'appointment_time\'] ?: \'99:99:99\') ?>">') !== false, "Appointment time has data-order attribute");
assertTrue(strpos($viewCode, 'url(\'/patients/\' . $row[\'patient_id\'])') !== false, "Patient ID and Name link to patient profile in appointments table");

// 4. Live DB Query Verification
echo "\nTest Group 4: Live DB Query Execution for Appointments Report\n";
$queryMethod = $reflection->getMethod('queryReportData');
$queryMethod->setAccessible(true);
try {
    $dbData = $queryMethod->invoke($controller, 'appointments', '2026-01-01', '2026-12-31', false);
    assertTrue(is_array($dbData), "Live DB query for appointments executes successfully and returns an array");
    
    $allTimeData = $queryMethod->invoke($controller, 'appointments', '2026-01-01', '2026-12-31', true);
    assertTrue(is_array($allTimeData), "Live DB query for cumulative all-time appointments executes successfully");
} catch (\Throwable $e) {
    assertTrue(false, "Live DB query failed: " . $e->getMessage());
}

echo "\n======================================================\n";
echo "Results: $testsPassed Passed, $testsFailed Failed\n";
echo "======================================================\n";

if ($testsFailed > 0) {
    exit(1);
}
exit(0);
