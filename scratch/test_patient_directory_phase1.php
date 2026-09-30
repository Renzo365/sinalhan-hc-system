<?php
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Models/Patient.php';
require_once __DIR__ . '/../app/Models/VitalSigns.php';
require_once __DIR__ . '/../app/Models/AuditLog.php';
require_once __DIR__ . '/../app/Controllers/PatientController.php';

use App\Models\Patient;
use App\Controllers\PatientController;

echo "=== TESTING PATIENT DIRECTORY PHASE 1: CENSUS METRICS & KPI CARDS ===\n\n";

$patientModel = new Patient();

// 1. Test getCensusMetrics
$metrics = $patientModel->getCensusMetrics();
echo "1. Census Metrics:\n";
print_r($metrics);

assert(isset($metrics['total_patients']), "total_patients missing");
assert(isset($metrics['seniors']), "seniors missing");
assert(isset($metrics['under5']), "under5 missing");
assert(isset($metrics['phic_covered']), "phic_covered missing");
assert(isset($metrics['households']), "households missing");
assert($metrics['total_patients'] >= 0, "total_patients should be >= 0");

echo "✓ Census Metrics aggregation works!\n\n";

// 2. Test Filters
echo "2. Testing Filter Presets:\n";
$all = $patientModel->allActive();
echo "- All Patients: " . count($all) . "\n";

$seniors = $patientModel->allActive(['age_group' => 'senior']);
echo "- Seniors (60+): " . count($seniors) . " (matches census metrics seniors: {$metrics['seniors']})\n";
assert(count($seniors) === $metrics['seniors'], "Senior filter count should match census metrics");

$under5 = $patientModel->allActive(['age_group' => 'under5']);
echo "- Under-5: " . count($under5) . " (matches census metrics under-5: {$metrics['under5']})\n";
assert(count($under5) === $metrics['under5'], "Under-5 filter count should match census metrics");

$phicCovered = $patientModel->allActive(['phic_status' => 'covered']);
echo "- PhilHealth Covered: " . count($phicCovered) . " (matches census metrics phic_covered: {$metrics['phic_covered']})\n";
assert(count($phicCovered) === $metrics['phic_covered'], "PhilHealth covered filter count should match census metrics");

// 3. Test View Render via output buffering
echo "\n3. Testing View Rendering:\n";
$_GET = []; // standard view
ob_start();
// Mock session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'Nurse';
$_SESSION['user_name'] = 'Test Nurse';

// Include view with mock data
$patients = $all;
$filters = ['search' => '', 'sex' => '', 'age_group' => '', 'phic_status' => ''];
$censusMetrics = $metrics;

// Capture view
include __DIR__ . '/../app/Views/patients/index.php';
$html = ob_get_clean();

assert(strpos($html, 'id="patientCensusCards"') !== false, "Demographic KPI Cards container should be present");
assert(strpos($html, 'Total Registered') !== false, "Total Registered card should be present");
assert(strpos($html, 'Senior Citizens (60+)') !== false, "Senior Citizens card should be present");
assert(strpos($html, 'Pediatric & Under-5') !== false, "Pediatric & Under-5 card should be present");
assert(strpos($html, 'PhilHealth Konsulta') !== false, "PhilHealth Konsulta card should be present");
assert(strpos($html, 'Demographic Presets:') !== false, "Demographic Presets chips should be present");

// 4. Verify Barangay field is strictly NOT in the patient directory view template
$viewCode = file_get_contents(__DIR__ . '/../app/Views/patients/index.php');
assert(stripos($viewCode, 'barangay') === false, "Barangay field must NOT be present in patient directory view");

echo "✓ View template is clean and has no traces of barangay field!\n";

echo "\n=== ALL PHASE 1 CHECKS PASSED SUCCESSFULLY ===\n";
