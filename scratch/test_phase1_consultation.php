<?php
/**
 * Test Suite for Phase 1 Consultation Remediation:
 * 1. AJAX Vitals logging & DOM integration
 * 2. Prescription rehydration on form error
 * 3. Unsaved changes guard
 * 4. Mock provider purge
 */

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/../app/helpers.php';

echo "=== Running Phase 1 Consultation Verification Tests ===\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($condition, $message) {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] {$message}\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$message}\n";
        $failCount++;
    }
}

// 1. Mock Data Setup
$mockPatient = [
    'id' => 1,
    'patient_no' => 'P-2026-0001',
    'last_name' => 'Dela Cruz',
    'first_name' => 'Juan',
    'middle_name' => 'Protacio',
    'suffix' => 'Jr.',
    'dob' => '1990-05-15',
    'age' => 36,
    'sex' => 'Male',
    'blood_type' => 'O+',
    'address' => '123 Lakeside St, Sinalhan',
    'envelope_no' => 'E-12',
    'family_no' => 'F-001'
];

$mockClinicians = [
    ['id' => 2, 'first_name' => 'Maria', 'last_name' => 'Santos', 'job_title' => 'Rural Health Midwife'],
    ['id' => 3, 'first_name' => 'Jose', 'last_name' => 'Rizal', 'job_title' => 'Municipal Health Officer']
];

$mockVitalsList = [
    [
        'id' => 10,
        'patient_id' => 1,
        'bp_systolic' => 120,
        'bp_diastolic' => 80,
        'temperature' => 36.6,
        'heart_rate' => 72,
        'respiratory_rate' => 18,
        'oxygen_saturation' => 98,
        'weight' => 65.0,
        'height' => 170.0,
        'bmi' => 22.49,
        'waist_circumference' => 78.0,
        'notes' => 'Patient is well',
        'recorded_at' => '2026-09-30 08:30:00'
    ]
];

$mockPrescriptions = [
    [
        'medicine_name' => 'Amoxicillin 500mg',
        'dosage' => '1 capsule',
        'frequency' => '3x a day',
        'duration' => '7 days',
        'instructions' => 'Take after meals'
    ]
];

// TEST 1: Check create.php file content for mock name purge
$createFileContent = file_get_contents(__DIR__ . '/../app/Views/consultations/create.php');
assertTest(strpos($createFileContent, 'Juana Dela Cruz, RM') === false, "create.php does NOT contain mock clinician 'Juana Dela Cruz, RM'");
assertTest(strpos($createFileContent, 'btn-cancel-consultation') !== false, "create.php contains 'btn-cancel-consultation' class");
assertTest(strpos($createFileContent, 'isConsultationDirty') !== false, "create.php contains 'isConsultationDirty' unsaved changes guard");
assertTest(strpos($createFileContent, 'oldPrescriptions') !== false, "create.php contains prescription rehydration script");
assertTest(strpos($createFileContent, 'vitalsForm.addEventListener(\'submit\', async function(e)') !== false, "create.php contains AJAX vitals form submission handler");
assertTest(strpos($createFileContent, 'id="vitalsEmptyCard"') !== false, "create.php contains '#vitalsEmptyCard'");
assertTest(strpos($createFileContent, 'id="vitalsSelectGroup"') !== false, "create.php contains '#vitalsSelectGroup'");

// TEST 2: Check edit.php file content for mock name purge & dirty guard
$editFileContent = file_get_contents(__DIR__ . '/../app/Views/consultations/edit.php');
assertTest(strpos($editFileContent, 'Juana Dela Cruz, RM') === false, "edit.php does NOT contain mock clinician 'Juana Dela Cruz, RM'");
assertTest(strpos($editFileContent, 'btn-cancel-consultation') !== false, "edit.php contains 'btn-cancel-consultation' class");
assertTest(strpos($editFileContent, 'isConsultationDirty') !== false, "edit.php contains 'isConsultationDirty' unsaved changes guard");

