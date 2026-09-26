<?php
/**
 * Post-Seeding System Integrity Verification Script
 */

$dbConfig = require dirname(__DIR__) . '/config/database.php';
$pdo = new PDO(
    "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}",
    $dbConfig['username'],
    $dbConfig['password'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);

echo "====================================================================\n";
echo " SYSTEM INTEGRITY AND DATA CONSISTENCY VERIFICATION\n";
echo "====================================================================\n\n";

$passes = 0;
$fails = 0;

function assertCondition($name, $condition, $details = '') {
    global $passes, $fails;
    if ($condition) {
        $passes++;
        echo " [PASS] {$name}\n";
        if ($details) echo "        -> {$details}\n";
    } else {
        $fails++;
        echo " [FAIL] {$name}\n";
        if ($details) echo "        -> ERROR: {$details}\n";
    }
}

// -------------------------------------------------------------
// 1. Existing User Accounts & Passwords
// -------------------------------------------------------------
echo "1. Checking User Accounts...\n";
$users = $pdo->query("SELECT id, username, role, password_hash, status FROM users ORDER BY id")->fetchAll();
assertCondition("Total User Accounts count is 5", count($users) === 5, "Found " . count($users) . " accounts");

$expectedUsers = ['admin', 'records_staff', 'midwife_user', 'ralph', 'jdoe'];
$actualUsernames = array_column($users, 'username');
assertCondition("All specific user accounts exist", empty(array_diff($expectedUsers, $actualUsernames)), implode(', ', $actualUsernames));

// Verify password hashes are valid bcrypt strings
$validHashes = true;
foreach ($users as $u) {
    if (strpos($u['password_hash'], '$2y$') !== 0 && strpos($u['password_hash'], '$2a$') !== 0) {
        $validHashes = false;
        break;
    }
}
assertCondition("Password hashes are valid bcrypt strings", $validHashes);

// -------------------------------------------------------------
// 2. Patient Counts & Directory Visibility
// -------------------------------------------------------------
echo "\n2. Checking Patient Records...\n";
$totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$activePatients = $pdo->query("SELECT COUNT(*) FROM patients WHERE deleted_at IS NULL")->fetchColumn();
$archivedPatients = $pdo->query("SELECT COUNT(*) FROM patients WHERE deleted_at IS NOT NULL")->fetchColumn();
assertCondition("Patient Count >= 300 (Total: {$totalPatients})", $totalPatients >= 300);
assertCondition("Active Patients: {$activePatients}, Archived: {$archivedPatients}", $activePatients >= 290 && $archivedPatients > 0);

// Check Patient No format and uniqueness
$uniquePatientNos = $pdo->query("SELECT COUNT(DISTINCT patient_no) FROM patients")->fetchColumn();
assertCondition("All Patient Numbers are Unique ({$uniquePatientNos} unique)", $uniquePatientNos == $totalPatients);

$invalidPatientNos = $pdo->query("SELECT COUNT(*) FROM patients WHERE patient_no NOT REGEXP '^P-[0-9]{4}-[0-9]{5}$'")->fetchColumn();
assertCondition("All Patient Numbers match 'P-YYYY-XXXXX' format", $invalidPatientNos == 0, "Found {$invalidPatientNos} invalid");

// Check PhilHealth No uniqueness among non-nulls
$totalPhic = $pdo->query("SELECT COUNT(philhealth_no) FROM patients WHERE philhealth_no IS NOT NULL")->fetchColumn();
$uniquePhic = $pdo->query("SELECT COUNT(DISTINCT philhealth_no) FROM patients WHERE philhealth_no IS NOT NULL")->fetchColumn();
assertCondition("All PhilHealth numbers are Unique ({$uniquePhic} unique / {$totalPhic} assigned)", $totalPhic == $uniquePhic);

// -------------------------------------------------------------
// 3. Foreign Key & Orphan Checks
// -------------------------------------------------------------
echo "\n3. Checking Foreign Key & Orphan Integrity...\n";

// Vitals orphan check
$orphanVitals = $pdo->query("SELECT COUNT(*) FROM vital_signs v LEFT JOIN patients p ON v.patient_id = p.id WHERE p.id IS NULL")->fetchColumn();
assertCondition("Vital signs have zero orphaned records", $orphanVitals == 0, "Orphans: {$orphanVitals}");

// Consultations orphan check
$orphanConsult = $pdo->query("SELECT COUNT(*) FROM consultations c LEFT JOIN patients p ON c.patient_id = p.id WHERE p.id IS NULL")->fetchColumn();
assertCondition("Consultations have zero orphaned patient references", $orphanConsult == 0, "Orphans: {$orphanConsult}");

$orphanConsultVitals = $pdo->query("SELECT COUNT(*) FROM consultations c LEFT JOIN vital_signs v ON c.vital_signs_id = v.id WHERE c.vital_signs_id IS NOT NULL AND v.id IS NULL")->fetchColumn();
assertCondition("Consultations have zero orphaned vital signs references", $orphanConsultVitals == 0, "Orphans: {$orphanConsultVitals}");

// Prescriptions orphan check
$orphanRx = $pdo->query("SELECT COUNT(*) FROM prescriptions rx LEFT JOIN consultations c ON rx.consultation_id = c.id WHERE c.id IS NULL")->fetchColumn();
assertCondition("Prescriptions have zero orphaned consultation references", $orphanRx == 0, "Orphans: {$orphanRx}");

// Immunizations orphan check
$orphanImm = $pdo->query("SELECT COUNT(*) FROM immunizations i LEFT JOIN patients p ON i.patient_id = p.id WHERE p.id IS NULL")->fetchColumn();
assertCondition("Immunizations have zero orphaned records", $orphanImm == 0, "Orphans: {$orphanImm}");

// Appointments orphan check
$orphanAppt = $pdo->query("SELECT COUNT(*) FROM appointments a LEFT JOIN patients p ON a.patient_id = p.id WHERE p.id IS NULL")->fetchColumn();
assertCondition("Appointments have zero orphaned records", $orphanAppt == 0, "Orphans: {$orphanAppt}");

// Queue entries orphan check
$orphanQueue = $pdo->query("SELECT COUNT(*) FROM queue_entries q LEFT JOIN patients p ON q.patient_id = p.id WHERE p.id IS NULL")->fetchColumn();
assertCondition("Queue entries have zero orphaned records", $orphanQueue == 0, "Orphans: {$orphanQueue}");

// Wellbaby orphan check
$orphanWb = $pdo->query("SELECT COUNT(*) FROM wellbaby_records wb LEFT JOIN patients p ON wb.patient_id = p.id WHERE p.id IS NULL")->fetchColumn();
assertCondition("Well-Baby records have zero orphaned patient records", $orphanWb == 0, "Orphans: {$orphanWb}");

// Child growth logs orphan check
$orphanCgl = $pdo->query("SELECT COUNT(*) FROM child_growth_logs cgl LEFT JOIN wellbaby_records wb ON cgl.wellbaby_id = wb.id WHERE wb.id IS NULL")->fetchColumn();
assertCondition("Child Growth logs have zero orphaned wellbaby records", $orphanCgl == 0, "Orphans: {$orphanCgl}");

// Prenatal records orphan check
$orphanPr = $pdo->query("SELECT COUNT(*) FROM prenatal_records pr LEFT JOIN patients p ON pr.patient_id = p.id WHERE p.id IS NULL")->fetchColumn();
assertCondition("Prenatal records have zero orphaned patient records", $orphanPr == 0, "Orphans: {$orphanPr}");

// Prenatal visits orphan check
$orphanPv = $pdo->query("SELECT COUNT(*) FROM prenatal_visits pv LEFT JOIN prenatal_records pr ON pv.prenatal_id = pr.id WHERE pr.id IS NULL")->fetchColumn();
assertCondition("Prenatal visits have zero orphaned prenatal records", $orphanPv == 0, "Orphans: {$orphanPv}");

// Check user attribution validity across all clinical tables
$invalidUserAttribution = $pdo->query("
    SELECT (
        (SELECT COUNT(*) FROM patients p LEFT JOIN users u ON p.created_by = u.id WHERE u.id IS NULL) +
        (SELECT COUNT(*) FROM vital_signs v LEFT JOIN users u ON v.recorded_by = u.id WHERE u.id IS NULL) +
        (SELECT COUNT(*) FROM consultations c LEFT JOIN users u ON c.consulted_by = u.id WHERE u.id IS NULL) +
        (SELECT COUNT(*) FROM prescriptions rx LEFT JOIN users u ON rx.prescribed_by = u.id WHERE u.id IS NULL) +
        (SELECT COUNT(*) FROM immunizations i LEFT JOIN users u ON i.administered_by = u.id WHERE u.id IS NULL) +
        (SELECT COUNT(*) FROM appointments a LEFT JOIN users u ON a.created_by = u.id WHERE u.id IS NULL) +
        (SELECT COUNT(*) FROM queue_entries q LEFT JOIN users u ON q.created_by = u.id WHERE u.id IS NULL)
    ) AS total_invalid
")->fetchColumn();
assertCondition("All clinical records reference valid existing user accounts", $invalidUserAttribution == 0, "Invalid references: {$invalidUserAttribution}");

// -------------------------------------------------------------
// 4. Clinical & Demographic Consistency
// -------------------------------------------------------------
echo "\n4. Checking Demographic and Clinical Consistency...\n";

// Future DOB check
$futureDobs = $pdo->query("SELECT COUNT(*) FROM patients WHERE dob > CURRENT_DATE()")->fetchColumn();
assertCondition("Zero patients with future Date of Birth", $futureDobs == 0);

// Maternal Biological Guard check
$malePrenatal = $pdo->query("SELECT COUNT(*) FROM prenatal_records pr INNER JOIN patients p ON pr.patient_id = p.id WHERE p.sex != 'Female'")->fetchColumn();
assertCondition("Prenatal records strictly belong to Female patients only", $malePrenatal == 0, "Found {$malePrenatal} non-female");

// Well-Baby Age Check (<= 5 years)
$overageWellbaby = $pdo->query("SELECT COUNT(*) FROM wellbaby_records wb INNER JOIN patients p ON wb.patient_id = p.id WHERE TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) > 5")->fetchColumn();
assertCondition("Well-baby records strictly belong to children <= 5 years old", $overageWellbaby == 0, "Found {$overageWellbaby} overage");

// Well-Baby Mother link check
$invalidMothers = $pdo->query("SELECT COUNT(*) FROM wellbaby_records wb LEFT JOIN patients m ON wb.mother_patient_id = m.id WHERE wb.mother_patient_id IS NOT NULL AND (m.id IS NULL OR m.sex != 'Female')")->fetchColumn();
assertCondition("Well-Baby mother links reference valid Female patients", $invalidMothers == 0, "Invalid mothers: {$invalidMothers}");

// Queue date and counter consistency
$todayStr = '2026-09-24';
$todayQueueCount = $pdo->query("SELECT COUNT(*) FROM queue_entries WHERE queue_date = '{$todayStr}'")->fetchColumn();
$counterToday = $pdo->query("SELECT last_queue_no FROM queue_daily_counters WHERE queue_date = '{$todayStr}'")->fetchColumn();
assertCondition("Queue entries count matches queue daily counter ({$todayQueueCount} entries, counter = {$counterToday})", $todayQueueCount == $counterToday);

// -------------------------------------------------------------
// 5. Test Models & Queries Execution
// -------------------------------------------------------------
echo "\n5. Testing Model Queries & System Modules...\n";

// Autoload application classes
require_once dirname(__DIR__) . '/app/Core/Model.php';
require_once dirname(__DIR__) . '/app/Core/Database.php';
require_once dirname(__DIR__) . '/app/Models/Patient.php';
require_once dirname(__DIR__) . '/app/Models/Consultation.php';
require_once dirname(__DIR__) . '/app/Models/Appointment.php';
require_once dirname(__DIR__) . '/app/Models/QueueEntry.php';
require_once dirname(__DIR__) . '/app/Models/VitalSigns.php';
require_once dirname(__DIR__) . '/app/Models/Prescription.php';

$patientModel = new \App\Models\Patient();

// Test allActive with filters
$allActive = $patientModel->allActive();
assertCondition("Patient::allActive() successfully loads active dataset (" . count($allActive) . " records)", count($allActive) >= 300);

// Test Search by Name
$searchResults = $patientModel->allActive(['search' => 'Santos']);
assertCondition("Patient::allActive(['search' => 'Santos']) returns matches (" . count($searchResults) . " found)", count($searchResults) > 0);

// Test Filter by Barangay
$barangayResults = $patientModel->allActive(['barangay' => 'Sinalhan']);
assertCondition("Patient::allActive(['barangay' => 'Sinalhan']) filter works (" . count($barangayResults) . " found)", count($barangayResults) > 0);

// Test Filter by Sex
$maleResults = $patientModel->allActive(['sex' => 'Male']);
$femaleResults = $patientModel->allActive(['sex' => 'Female']);
assertCondition("Patient::allActive() Sex filters work (Male: " . count($maleResults) . ", Female: " . count($femaleResults) . ")", count($maleResults) > 0 && count($femaleResults) > 0);

// Test Filter by Age Group
$infants = $patientModel->allActive(['age_group' => 'infant']);
$seniors = $patientModel->allActive(['age_group' => 'senior']);
$adults = $patientModel->allActive(['age_group' => 'adult']);
assertCondition("Patient::allActive() Age group filters work (Infants: " . count($infants) . ", Adults: " . count($adults) . ", Seniors: " . count($seniors) . ")", count($infants) > 0 && count($seniors) > 0);

// Test Consultation Model
$consultModel = new \App\Models\Consultation();
$firstConsult = $consultModel->findById(1);
assertCondition("Consultation::findById(1) returns clinical consultation with joined data", $firstConsult !== false && !empty($firstConsult['assessment']));

$patientConsults = $consultModel->findByPatientId($firstConsult['patient_id']);
assertCondition("Consultation::findByPatientId({$firstConsult['patient_id']}) returns consultation list (" . count($patientConsults) . " records)", count($patientConsults) > 0);

// Test Prescription Model
$rxModel = new \App\Models\Prescription();
$sampleConsultId = 1;
$rxs = $rxModel->findByConsultationId($sampleConsultId);
assertCondition("Prescription::findByConsultationId({$sampleConsultId}) successfully fetches structured medications (" . count($rxs) . " meds)", count($rxs) > 0);

// Test Queue Model
$queueModel = new \App\Models\QueueEntry();
$todayQueue = $queueModel->findAllToday();
assertCondition("QueueEntry::findAllToday() retrieves active operational queue (" . count($todayQueue) . " entries)", count($todayQueue) > 0);

// Test Appointment Model
$apptModel = new \App\Models\Appointment();
$allAppts = $apptModel->findAll();
assertCondition("Appointment::findAll() retrieves appointments (" . count($allAppts) . " records)", count($allAppts) > 0);

// Test Archived Hub
$archivedRecords = $patientModel->allArchived();
assertCondition("Patient::allArchived() returns soft-deleted patient charts (" . count($archivedRecords) . " records)", count($archivedRecords) > 0);

echo "\n====================================================================\n";
echo " VERIFICATION SUMMARY: {$passes} PASSED, {$fails} FAILED\n";
echo "====================================================================\n";

if ($fails > 0) {
    exit(1);
} else {
    exit(0);
}
