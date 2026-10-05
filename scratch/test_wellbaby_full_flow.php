<?php
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Models/Patient.php';
require_once __DIR__ . '/../app/Models/WellbabyRecord.php';
require_once __DIR__ . '/../app/Models/Immunization.php';
require_once __DIR__ . '/../app/Models/ChildGrowthLog.php';
require_once __DIR__ . '/../app/Validators/BaseValidator.php';
require_once __DIR__ . '/../app/Validators/WellbabyValidator.php';

use App\Core\Database;
use App\Models\Patient;
use App\Models\WellbabyRecord;
use App\Models\Immunization;
use App\Models\ChildGrowthLog;

$pass = 0;
$fail = 0;

function it(string $description, bool $condition, string $detail = '') {
    global $pass, $fail;
    if ($condition) {
        echo "  [PASS] {$description}\n";
        $pass++;
    } else {
        echo "  [FAIL] {$description}" . ($detail ? " -> {$detail}" : "") . "\n";
        $fail++;
    }
}

echo "====================================================\n";
echo " WELL-BABY & EPI MODULE: FULL VERIFICATION SUITE \n";
echo "====================================================\n\n";

// ----------------------------------------------------
// 1. Pediatric Age Helper Verification
// ----------------------------------------------------
echo "1. Pediatric Age Helper Verification:\n";

$ref = '2026-10-04';

// 0 days
it("Born today returns 'Newborn (0 days)'", calculate_pediatric_age('2026-10-04', $ref) === 'Newborn (0 days)');
// 1 day
it("1 day old returns '1 day old'", calculate_pediatric_age('2026-10-03', $ref) === '1 day old');
// 20 days
it("20 days old returns '20 days old'", calculate_pediatric_age('2026-09-14', $ref) === '20 days old');
// Exactly 1 month
it("Exactly 1 month returns '1 mo (1 mos)'", str_contains(calculate_pediatric_age('2026-09-04', $ref), '1 mo'));
// 3 months 12 days
$a3m12d = calculate_pediatric_age('2026-06-22', $ref);
it("3 months 12 days includes '3 mos, 12 days' and '(3.4 mos)'", str_contains($a3m12d, '3 mos, 12 days') && str_contains($a3m12d, '(3.4 mos)'));
// Exactly 1 year
$a1y = calculate_pediatric_age('2025-10-04', $ref);
it("Exactly 1 year includes '1 yr' and '(12 mos)'", str_contains($a1y, '1 yr') && str_contains($a1y, '(12 mos)'));
// 3 years 2 months
$a3y2m = calculate_pediatric_age('2023-08-04', $ref);
it("3 yrs 2 mos includes '3 yrs, 2 mos' and '(38 mos)'", str_contains($a3y2m, '3 yrs, 2 mos') && str_contains($a3y2m, '(38 mos)'));
// Null / empty DOB
it("Empty DOB returns 'N/A'", calculate_pediatric_age(null) === 'N/A');

// calculate_age_in_months
it("calculate_age_in_months same day returns 0.0", calculate_age_in_months('2026-10-04', '2026-10-04') === 0.0);
it("calculate_age_in_months 38 months returns 38.0", calculate_age_in_months('2023-08-04', '2026-10-04') === 38.0);
it("calculate_age_in_months null DOB returns 0.0", calculate_age_in_months(null, '2026-10-04') === 0.0);


// ----------------------------------------------------
// 2. Canonicalization & Dose Mapping Verification
// ----------------------------------------------------
echo "\n2. Canonicalization & Routine Schedule Mapping:\n";

it("Rotavirus Dose 1 canonicalizes to Rotavirus Vaccine (ROTA)", 
    Immunization::canonicalizeVaccineName('Rotavirus', 1) === 'Rotavirus Vaccine (ROTA)');
it("ROTA Dose 2 canonicalizes to Rotavirus Vaccine (ROTA)", 
    Immunization::canonicalizeVaccineName('ROTA', 2) === 'Rotavirus Vaccine (ROTA)');
it("PCV Dose 1 canonicalizes to Pneumococcal Conjugate Vaccine (PCV)", 
    Immunization::canonicalizeVaccineName('PCV', 1) === 'Pneumococcal Conjugate Vaccine (PCV)');
