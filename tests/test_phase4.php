<?php

// Check if run from CLI
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

$dbConfig = require dirname(__DIR__) . '/config/database.php';

use App\Models\Patient;
use App\Models\PrenatalRecord;
use App\Models\PrenatalVisit;
use App\Models\PastObstetricHistory;
use App\Controllers\PrenatalController;

echo "\n=======================================================\n";
echo "   PHASE 4 MATERNAL & PRENATAL CARE TEST SUITE         \n";
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
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; DELETE FROM patients WHERE first_name LIKE 'TestP4%'; SET FOREIGN_KEY_CHECKS = 1;");

    $patientModel = new Patient();
    $prenatalModel = new PrenatalRecord();
    $visitModel = new PrenatalVisit();
    $pohModel = new PastObstetricHistory();

    // 1. Controller Initialization
    $prenatalController = new PrenatalController();
    assertTest($prenatalController instanceof PrenatalController, "PrenatalController initialized");

    // 2. Create Female Patient (26 yrs old)
    $femalePatientData = [
        'first_name' => 'TestP4Mother',
        'middle_name' => 'Reyes',
        'last_name' => 'Dela Cruz',
        'suffix' => null,
        'dob' => '2000-03-20',
        'sex' => 'Female',
        'civil_status' => 'Married',
        'blood_type' => 'O+',
        'religion' => 'Roman Catholic',
        'occupation' => 'Tailor',
        'education_attainment' => 'High School',
        'family_no' => 'FAM-TEST-P4',
        'contact_no' => '09176665544',
        'barangay' => 'Sinalhan',
        'address' => 'Purok 4 Block 2',
        'phic_status' => 'Member',
        'phic_type' => 'Sponsored - NHTS',
        'philhealth_no' => '12-999000111-2',
        'spouse_name' => 'Juan Dela Cruz',
        'spouse_dob' => '1998-05-15',
        'emergency_name' => 'Juan Dela Cruz',
        'emergency_relationship' => 'Spouse',
        'emergency_no' => '09187776655',
        'created_by' => 1
    ];

    $patientId = $patientModel->create($femalePatientData);
    assertTest($patientId > 0, "Created Mother Patient for Phase 4 (ID: $patientId)");

    // 3. Test Naegele's Rule Calculation (LMP: 2026-02-10 -> EDC: 2026-11-17)
    $lmp = '2026-02-10';
    $calculatedEDC = $prenatalModel->calculateEDC($lmp);
    assertTest($calculatedEDC === '2026-11-17', "Naegele's Rule correctly computed EDC: $calculatedEDC");

    // 4. Test Dynamic AOG Calculation
    $calculatedAOG = $prenatalModel->calculateCurrentAOG($lmp);
    assertTest(!empty($calculatedAOG['weeks']) && $calculatedAOG['weeks'] >= 28, "Dynamic AOG calculated ({$calculatedAOG['formatted']})");

    // 5. Start Active Maternal Pregnancy Episode
    $episodeData = [
        'patient_id' => $patientId,
        'husband_name' => 'Juan Dela Cruz',
        'gravida' => 2,
        'para' => 1,
        'term_births' => 1,
        'preterm_births' => 0,
        'abortions' => 0,
        'living_children' => 1,
        'lmp' => $lmp,
        'edc' => $calculatedEDC,
        'is_active' => 1,
        'pre_eclampsia' => 1, // High Risk Flag
        'fp_counselling' => 1,
        'notes' => 'Patient enrolled in CHO I Maternal Care program. Monitored for elevated BP.',
        'created_by' => 1
    ];

    $episodeId = $prenatalModel->createEpisode($episodeData);
    assertTest($episodeId > 0, "Created Active Pregnancy Episode #$episodeId");

    // 6. Verify Active Episode Retrieval & Pre-Eclampsia High Risk Alert
    $activeEpisode = $prenatalModel->findActiveByPatientId($patientId);
    assertTest($activeEpisode !== false && $activeEpisode['id'] == $episodeId, "Retrieved active maternal episode");
    assertTest((int)$activeEpisode['pre_eclampsia'] === 1, "Clinical Safety Alert: Pre-Eclampsia High-Risk flag confirmed");
    assertTest((int)$activeEpisode['gravida'] === 2 && (int)$activeEpisode['para'] === 1, "GTPAL Score verified (G2 P1)");

    // 7. Verify Patient Program Badge is 'prenatal'
    $badge = $patientModel->getProgramBadge($patientId, $femalePatientData['dob'], 'Female');
    assertTest($badge['tag'] === 'prenatal', "Patient badge dynamically evaluated to 'prenatal' (Pink)");

    // 8. Log Past Obstetric History (Gravida 1 delivered in 2024)
    $pastDeliveryData = [
        'patient_id' => $patientId,
        'gravida_no' => 1,
        'delivery_type' => 'NSD',
        'infant_sex' => 'Male',
        'place_of_delivery' => 'Sta. Rosa Lying-in Clinic',
        'year_delivered' => 2024,
        'attended_by' => 'Midwife Ramos',
        'status' => 'Alive',
        'tt_status' => 'TT2 in 2024'
    ];

    $pastDeliveryId = $pohModel->createRecord($pastDeliveryData);
    assertTest($pastDeliveryId > 0, "Created Past Obstetric History G1 (ID: $pastDeliveryId)");

    $pastList = $pohModel->findByPatientId($patientId);
    assertTest(count($pastList) === 1 && $pastList[0]['attended_by'] === 'Midwife Ramos', "Retrieved past delivery G1 from matrix");

    // 9. Log Serial Follow-up Prenatal Visits
    // Visit 1: First Trimester checkup
    $visit1Id = $visitModel->createVisit([
        'prenatal_id' => $episodeId,
        'visit_date' => '2026-05-15',
        'chief_complaint' => 'Routine prenatal checkup',
        'aog_weeks' => 13.5,
        'bp_systolic' => 120,
        'bp_diastolic' => 80,
        'weight_kg' => 54.0,
        'height_cm' => 158.0,
        'fetal_heart_tone' => 144,
        'fundal_height_cm' => 14.0,
        'fetal_presentation' => 'Cephalic',
        'tcb' => 'TT3 given',
        'remarks' => 'Fetal heart tone clear, normal range.',
        'attended_by' => 1
    ]);
    assertTest($visit1Id > 0, "Logged Follow-up Visit 1 at 13.5 wks (FHT: 144 bpm, ID: $visit1Id)");

    // Visit 2: Second Trimester checkup
    $visit2Id = $visitModel->createVisit([
        'prenatal_id' => $episodeId,
        'visit_date' => '2026-08-10',
        'chief_complaint' => 'Mild leg edema',
        'aog_weeks' => 26.0,
        'bp_systolic' => 135,
        'bp_diastolic' => 85,
        'weight_kg' => 58.5,
        'height_cm' => 158.0,
        'fetal_heart_tone' => 148,
        'fundal_height_cm' => 25.0,
        'fetal_presentation' => 'Cephalic',
        'tcb' => 'Iron/Folic acid supplied',
        'remarks' => 'Advised low salt diet, monitor BP regularly.',
        'attended_by' => 1
    ]);
    assertTest($visit2Id > 0, "Logged Follow-up Visit 2 at 26 wks (FHT: 148 bpm, ID: $visit2Id)");

    $visits = $visitModel->findByPrenatalId($episodeId);
    assertTest(count($visits) === 2, "Retrieved 2 chronological follow-up visits");
    assertTest((int)$visits[0]['fetal_heart_tone'] === 144 && (int)$visits[1]['fetal_heart_tone'] === 148, "Verified FHT logs (144 bpm & 148 bpm in physiological range 120-160)");

    // 10. Test Updating Pregnancy Episode
    $updateSuccess = $prenatalModel->updateEpisode($episodeId, array_merge($episodeData, [
        'living_children' => 2,
        'notes' => 'Updated clinical notes during 3rd trimester'
    ]));
    assertTest($updateSuccess === true, "Updated ongoing pregnancy episode");

    // 11. Test Concluding Pregnancy Episode
    $concludeSuccess = $prenatalModel->concludeEpisode($episodeId, [
        'delivery_date' => '2026-11-15',
        'delivery_outcome' => 'Live Birth (Single)',
        'notes' => 'Delivered healthy baby boy at CHO Lying-in'
    ]);
    assertTest($concludeSuccess === true, "Concluded pregnancy episode upon delivery");

    // Verify episode is no longer active
    $activeAfterConclude = $prenatalModel->findActiveByPatientId($patientId);
    assertTest($activeAfterConclude === false, "Confirmed pregnancy episode is deactivated (is_active = 0)");

    $badgeAfterConclude = $patientModel->getProgramBadge($patientId, $femalePatientData['dob'], 'Female');
    assertTest($badgeAfterConclude['tag'] === 'opd', "Program badge reverted to 'General OPD' after delivery conclusion");

    // 12. Cleanup Test Records
    echo "\nCleaning up Phase 4 test records...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; DELETE FROM patients WHERE id = $patientId; SET FOREIGN_KEY_CHECKS = 1;");
    echo "  [+] Cleaned up test maternal records and cascaded entries.\n";

} catch (Exception $e) {
    echo "\n[ERROR]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $failCount++;
}

echo "\n=======================================================\n";
echo "PHASE 4 TEST RESULTS: $passCount PASSED, $failCount FAILED\n";
echo "=======================================================\n\n";

exit($failCount > 0 ? 1 : 0);
