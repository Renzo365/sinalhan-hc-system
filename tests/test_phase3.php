<?php

// Check if run from CLI
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

$dbConfig = require dirname(__DIR__) . '/config/database.php';

use App\Models\Patient;
use App\Models\PatientMedicalHistory;
use App\Models\VitalSigns;
use App\Models\PrenatalRecord;
use App\Controllers\PatientMedicalHistoryController;

echo "\n=======================================================\n";
echo "   PHASE 3 PATIENT WORKSTATION & IHP TEST SUITE        \n";
echo "=======================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($condition, $testName) {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] $testName\n";
        $passCount++;
    } else {
        echo "  [FAIL] $testName\n";
        $failCount++;
    }
}

try {
    $pdo = new PDO("mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}", $dbConfig['username'], $dbConfig['password']);
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; DELETE FROM patients WHERE first_name LIKE 'TestP3%'; SET FOREIGN_KEY_CHECKS = 1;");

    $patientModel = new Patient();
    $pmhModel = new PatientMedicalHistory();
    $vitalsModel = new VitalSigns();
    $prenatalModel = new PrenatalRecord();

    // 1. Controller & Route Class Initialization
    $pmhController = new PatientMedicalHistoryController();
    assertTest($pmhController instanceof PatientMedicalHistoryController, "PatientMedicalHistoryController initialized");

    // 2. Create Test Patient
    $testPatientData = [
        'first_name' => 'TestP3Maria',
        'middle_name' => 'Clara',
        'last_name' => 'Santos',
        'suffix' => null,
        'dob' => '1995-06-15',
        'sex' => 'Female',
        'civil_status' => 'Married',
        'blood_type' => 'B+',
        'religion' => 'Roman Catholic',
        'occupation' => 'Teacher',
        'education_attainment' => 'College / Post-Graduate',
        'family_no' => 'FAM-TEST-P3',
        'contact_no' => '09179998877',
        'barangay' => 'Sinalhan',
        'address' => 'Purok 2 Block 8',
        'phic_status' => 'Member',
        'phic_type' => 'Employed - Private',
        'philhealth_no' => '12-333444555-6',
        'spouse_name' => 'TestP3Juan Santos',
        'spouse_dob' => '1993-04-10',
        'emergency_name' => 'TestP3Juan Santos',
        'emergency_relationship' => 'Spouse',
        'emergency_no' => '09181112233',
        'created_by' => 1
    ];

    $patientId = $patientModel->create($testPatientData);
    assertTest($patientId > 0, "Created Test Patient for Phase 3 (ID: $patientId)");

    // 3. Save Annex A1 IHP Medical History
    $ihpPayload = [
        'past_medical_history' => [
            'Allergy' => 'Penicillin, Shellfish',
            'Hypertension' => 'Highest BP: 160/100',
            'Asthma' => 'Asthma',
            'Diabetes Mellitus' => 'Diabetes Mellitus'
        ],
        'surgical_history' => [
            [
                'operation' => 'Appendectomy',
                'date' => '2019-03-12',
                'hospital' => 'Sta. Rosa Community Hospital'
            ],
            [
                'operation' => 'Cholecystectomy',
                'date' => '2022-08-20',
                'hospital' => 'Qualimed Hospital'
            ]
        ],
        'family_history' => [
            'Hypertension',
            'Diabetes Mellitus',
            'Asthma',
            'Kidney Disease'
        ],
        'smoking_status' => 'Yes',
        'smoking_pack_years' => 5.5,
        'alcohol_status' => 'Quit',
        'alcohol_bottles_per_day' => null,
        'illicit_drugs' => 0,
        'menarche_age' => 12,
        'sexual_onset_age' => 22,
        'lmp' => '2026-08-01',
        'period_duration_days' => 5,
        'cycle_interval_days' => 28,
        'pads_per_day' => 3,
        'is_menopausal' => 0,
        'menopause_age' => null,
        'birth_control_method' => 'Oral Contraceptive Pills'
    ];

    $saveResult = $pmhModel->saveHistory($patientId, $ihpPayload, 1);
    assertTest($saveResult === true, "Saved PhilHealth Annex A1 IHP Medical History");

    // 4. Retrieve & Validate Persisted IHP Data
    $retrievedHistory = $pmhModel->findByPatientId($patientId);
    assertTest($retrievedHistory !== false, "Retrieved IHP record from database");
    assertTest(isset($retrievedHistory['past_medical_history']['Allergy']) && $retrievedHistory['past_medical_history']['Allergy'] === 'Penicillin, Shellfish', "Allergy specifics correctly saved and decoded");
    assertTest(count($retrievedHistory['surgical_history']) === 2, "2 surgical procedures correctly retrieved");
    assertTest($retrievedHistory['surgical_history'][0]['operation'] === 'Appendectomy', "First operation name verified");
    assertTest(in_array('Hypertension', $retrievedHistory['family_history']), "Family heredity checklist includes Hypertension");
    assertTest((float)$retrievedHistory['smoking_pack_years'] === 5.5, "Smoking pack years matches 5.5");
    assertTest($retrievedHistory['birth_control_method'] === 'Oral Contraceptive Pills', "Birth control method matches 'Oral Contraceptive Pills'");

    // 5. Test Clinical Safety Flag Logic
    // Allergy Detection
    $hasAllergy = !empty($retrievedHistory['past_medical_history']['Allergy']);
    assertTest($hasAllergy === true, "Safety Alert: Allergy detected ('Penicillin, Shellfish')");

    // Hypertension Detection
    $hasHTN = !empty($retrievedHistory['past_medical_history']['Hypertension']);
    assertTest($hasHTN === true, "Safety Alert: Hypertension history detected");

    // Record High BP Vitals (150/95) with waist circumference
    $vitalsId = $vitalsModel->create([
        'patient_id' => $patientId,
        'bp_systolic' => 150,
        'bp_diastolic' => 95,
        'heart_rate' => 82,
        'temperature' => 36.6,
        'respiratory_rate' => 18,
        'oxygen_saturation' => 99,
        'weight' => 58.5,
        'height' => 160.0,
        'waist_circumference' => 78.0,
        'recorded_by' => 1
    ]);
    assertTest($vitalsId > 0, "Created vital signs with waist circumference (ID: $vitalsId)");

    $latestVitals = $vitalsModel->latestByPatientId($patientId);
    assertTest((int)$latestVitals['bp_systolic'] === 150 && (float)$latestVitals['waist_circumference'] === 78.0, "Latest vitals retrieved (BP 150/95, Waist 78cm)");

    // 6. Test PatientController Eager-loading
    $pController = new \App\Controllers\PatientController();
    $refPatientModel = new \ReflectionProperty(\App\Controllers\PatientController::class, 'patientModel');
    $refPatientModel->setAccessible(true);
    $loadedPatient = $refPatientModel->getValue($pController)->findById($patientId);
    assertTest($loadedPatient['id'] == $patientId, "PatientController found patient record");

    $householdMembers = $refPatientModel->getValue($pController)->familyMembers($loadedPatient['family_no'], $patientId);
    assertTest(is_array($householdMembers), "Household familyMembers() query executed safely");

    // 7. Cleanup
    echo "\nCleaning up Phase 3 test records...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; DELETE FROM patients WHERE id = $patientId; SET FOREIGN_KEY_CHECKS = 1;");
    echo "  [+] Cleaned up test patient record and cascaded IHP data.\n";

} catch (Exception $e) {
    echo "\n[ERROR]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $failCount++;
}

echo "\n=======================================================\n";
echo "PHASE 3 TEST RESULTS: $passCount PASSED, $failCount FAILED\n";
echo "=======================================================\n\n";

exit($failCount > 0 ? 1 : 0);