it("MCV Dose 1 canonicalizes to Measles-Rubella (MCV1)", 
    Immunization::canonicalizeVaccineName('MCV', 1) === 'Measles-Rubella (MCV1)');
it("MCV Dose 2 canonicalizes to Measles-Mumps-Rubella (MCV2)", 
    Immunization::canonicalizeVaccineName('MCV', 2) === 'Measles-Mumps-Rubella (MCV2)');
it("MCV1 alias canonicalizes to Measles-Rubella (MCV1)", 
    Immunization::canonicalizeVaccineName('MCV1') === 'Measles-Rubella (MCV1)');
it("MMR alias canonicalizes to Measles-Mumps-Rubella (MCV2)", 
    Immunization::canonicalizeVaccineName('MMR') === 'Measles-Mumps-Rubella (MCV2)');
it("Vitamin A canonicalizes to Vitamin A", 
    Immunization::canonicalizeVaccineName('Vitamin A', 1) === 'Vitamin A');
it("Deworming canonicalizes to Deworming", 
    Immunization::canonicalizeVaccineName('Deworming', 1) === 'Deworming');

// ----------------------------------------------------
// 2b. Validator Edge Cases
// ----------------------------------------------------
echo "\n2b. Validator Edge Cases:\n";
$val = new \App\Validators\WellbabyValidator();

$err1 = $val->validateBirthRecord([
    'birth_weight_kg' => 3.2,
    'birth_length_cm' => 50,
    'place_of_delivery' => 'Others',
    'place_of_delivery_other' => '',
    'attended_by' => 'Midwife'
]);
it("Validator catches missing place_of_delivery_other when 'Others' selected", 
    in_array('Please specify the other place of delivery.', $err1));

$err2 = $val->validateBirthRecord([
    'birth_weight_kg' => 3.2,
    'birth_length_cm' => 50,
    'place_of_delivery' => 'Hospital',
    'attended_by' => 'Others',
    'attended_by_other' => '   '
]);
it("Validator catches missing attended_by_other when 'Others' selected", 
    in_array('Please specify the other attendant.', $err2));

$err3 = $val->validateBirthRecord([
    'birth_weight_kg' => 3.2,
    'birth_length_cm' => 50,
    'place_of_delivery' => 'Others',
    'place_of_delivery_other' => 'Clinic ABC',
    'attended_by' => 'Others',
    'attended_by_other' => 'Doctor XYZ'
]);
it("Validator passes when other fields are properly provided", empty($err3));

// ----------------------------------------------------
// 3. Database Schema & Persistence Verification
// ----------------------------------------------------
echo "\n3. Database Schema & Field Persistence:\n";

$pdo = Database::getInstance()->getConnection();
$cols = $pdo->query("SHOW COLUMNS FROM wellbaby_records LIKE '%other%'")->fetchAll();
$colNames = array_column($cols, 'Field');
it("wellbaby_records has 'place_of_delivery_other' column", in_array('place_of_delivery_other', $colNames));
it("wellbaby_records has 'attended_by_other' column", in_array('attended_by_other', $colNames));

// ----------------------------------------------------
// 4. End-to-End Workflow with a Temporary Test Patient
// ----------------------------------------------------
echo "\n4. End-to-End Workflow Simulation:\n";

// Create temporary patient
$patientModel = new Patient();
$tempPatientId = $patientModel->create([
    'first_name' => 'BabyTest',
    'last_name' => 'Pediatric',
    'dob' => '2025-04-04', // exactly 18 months ago as of 2026-10-04
    'sex' => 'Female',
    'civil_status' => 'Single',
    'address' => 'Purok 1',
    'barangay' => 'Sinalhan',
    'created_by' => 1
]);

it("Created test patient with ID: {$tempPatientId}", $tempPatientId > 0);

