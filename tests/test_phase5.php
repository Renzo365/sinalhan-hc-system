<?php

// Check if run from CLI
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

$dbConfig = require dirname(__DIR__) . '/config/database.php';

use App\Models\Patient;
use App\Models\WellbabyRecord;
use App\Models\ChildGrowthLog;
use App\Models\Immunization;
use App\Controllers\WellbabyController;

echo "\n=======================================================\n";
echo "   PHASE 5 WELL BABY & GROWTH MONITORING TEST SUITE    \n";
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
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; DELETE FROM patients WHERE first_name LIKE 'TestP5%'; SET FOREIGN_KEY_CHECKS = 1;");

    $patientModel = new Patient();
    $wbModel = new WellbabyRecord();
    $growthModel = new ChildGrowthLog();
    $immModel = new Immunization();

    // 1. Controller & Model Initialization
    $wbController = new WellbabyController();
    assertTest($wbController instanceof WellbabyController, "WellbabyController initialized");
    assertTest($immModel instanceof Immunization, "Immunization model instantiated");

    // 2. Create Mother Patient
    $motherId = $patientModel->create([
        'first_name' => 'TestP5Mother',
        'middle_name' => 'Santos',
        'last_name' => 'Alvarez',
        'suffix' => null,
        'dob' => '1996-08-10',
        'sex' => 'Female',
        'civil_status' => 'Married',
        'blood_type' => 'B+',
        'religion' => 'Roman Catholic',
        'family_no' => 'FAM-TEST-P5',
        'contact_no' => '09175556677',
        'barangay' => 'Sinalhan',
        'address' => 'Purok 1 Main Road',
        'created_by' => 1
    ]);
    assertTest($motherId > 0, "Created Mother Patient (ID: $motherId)");

    // 3. Create Infant Patient (2 months old)
    $infantDob = date('Y-m-d', strtotime('-2 months'));
    $infantId = $patientModel->create([
        'first_name' => 'TestP5Infant',
        'middle_name' => 'Santos',
        'last_name' => 'Alvarez',
        'suffix' => 'Jr.',
        'dob' => $infantDob,
        'sex' => 'Male',
        'civil_status' => 'Single',
        'blood_type' => 'B+',
        'religion' => 'Roman Catholic',
        'family_no' => 'FAM-TEST-P5',
        'barangay' => 'Sinalhan',
        'address' => 'Purok 1 Main Road',
        'mother_name' => 'TestP5Mother Alvarez',
        'created_by' => 1
    ]);
    assertTest($infantId > 0, "Created Infant Patient (ID: $infantId)");

    // 4. Verify Program Badge for Infant
    $badge = $patientModel->getProgramBadge($infantId, $infantDob, 'Male');
    assertTest($badge['tag'] === 'wellbaby', "Infant program badge dynamically evaluated to 'wellbaby' (Green)");

    // 5. Initialize Well Baby Record with Birth Context & Newborn Screening
    $wbData = [
        'patient_id' => $infantId,
        'mother_patient_id' => $motherId,
        'birth_time' => '08:30:00',
        'birth_weight_kg' => 3.10,
        'birth_length_cm' => 49.0,
        'place_of_delivery' => 'Lying-in Clinic',
        'delivery_type' => 'Normal Spontaneous Delivery (NSD)',
        'attended_by' => 'Midwife Ramos',
        'newborn_screening_done' => 1,
        'newborn_screening_date' => date('Y-m-d', strtotime($infantDob . ' +2 days')),
        'newborn_screening_result' => 'Normal (Cert # NBS-2026-8888)',
        'mother_cpab_tt' => 'TT2 Complete in 2026',
        'feeding_method' => 'LAM / Exclusive Breastfeeding',
        'created_by' => 1
    ];

    $wbId = $wbModel->createRecord($wbData);
    assertTest($wbId > 0, "Created Well Baby Record (ID: $wbId)");

    // 6. Verify Well Baby Record Retrieval & Mother Relationship Link
    $savedWb = $wbModel->findByPatientId($infantId);
    assertTest($savedWb !== false, "Retrieved Well Baby record for Infant");
    assertTest((float)$savedWb['birth_weight_kg'] === 3.10 && (float)$savedWb['birth_length_cm'] === 49.0, "Birth weight (3.10kg) and length (49cm) verified");
    assertTest((int)$savedWb['mother_patient_id'] === $motherId && $savedWb['mother_last_name'] === 'Alvarez', "Mother patient relationship link verified ({$savedWb['mother_last_name']}, {$savedWb['mother_first_name']})");
    assertTest((int)$savedWb['newborn_screening_done'] === 1 && strpos($savedWb['newborn_screening_result'], 'NBS-2026-8888') !== false, "Newborn screening certificate verified");

    // 7. Update Well Baby Record
    $updateWb = $wbModel->updateRecord($wbId, array_merge($wbData, [
        'feeding_method' => 'Mixed Feeding',
        'attended_by' => 'Dr. Santos / Midwife Ramos'
    ]));
    assertTest($updateWb === true, "Updated Well Baby record details");

    // 8. Record EPI Routine Infant Immunizations (BCG, Hep B, Penta 1, OPV 1, Rota 1)
    $bcgId = $immModel->recordDose([
        'patient_id' => $infantId,
        'vaccine_name' => 'BCG',
        'dose_number' => 1,
        'administered_date' => $infantDob,
        'remarks' => 'Right deltoid at birth',
        'administered_by' => 1
    ]);
    assertTest($bcgId > 0, "Recorded BCG Dose 1 at birth (ID: $bcgId)");

    $hepBId = $immModel->recordDose([
        'patient_id' => $infantId,
        'vaccine_name' => 'Hepatitis B',
        'dose_number' => 1,
        'administered_date' => $infantDob,
        'remarks' => 'Within 24 hours of birth',
        'administered_by' => 1
    ]);
    assertTest($hepBId > 0, "Recorded Hepatitis B Dose 1 (ID: $hepBId)");

    $penta1Date = date('Y-m-d', strtotime($infantDob . ' +6 weeks'));
    $pentaId = $immModel->recordDose([
        'patient_id' => $infantId,
        'vaccine_name' => 'Pentavalent',
        'dose_number' => 1,
        'administered_date' => $penta1Date,
        'remarks' => 'EPI 6 weeks',
        'administered_by' => 1
    ]);
    assertTest($pentaId > 0, "Recorded Pentavalent Dose 1 at 6 weeks (ID: $pentaId)");

    // Verify Vaccine Map
    $vaccineMap = $immModel->getVaccineMap($infantId);
    assertTest(isset($vaccineMap['BCG:1']) && isset($vaccineMap['HEPATITIS B:1']) && isset($vaccineMap['PENTAVALENT:1']), "Vaccine Map verified (BCG, Hep B, Pentavalent 1)");

    // 9. Record Periodic Growth Anthropometrics Checkups
    // Checkup 1: 1.5 Months
    $growth1Id = $growthModel->createLog([
        'wellbaby_id' => $wbId,
        'log_date' => $penta1Date,
        'age_months' => 1.5,
        'weight_kg' => 4.40,
        'height_cm' => 54.0,
        'head_circumference_cm' => 37.5,
        'chest_circumference_cm' => 37.0,
        'temperature' => 36.5,
        'feeding_method' => 'LAM / Exclusive Breastfeeding',
        'vaccines_administered' => 'Pentavalent 1, OPV 1, Rotavirus 1',
        'vitamin_a_dose' => 0,
        'deworming_dose' => 0,
        'tcb_notes' => 'Active, responsive, good muscle tone.',
        'recorded_by' => 1
    ]);
    assertTest($growth1Id > 0, "Recorded Growth Visit 1 at 1.5 mos (Head: 37.5cm, Chest: 37.0cm, ID: $growth1Id)");

    // Checkup 2: 6.0 Months (with Vitamin A supplementation)
    $growth2Date = date('Y-m-d', strtotime($infantDob . ' +6 months'));
    $growth2Id = $growthModel->createLog([
        'wellbaby_id' => $wbId,
        'log_date' => $growth2Date,
        'age_months' => 6.0,
        'weight_kg' => 7.20,
        'height_cm' => 65.0,
        'head_circumference_cm' => 42.0,
        'chest_circumference_cm' => 42.5,
        'temperature' => 36.6,
        'feeding_method' => 'Mixed Feeding',
        'vaccines_administered' => 'None',
        'vitamin_a_dose' => 1,
        'deworming_dose' => 0,
        'tcb_notes' => 'Administered 100,000 IU Vitamin A capsule.',
        'recorded_by' => 1
    ]);
    assertTest($growth2Id > 0, "Recorded Growth Visit 2 at 6.0 mos with Vitamin A dose (ID: $growth2Id)");

    // 10. Verify Growth Logs Retrieval
    $logs = $growthModel->findByWellbabyId($wbId);
    assertTest(count($logs) === 2, "Retrieved 2 chronological growth logs");
    assertTest((float)$logs[0]['weight_kg'] === 4.40 && (float)$logs[1]['weight_kg'] === 7.20, "Verified weight progression (4.40kg -> 7.20kg)");
    assertTest((int)$logs[1]['vitamin_a_dose'] === 1, "Verified Vitamin A supplementation dose record");

    // 11. Test Growth Log Deletion
    $deleteSuccess = $growthModel->deleteLog($growth2Id);
    assertTest($deleteSuccess === true, "Deleted growth log #$growth2Id");
    $logsAfterDelete = $growthModel->findByWellbabyId($wbId);
    assertTest(count($logsAfterDelete) === 1, "Confirmed remaining growth logs count is 1");

    // 12. Cleanup Test Records
    echo "\nCleaning up Phase 5 test records...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; DELETE FROM patients WHERE id IN ($motherId, $infantId); SET FOREIGN_KEY_CHECKS = 1;");
    echo "  [+] Cleaned up test well baby records and cascaded entries.\n";

} catch (Exception $e) {
    echo "\n[ERROR]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $failCount++;
}

echo "\n=======================================================\n";
echo "PHASE 5 TEST RESULTS: $passCount PASSED, $failCount FAILED\n";
echo "=======================================================\n\n";

exit($failCount > 0 ? 1 : 0);