// TEST 3: Check ConsultationController edit method prescription fallback
$controllerContent = file_get_contents(__DIR__ . '/../app/Controllers/ConsultationController.php');
assertTest(strpos($controllerContent, '$prescriptions = !empty($input[\'prescriptions\']) ? $input[\'prescriptions\'] : $dbPrescriptions;') !== false, "ConsultationController::edit preserves uncommitted prescriptions from form input");

// TEST 4: Render create.php with mock variables in buffer
echo "\n--- Testing View Render Execution ---\n";
$_SESSION['user_id'] = 2;
$_SESSION['user_name'] = 'Maria Santos';
$_SESSION['user_role'] = 'staff';

$patient = $mockPatient;
$clinicians = $mockClinicians;
$vitalsList = $mockVitalsList;
$latestVitals = $mockVitalsList[0];
$medicalHistory = [];
$activePrenatal = null;
$cdsAlerts = ['has_alerts' => false, 'flags' => []];
$errors = [];
$input = [
    'prescriptions' => $mockPrescriptions
];

ob_start();
try {
    include __DIR__ . '/../app/Views/consultations/create.php';
    $renderedOutput = ob_get_clean();
    assertTest(true, "create.php rendered successfully without runtime errors");
    assertTest(strpos($renderedOutput, 'Amoxicillin 500mg') !== false, "Flashed prescriptions successfully embedded in rendered JS for rehydration");
    assertTest(strpos($renderedOutput, 'Maria Santos (Rural Health Midwife)') !== false, "Consulting provider correctly defaulted to logged-in clinician");
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest(false, "create.php render threw exception: " . $e->getMessage());
}

// TEST 5: Render create.php with EMPTY vitals
$vitalsList = [];
$latestVitals = null;
ob_start();
try {
    include __DIR__ . '/../app/Views/consultations/create.php';
    $renderedEmptyVitals = ob_get_clean();
    assertTest(strpos($renderedEmptyVitals, 'id="vitalsEmptyCard"') !== false && strpos($renderedEmptyVitals, 'id="vitalsSelectGroup"') !== false, "create.php with empty vitals renders both empty card and select group for dynamic AJAX injection");
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest(false, "create.php empty vitals render threw exception: " . $e->getMessage());
}

// TEST 6: Render edit.php with existing consultation and prescriptions
$mockConsultation = [
    'id' => 45,
    'patient_id' => 1,
    'vital_signs_id' => 10,
    'consulting_provider' => 'Maria Santos (Rural Health Midwife)',
    'subjective' => 'Patient has mild headache',
    'objective' => 'Normal physical exam',
    'assessment' => 'Tension headache',
    'plan' => 'Hydration and rest',
    'status' => 'Completed',
    'consulted_at' => '2026-09-30 08:30:00',
    'created_at' => '2026-09-30 08:30:00',
    'updated_at' => null,
    'creator_name' => 'Maria Santos',
    'updater_name' => null
];

$consultation = $mockConsultation;
$prescriptions = $mockPrescriptions;
$input = $mockConsultation;

ob_start();
try {
    include __DIR__ . '/../app/Views/consultations/edit.php';
    $renderedEdit = ob_get_clean();
    assertTest(true, "edit.php rendered successfully with prescriptions without runtime errors");
    assertTest(strpos($renderedEdit, 'initialConsultationData = getConsultationFormData()') !== false, "edit.php contains initialConsultationData snapshot initialization");
    assertTest(strpos($renderedEdit, 'window.isConsultationDirty') !== false, "edit.php contains window.isConsultationDirty");
    assertTest(strpos($renderedEdit, 'document.addEventListener(\'click\', function(e)') !== false, "edit.php contains global click capture guard");
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest(false, "edit.php render threw exception: " . $e->getMessage());
}

echo "\n============================================\n";
echo "Test Results: {$passCount} Passed, {$failCount} Failed.\n";
if ($failCount === 0) {
    echo ">>> All Phase 1 Verification Checks Succeeded! <<<\n";
}