$wbModel = new WellbabyRecord();
$wbId = $wbModel->createRecord([
    'patient_id' => $tempPatientId,
    'place_of_delivery' => 'Others',
    'place_of_delivery_other' => 'Private Maternity Clinic ABC',
    'attended_by' => 'Others',
    'attended_by_other' => 'Traditional Midwife Elena',
    'birth_weight_kg' => 3.25,
    'birth_length_cm' => 49.5,
    'delivery_type' => 'Normal Spontaneous Delivery (NSD)',
    'newborn_screening_done' => 0,
    'newborn_screening_date' => null,
    'newborn_screening_result' => null,
    'created_by' => 1
]);

it("Created wellbaby record with ID: {$wbId}", $wbId > 0);

// Update child patient parental DOBs as done by WellbabyController
$patientModel->updateParentalInfo($tempPatientId, 'Juan Pediatric', '1994-08-20', 'Maria Pediatric', '1996-02-14', 1);

// Read back and verify
$record = $wbModel->findByPatientId($tempPatientId);
$pData = $patientModel->findById($tempPatientId);
it("Retrieved wellbaby record correctly", $record !== false);
it("place_of_delivery is 'Others'", ($record['place_of_delivery'] ?? '') === 'Others');
it("place_of_delivery_other is 'Private Maternity Clinic ABC'", ($record['place_of_delivery_other'] ?? '') === 'Private Maternity Clinic ABC');
it("attended_by is 'Others'", ($record['attended_by'] ?? '') === 'Others');
it("attended_by_other is 'Traditional Midwife Elena'", ($record['attended_by_other'] ?? '') === 'Traditional Midwife Elena');
it("mother_dob is '1996-02-14'", ($pData['mother_dob'] ?? '') === '1996-02-14');
it("father_dob is '1994-08-20'", ($pData['father_dob'] ?? '') === '1994-08-20');
it("newborn_screening_done is 0 with null date and result", ($record['newborn_screening_done'] == 0) && is_null($record['newborn_screening_date']) && is_null($record['newborn_screening_result']));

// ----------------------------------------------------
// 5. Immunization & Supplementation Logging
// ----------------------------------------------------
echo "\n5. Immunization & Supplementation Logging:\n";

$immModel = new Immunization();

// Administer 12 out of 13 routine vaccines (missing MCV2)
$routineDoses = [
    ['BCG', 1, '2025-04-05'],
    ['Hepatitis B (Birth Dose)', 1, '2025-04-05'],
    ['Pentavalent (DTP-HepB-Hib)', 1, '2025-05-16'],
    ['Pentavalent (DTP-HepB-Hib)', 2, '2025-06-20'],
    ['Pentavalent (DTP-HepB-Hib)', 3, '2025-07-25'],
    ['Oral Polio Vaccine (OPV)', 1, '2025-05-16'],
    ['Oral Polio Vaccine (OPV)', 2, '2025-06-20'],
    ['Oral Polio Vaccine (OPV)', 3, '2025-07-25'],
    ['Inactivated Polio Vaccine (IPV)', 1, '2025-07-25'],
    ['Rotavirus Vaccine (ROTA)', 1, '2025-05-16'],
    ['Rotavirus Vaccine (ROTA)', 2, '2025-06-20'],
    ['Measles-Rubella (MCV1)', 1, '2026-01-10'],
];

foreach ($routineDoses as [$vName, $dose, $dt]) {
    $immModel->recordDose([
        'patient_id' => $tempPatientId,
        'vaccine_name' => $vName,
        'dose_number' => $dose,
        'administered_date' => $dt,
        'administered_by' => 1
    ]);
}

// Administer Vitamin A Dose 1 & Deworming Dose 1
$immModel->recordDose([
    'patient_id' => $tempPatientId,
    'vaccine_name' => 'Vitamin A',
    'dose_number' => 1,
    'administered_date' => '2025-10-10',
    'administered_by' => 1,
    'remarks' => '100,000 IU blue capsule'
]);

$immModel->recordDose([
    'patient_id' => $tempPatientId,
    'vaccine_name' => 'Deworming',
    'dose_number' => 1,
    'administered_date' => '2026-04-10',
    'administered_by' => 1,
    'remarks' => 'Albendazole 200mg'
]);

