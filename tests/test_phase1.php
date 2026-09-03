<?php

// Check if run from CLI
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

$dbConfig = require dirname(__DIR__) . '/config/database.php';

use App\Models\Patient;
use App\Models\VitalSigns;
use App\Models\PatientMedicalHistory;
use App\Models\PrenatalRecord;
use App\Models\PrenatalVisit;
use App\Models\PastObstetricHistory;
use App\Models\WellbabyRecord;
use App\Models\ChildGrowthLog;

echo "\n=======================================================\n";
echo "       PHASE 1 DATABASE & CORE MODELS TEST SUITE       \n";
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
    // Clean up any remnants of prior test runs
    $pdo = new PDO("mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}", $dbConfig['username'], $dbConfig['password']);
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; DELETE FROM patients WHERE first_name LIKE 'Test%' OR philhealth_no = '12-999999999-1' OR family_no = 'TEST-FAM-001'; SET FOREIGN_KEY_CHECKS = 1;");

    $patientModel = new Patient();
    $vitalsModel = new VitalSigns();
    $pmhModel = new PatientMedicalHistory();
    $prenatalModel = new PrenatalRecord();
    $pvModel = new PrenatalVisit();
    $pohModel = new PastObstetricHistory();
    $wbModel = new WellbabyRecord();
    $cglModel = new ChildGrowthLog();

    // 1. Test Model Instantiation
    assertTest($patientModel instanceof Patient, "Patient model instantiated");
    assertTest($pmhModel instanceof PatientMedicalHistory, "PatientMedicalHistory model instantiated");
    assertTest($prenatalModel instanceof PrenatalRecord, "PrenatalRecord model instantiated");
    assertTest($wbModel instanceof WellbabyRecord, "WellbabyRecord model instantiated");

    // 2. Test Creating Mother Patient with New Demographic Fields
    $motherData = [
        'first_name' => 'TestMother',
        'middle_name' => 'Santos',
        'last_name' => 'PhaseOne',
        'suffix' => null,
        'dob' => '1998-05-15',
        'sex' => 'Female',
        'civil_status' => 'Married',
        'blood_type' => 'O+',
        'religion' => 'Roman Catholic',
        'occupation' => 'Teacher',
        'education_attainment' => 'College / Post-Graduate',
        'family_no' => 'TEST-FAM-001',
        'contact_no' => '09171234567',
        'barangay' => 'Sinalhan',
        'address' => '100 Test Street, Sinalhan',
        'phic_status' => 'Member',
        'phic_type' => 'Employed - Private',
        'philhealth_no' => '12-999999999-1',
        'father_name' => 'Father Test',
        'father_dob' => '1970-01-01',
        'mother_name' => 'Mother Test',
        'mother_dob' => '1972-02-02',
        'spouse_name' => 'TestFather PhaseOne',
        'spouse_dob' => '1996-03-03',
        'emergency_name' => 'TestFather PhaseOne',
        'emergency_relationship' => 'Spouse',
        'emergency_no' => '09187654321',
        'created_by' => 1
    ];

    $motherId = $patientModel->create($motherData);
    assertTest($motherId > 0, "Created Mother Patient (ID: $motherId)");

    $fetchedMother = $patientModel->findById($motherId);
    assertTest($fetchedMother['family_no'] === 'TEST-FAM-001', "Verified Family No on Mother ('TEST-FAM-001')");
    assertTest($fetchedMother['phic_status'] === 'Member', "Verified PHIC Status ('Member')");
    assertTest($fetchedMother['phic_type'] === 'Employed - Private', "Verified PHIC Type ('Employed - Private')");
    assertTest($fetchedMother['education_attainment'] === 'College / Post-Graduate', "Verified Education Attainment");

    // 3. Test Creating Child Patient with Same Family No
    $childData = [
        'first_name' => 'TestChild',
        'middle_name' => 'Santos',
        'last_name' => 'PhaseOne',
        'suffix' => 'Jr.',
        'dob' => date('Y-m-d', strtotime('-2 months')),
        'sex' => 'Male',
        'civil_status' => 'Single',
        'blood_type' => 'O+',
        'family_no' => 'TEST-FAM-001',
        'contact_no' => '09171234567',
        'barangay' => 'Sinalhan',
        'address' => '100 Test Street, Sinalhan',
        'phic_status' => 'Dependent',
        'created_by' => 1
    ];

    $childId = $patientModel->create($childData);
    assertTest($childId > 0, "Created Child Patient (ID: $childId)");

    // 4. Test Family Clustering Helper
    $family = $patientModel->familyMembers('TEST-FAM-001');
    assertTest(count($family) === 2, "familyMembers() returned 2 household members for 'TEST-FAM-001'");

    // 5. Test PatientMedicalHistory (IHP)
    $pmhData = [
        'past_medical_history' => ['Allergy: Penicillin', 'Asthma: Maintenance Inhaler'],
        'surgical_history' => ['Appendectomy (2019)'],
        'family_history' => ['Hypertension (Mother)', 'Diabetes (Father)'],
        'smoking_status' => 'Never',
        'alcohol_status' => 'Quit',
        'menarche_age' => 13,
        'sexual_onset_age' => 22,
        'period_duration_days' => 5,
        'cycle_interval_days' => 28,
        'pads_per_day' => 3,
        'birth_control_method' => 'None (Pregnant)'
    ];

    $pmhSaved = $pmhModel->saveHistory($motherId, $pmhData, 1);
    assertTest($pmhSaved === true, "Saved PatientMedicalHistory for Mother");

    $fetchedPmh = $pmhModel->findByPatientId($motherId);
    assertTest(is_array($fetchedPmh['past_medical_history']) && in_array('Allergy: Penicillin', $fetchedPmh['past_medical_history']), "Verified JSON decoding of past medical history");
    assertTest($fetchedPmh['alcohol_status'] === 'Quit', "Verified alcohol status ('Quit')");

    // 6. Test Naegele's Rule & PrenatalRecord
    $lmp = date('Y-m-d', strtotime('-20 weeks'));
    $expectedEdc = date('Y-m-d', strtotime("$lmp +1 year -3 months +7 days"));
    $calcEdc = $prenatalModel->calculateEDC($lmp);
    assertTest($calcEdc === $expectedEdc, "Naegele EDC calculation ($calcEdc) matches expected ($expectedEdc)");

    $aog = $prenatalModel->calculateCurrentAOG($lmp);
    assertTest($aog['weeks'] === 20, "Dynamic AOG calculated 20 weeks (actual: {$aog['formatted']})");

    $prenatalData = [
        'patient_id' => $motherId,
        'husband_name' => 'TestFather PhaseOne',
        'gravida' => 2,
        'para' => 1,
        'term_births' => 1,
        'preterm_births' => 0,
        'abortions' => 0,
        'living_children' => 1,
        'lmp' => $lmp,
        'edc' => $calcEdc,
        'is_active' => 1,
        'pre_eclampsia' => 1,
        'fp_counselling' => 1,
        'created_by' => 1
    ];

    $prenatalId = $prenatalModel->createEpisode($prenatalData);
    assertTest($prenatalId > 0, "Created Prenatal Record Episode (ID: $prenatalId)");

    // 7. Test PrenatalVisit Log
    $visitData = [
        'prenatal_id' => $prenatalId,
        'visit_date' => date('Y-m-d'),
        'chief_complaint' => 'Routine 2nd Trimester Prenatal Check',
        'aog_weeks' => 20.0,
        'bp_systolic' => 120,
        'bp_diastolic' => 80,
        'weight_kg' => 58.5,
        'fetal_heart_tone' => 145,
        'fundal_height_cm' => 18.0,
        'fetal_presentation' => 'Cephalic',
        'tcb' => 'TT3 given',
        'remarks' => 'FHT clear, prescribed FeSO4 + Folic Acid',
        'attended_by' => 1
    ];

    $visitId = $pvModel->createVisit($visitData);
    assertTest($visitId > 0, "Logged Prenatal Visit (ID: $visitId)");

    $visits = $pvModel->findByPrenatalId($prenatalId);
    assertTest(count($visits) === 1 && $visits[0]['fetal_heart_tone'] == 145, "Retrieved Prenatal Visits list (FHT 145 bpm)");

    // 8. Test Past Obstetric History
    $pohData = [
        'patient_id' => $motherId,
        'gravida_no' => 1,
        'delivery_type' => 'NSD',
        'infant_sex' => 'Male',
        'place_of_delivery' => 'Sinalhan Lying-in',
        'year_delivered' => 2024,
        'attended_by' => 'Midwife Ramos',
        'status' => 'Alive',
        'tt_status' => 'TT1, TT2 given'
    ];

    $pohId = $pohModel->createRecord($pohData);
    assertTest($pohId > 0, "Created Past Obstetric History G1 (ID: $pohId)");

    $pohList = $pohModel->findByPatientId($motherId);
    assertTest(count($pohList) === 1 && $pohList[0]['delivery_type'] === 'NSD', "Retrieved Past Obstetric Histories");

    // 9. Test WellbabyRecord & ChildGrowthLog
    $wbData = [
        'patient_id' => $childId,
        'mother_patient_id' => $motherId,
        'birth_time' => '05:30:00',
        'birth_weight_kg' => 3.20,
        'birth_length_cm' => 50.0,
        'place_of_delivery' => 'Lying-in',
        'delivery_type' => 'Normal Spontaneous Delivery (NSD)',
        'attended_by' => 'Midwife',
        'newborn_screening_done' => 1,
        'newborn_screening_date' => date('Y-m-d', strtotime('-2 months + 2 days')),
        'newborn_screening_result' => 'NORMAL',
        'mother_cpab_tt' => 'Protected at Birth (TT2 active)',
        'feeding_method' => 'LAM / Exclusive Breastfeeding',
        'created_by' => 1
    ];

    $wbId = $wbModel->createRecord($wbData);
    assertTest($wbId > 0, "Created Well Baby Record for Child");

    $fetchedWb = $wbModel->findByPatientId($childId);
    assertTest($fetchedWb['mother_first_name'] === 'TestMother', "Verified Mother relationship link on Well Baby record");

    // 10. Test Child Growth Log
    $growthData = [
        'wellbaby_id' => $fetchedWb['id'],
        'log_date' => date('Y-m-d'),
        'age_months' => 2.0,
        'weight_kg' => 4.80,
        'height_cm' => 56.0,
        'head_circumference_cm' => 38.5,
        'chest_circumference_cm' => 38.0,
        'temperature' => 36.6,
        'feeding_method' => 'LAM / Exclusive Breastfeeding',
        'vaccines_administered' => 'Penta 1, OPV 1, Rota 1',
        'tcb_notes' => 'Responsive, smiling, healthy weight gain',
        'recorded_by' => 1
    ];

    $cglId = $cglModel->createLog($growthData);
    assertTest($cglId > 0, "Logged Child Growth checkup (ID: $cglId)");

    $growthLogs = $cglModel->findByWellbabyId($fetchedWb['id']);
    assertTest(count($growthLogs) === 1 && $growthLogs[0]['head_circumference_cm'] == 38.5, "Retrieved Child Growth Logs");

    // 11. Test Patient Model Relationship Helpers
    assertTest($patientModel->medicalHistory($motherId) !== false, "Patient::medicalHistory() helper working");
    assertTest($patientModel->activePrenatalRecord($motherId) !== false, "Patient::activePrenatalRecord() helper working");
    assertTest($patientModel->wellbabyRecord($childId) !== false, "Patient::wellbabyRecord() helper working");

    // Program badge checks
    $motherBadge = $patientModel->getProgramBadge($motherId, $motherData['dob'], 'Female');
    assertTest($motherBadge['tag'] === 'prenatal', "Mother identified as 'prenatal' program badge");

    $childBadge = $patientModel->getProgramBadge($childId, $childData['dob'], 'Male');
    assertTest($childBadge['tag'] === 'wellbaby', "Child (2 mos) identified as 'wellbaby' program badge");

    // 12. Clean up Test Data
    echo "\nCleaning up test records...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; DELETE FROM patients WHERE id IN ($motherId, $childId); SET FOREIGN_KEY_CHECKS = 1;");
    echo "  [+] Cleaned up test patient records and cascaded child entries.\n";

} catch (Exception $e) {
    echo "\n[ERROR]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $failCount++;
}

echo "\n=======================================================\n";
echo "TEST RESULTS: $passCount PASSED, $failCount FAILED\n";
echo "=======================================================\n\n";

exit($failCount > 0 ? 1 : 0);