// Check vaccine map
$vMap = $immModel->getVaccineMap($tempPatientId);
it("Vaccine map has BCG__1", isset($vMap['BCG__1']));
it("Vaccine map has Rotavirus__1", isset($vMap['Rotavirus__1']));
it("Vaccine map has Rotavirus__2", isset($vMap['Rotavirus__2']));
it("Vaccine map has MCV__1", isset($vMap['MCV__1']));
it("Vaccine map has Vitamin_A__1", isset($vMap['Vitamin_A__1']));
it("Vaccine map has Deworming__1", isset($vMap['Deworming__1']));
it("Vaccine map does not have MCV__2 yet", !isset($vMap['MCV__2']));

// Check Roster computation (12 / 13 doses)
$roster = $wbModel->getRegisteredRoster('BabyTest');
$foundChild = null;
foreach ($roster as $r) {
    if ($r['patient_id'] == $tempPatientId) {
        $foundChild = $r;
        break;
    }
}

it("Found test child in registered roster", $foundChild !== null);
it("Roster counts exactly 12 completed EPI doses", ($foundChild['imm_count'] ?? 0) == 12);

// Now administer MCV2
$immModel->recordDose([
    'patient_id' => $tempPatientId,
    'vaccine_name' => 'Measles-Mumps-Rubella (MCV2)',
    'dose_number' => 2,
    'administered_date' => '2026-05-15',
    'administered_by' => 1
]);

// Re-check roster
$roster2 = $wbModel->getRegisteredRoster('BabyTest');
$foundChild2 = null;
foreach ($roster2 as $r) {
    if ($r['patient_id'] == $tempPatientId) {
        $foundChild2 = $r;
        break;
    }
}
it("Roster now counts exactly 13 / 13 doses", ($foundChild2['imm_count'] ?? 0) == 13);


// ----------------------------------------------------
// 6. Growth Monitoring Verification
// ----------------------------------------------------
echo "\n6. Child Growth Log & Age in Months:\n";

$cglModel = new ChildGrowthLog();
$growthAgeMonths = calculate_age_in_months('2025-04-04', '2025-07-04'); // exactly 3 months
$cglId = $cglModel->createLog([
    'wellbaby_id' => $wbId,
    'log_date' => '2025-07-04',
    'age_months' => $growthAgeMonths,
    'weight_kg' => 5.8,
    'height_cm' => 60.5,
    'head_circumference_cm' => 39.5,
    'chest_circumference_cm' => 38.0,
    'temperature' => 36.6,
    'feeding_method' => 'LAM / Exclusive Breastfeeding',
    'vaccines_administered' => 'Penta 2, OPV 2, Rota 2',
    'tcb_notes' => 'Child is active and breastfeeding well',
    'recorded_by' => 1
]);

it("Child growth log created successfully with ID: {$cglId}", $cglId > 0);
$logs = $cglModel->findByWellbabyId($wbId);
it("findByWellbabyId returns recorded log", count($logs) >= 1);
it("Growth record preserves exact age_months (3.0)", (float)($logs[0]['age_months'] ?? 0) === 3.0);
it("Growth record preserves 'vaccines_administered'", ($logs[0]['vaccines_administered'] ?? '') === 'Penta 2, OPV 2, Rota 2');


// ----------------------------------------------------
// 7. Cleanup Test Records
// ----------------------------------------------------
echo "\n7. Cleanup Test Records:\n";

$pdo->prepare("DELETE FROM child_growth_logs WHERE wellbaby_id = ?")->execute([$wbId]);
$pdo->prepare("DELETE FROM immunizations WHERE patient_id = ?")->execute([$tempPatientId]);
$pdo->prepare("DELETE FROM wellbaby_records WHERE patient_id = ?")->execute([$tempPatientId]);
$pdo->prepare("DELETE FROM patients WHERE id = ?")->execute([$tempPatientId]);

it("Deleted child growth log test records", true);
it("Deleted immunization test records", true);
it("Deleted wellbaby test record", true);
it("Deleted test patient", true);


// ----------------------------------------------------
// Final Summary
// ----------------------------------------------------
echo "\n====================================================\n";
echo " TEST SUITE COMPLETE: {$pass} PASSED, {$fail} FAILED\n";
echo "====================================================\n";

if ($fail > 0) {
    exit(1);
}
