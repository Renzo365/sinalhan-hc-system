<?php

// Check if run from CLI
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once dirname(__DIR__) . '/app/helpers.php';

$dbConfig = require dirname(__DIR__) . '/config/database.php';

use App\Models\Patient;
use App\Models\PatientMedicalHistory;
use App\Models\PrenatalRecord;
use App\Models\PrenatalVisit;
use App\Models\PastObstetricHistory;
use App\Models\WellbabyRecord;
use App\Models\ChildGrowthLog;
use App\Models\Immunization;
use App\Models\QueueEntry;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\VitalSigns;
use App\Controllers\QueueController;
use App\Controllers\AppointmentController;
use App\Controllers\ConsultationController;
use App\Controllers\ReportController;
use App\Controllers\BackupController;

echo "\n=======================================================\n";
echo "   PHASE 6 CROSS-MODULE INTEGRATION & AUDIT TEST SUITE \n";
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
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; DELETE FROM patients WHERE first_name LIKE 'TestP6%'; SET FOREIGN_KEY_CHECKS = 1;");

    $patientModel = new Patient();
    $historyModel = new PatientMedicalHistory();
    $prenatalModel = new PrenatalRecord();
    $visitModel = new PrenatalVisit();
    $wbModel = new WellbabyRecord();
    $growthModel = new ChildGrowthLog();
    $immModel = new Immunization();
    $queueModel = new QueueEntry();
    $apptModel = new Appointment();
    $consultModel = new Consultation();
    $vitalsModel = new VitalSigns();

    // -------------------------------------------------------------
    // 1. End-to-End Registration: Family Cluster
    // -------------------------------------------------------------
    $familyNo = 'FAM-TEST-P6-AUDIT';

    // A. Mother
    $motherId = $patientModel->create([
        'first_name' => 'TestP6Mother',
        'middle_name' => 'Santos',
        'last_name' => 'Reyes',
        'dob' => '1998-05-15',
        'sex' => 'Female',
        'civil_status' => 'Married',
        'blood_type' => 'O+',
        'religion' => 'Roman Catholic',
        'family_no' => $familyNo,
        'contact_no' => '09181112233',
        'barangay' => 'Sinalhan',
        'address' => 'Purok 2 Lakeside',
        'philhealth_status' => 'Member',
        'philhealth_no' => '12-998877665-1',
        'created_by' => 1
    ]);
    assertTest($motherId > 0, "Registered Mother in Family ($familyNo, ID: $motherId)");

    // B. Father
    $fatherId = $patientModel->create([
        'first_name' => 'TestP6Father',
        'middle_name' => 'Gomez',
        'last_name' => 'Reyes',
        'dob' => '1995-02-20',
        'sex' => 'Male',
        'civil_status' => 'Married',
        'blood_type' => 'O+',
        'religion' => 'Roman Catholic',
        'family_no' => $familyNo,
        'contact_no' => '09181112244',
        'barangay' => 'Sinalhan',
        'address' => 'Purok 2 Lakeside',
        'created_by' => 1
    ]);
    assertTest($fatherId > 0, "Registered Father in Family ($familyNo, ID: $fatherId)");

    // C. Infant
    $infantDob = date('Y-m-d', strtotime('-3 months'));
    $infantId = $patientModel->create([
        'first_name' => 'TestP6Infant',
        'middle_name' => 'Santos',
        'last_name' => 'Reyes',
        'suffix' => 'Jr.',
        'dob' => $infantDob,
        'sex' => 'Male',
        'civil_status' => 'Single',
        'blood_type' => 'O+',
        'religion' => 'Roman Catholic',
        'family_no' => $familyNo,
        'barangay' => 'Sinalhan',
        'address' => 'Purok 2 Lakeside',
        'mother_name' => 'TestP6Mother Reyes',
        'created_by' => 1
    ]);
    assertTest($infantId > 0, "Registered Infant in Family ($familyNo, ID: $infantId)");

    // Verify Household clustering
    $familyMembers = $patientModel->familyMembers($familyNo, 0);
    assertTest(count($familyMembers) === 3, "Household directory search linked all 3 family members under $familyNo");

    // -------------------------------------------------------------
    // 2. IHP Medical History & Allergy Alert Baseline
    // -------------------------------------------------------------
    $historySaved = $historyModel->saveHistory($motherId, [
        'past_medical_history' => [
            'Allergy' => 'Penicillin, Amoxicillin, Shellfish',
            'Hypertension' => 'Highest BP: 160/100',
            'Asthma' => 'Mild intermittent'
        ],
        'smoking_status' => 'Never',
        'alcohol_status' => 'Never'
    ], 1);
    assertTest($historySaved === true, "Recorded Mother IHP Medical History with Allergies & Hypertension");

    // -------------------------------------------------------------
    // 3. Maternal Care Enrollment (Prenatal Episode)
    // -------------------------------------------------------------
    $lmp = date('Y-m-d', strtotime('-16 weeks'));
    $edc = date('Y-m-d', strtotime($lmp . ' +280 days'));
    $prenatalId = $prenatalModel->createEpisode([
        'patient_id' => $motherId,
        'gravida' => 2,
        'para' => 1,
        'term_births' => 1,
        'preterm_births' => 0,
        'abortions' => 0,
        'living_children' => 1,
        'lmp' => $lmp,
        'edc' => $edc,
        'pre_eclampsia' => 1,
        'notes' => 'History of gestational hypertension',
        'created_by' => 1
    ]);
    assertTest($prenatalId > 0, "Enrolled Mother in Active Prenatal Episode #$prenatalId (EDC: $edc, Pre-Eclampsia High Risk)");

    // -------------------------------------------------------------
    // 4. Well Baby & EPI Immunization Registration
    // -------------------------------------------------------------
    $wbId = $wbModel->createRecord([
        'patient_id' => $infantId,
        'mother_patient_id' => $motherId,
        'birth_time' => '06:15:00',
        'birth_weight_kg' => 3.25,
        'birth_length_cm' => 50.0,
        'place_of_delivery' => 'Barangay Sinalhan Birthing Station',
        'delivery_type' => 'Normal Spontaneous Delivery (NSD)',
        'attended_by' => 'Midwife Ramos',
        'newborn_screening_done' => 1,
        'newborn_screening_date' => date('Y-m-d', strtotime($infantDob . ' +2 days')),
        'newborn_screening_result' => 'Normal (Cert # NBS-P6-12345)',
        'mother_cpab_tt' => 'TT3 Complete',
        'feeding_method' => 'LAM / Exclusive Breastfeeding',
        'created_by' => 1
    ]);
    assertTest($wbId > 0, "Enrolled Infant in Well Baby Care (Birth Wt: 3.25kg, NBS Cert # NBS-P6-12345)");

    // Record Routine Vaccines (BCG, Hep B, Penta 1, OPV 1)
    $immModel->recordDose([
        'patient_id' => $infantId,
        'vaccine_name' => 'BCG',
        'dose_number' => 1,
        'administered_date' => $infantDob,
        'remarks' => 'At birth',
        'administered_by' => 1
    ]);
    $immModel->recordDose([
        'patient_id' => $infantId,
        'vaccine_name' => 'Hepatitis B',
        'dose_number' => 1,
        'administered_date' => $infantDob,
        'remarks' => 'Within 24 hours',
        'administered_by' => 1
    ]);
    $pentaDate = date('Y-m-d', strtotime($infantDob . ' +6 weeks'));
    $immModel->recordDose([
        'patient_id' => $infantId,
        'vaccine_name' => 'Pentavalent',
        'dose_number' => 1,
        'administered_date' => $pentaDate,
        'remarks' => 'EPI Dose 1',
        'administered_by' => 1
    ]);
    $immModel->recordDose([
        'patient_id' => $infantId,
        'vaccine_name' => 'OPV',
        'dose_number' => 1,
        'administered_date' => $pentaDate,
        'remarks' => 'EPI Dose 1',
        'administered_by' => 1
    ]);
    assertTest(count($immModel->findByPatientId($infantId)) === 4, "Recorded 4 EPI Childhood Vaccines for Infant");

    // -------------------------------------------------------------
    // 5. Queue Management: Service Program Tagging
    // -------------------------------------------------------------
    $queueMotherId = $queueModel->create([
        'patient_id' => $motherId,
        'service_type' => 'Prenatal Care',
        'created_by' => 1
    ]);
    assertTest($queueMotherId > 0, "Enqueued Mother for 'Prenatal Care' (Queue Entry ID: $queueMotherId)");

    $queueInfantId = $queueModel->create([
        'patient_id' => $infantId,
        'service_type' => 'Well Baby Immunization',
        'created_by' => 1
    ]);
    assertTest($queueInfantId > 0, "Enqueued Infant for 'Well Baby Immunization' (Queue Entry ID: $queueInfantId)");

    // Check Public Display Data
    $displayData = $queueModel->getPublicDisplayData();
    assertTest(isset($displayData['waiting']) && count($displayData['waiting']) >= 2, "Public display feed contains waiting queue tickets");

    // Transition Mother Queue Status: Waiting -> Called -> Serving
    $queueModel->updateStatus($queueMotherId, 'Called', 1);
    $queueModel->updateStatus($queueMotherId, 'Serving', 1);
    $servingData = $queueModel->getPublicDisplayData();
    assertTest($servingData['serving_service'] === 'Prenatal Care', "Public Display Monitor identifies Serving Service as 'Prenatal Care'");

    // -------------------------------------------------------------
    // 6. Appointment Scheduling with Clinical Program Link
    // -------------------------------------------------------------
    $nextWeek = date('Y-m-d', strtotime('+7 days'));
    $apptMotherId = $apptModel->create([
        'patient_id' => $motherId,
        'appointment_date' => $nextWeek,
        'appointment_time' => '09:00:00',
        'purpose' => 'Prenatal Follow-up',
        'program_type' => 'Prenatal Care',
        'notes' => 'Second trimester prenatal checkup & BP monitoring',
        'created_by' => 1
    ]);
    assertTest($apptMotherId > 0, "Scheduled Mother Appointment with Program 'Prenatal Care' (ID: $apptMotherId)");

    $apptInfantId = $apptModel->create([
        'patient_id' => $infantId,
        'appointment_date' => $nextWeek,
        'appointment_time' => '10:00:00',
        'purpose' => 'Routine EPI Vaccine',
        'program_type' => 'Well Baby Immunization',
        'notes' => 'Pentavalent 2 and OPV 2 vaccination',
        'created_by' => 1
    ]);
    assertTest($apptInfantId > 0, "Scheduled Infant Appointment with Program 'Well Baby Immunization' (ID: $apptInfantId)");

    // -------------------------------------------------------------
    // 7. Clinical SOAP Consultation & Safety Alert Verification
    // -------------------------------------------------------------
    $vitalsId = $vitalsModel->create([
        'patient_id' => $motherId,
        'blood_pressure_systolic' => 145,
        'blood_pressure_diastolic' => 95,
        'heart_rate' => 82,
        'temperature' => 36.8,
        'respiratory_rate' => 18,
        'oxygen_saturation' => 99,
        'weight_kg' => 64.0,
        'height_cm' => 158.0,
        'recorded_by' => 1
    ]);
    assertTest($vitalsId > 0, "Recorded Vital Signs with Elevated BP (145/95 mmHg, ID: $vitalsId)");

    $consultId = $consultModel->create([
        'patient_id' => $motherId,
        'vital_signs_id' => $vitalsId,
        'subjective' => 'Patient reports mild morning headaches, feeling fetal movements.',
        'objective' => 'BP 145/95 mmHg, FHT 148 bpm, clear breath sounds, mild pedal edema.',
        'assessment' => 'G2P1 16 wks AOG; Gestational Hypertension / High-Risk Pre-Eclampsia.',
        'plan' => 'Prescribed Methyldopa 250mg BID. Avoided penicillin-group antibiotics. Scheduled follow-up next week.',
        'status' => 'Completed',
        'consulted_by' => 1,
        'consulted_at' => date('Y-m-d H:i:s'),
        'created_by' => 1
    ]);
    assertTest($consultId > 0, "Logged SOAP Consultation with Safety Decisions for Pre-Eclampsia (ID: $consultId)");

    // Complete Mother Queue Ticket
    $queueModel->updateStatus($queueMotherId, 'Completed', 1);
    $motherQueueRow = $queueModel->findById($queueMotherId);
    assertTest($motherQueueRow['status'] === 'Completed', "Completed Mother Queue Ticket");

    // -------------------------------------------------------------
    // 8. Reports & Analytics Verification
    // -------------------------------------------------------------
    $reportController = new ReportController();
    $reflection = new ReflectionClass($reportController);
    $queryMethod = $reflection->getMethod('queryReportData');
    $queryMethod->setAccessible(true);

    // A. Maternal Health Report
    $maternalReport = $queryMethod->invoke($reportController, 'maternal_health', date('Y-m-01'), date('Y-m-d', strtotime('+1 year')));
    assertTest(count($maternalReport) >= 1, "Maternal Health Registry Report returned active pregnancies");
    $foundMotherReport = false;
    foreach ($maternalReport as $mr) {
        if ((int)$mr['patient_id'] === $motherId && (int)$mr['pre_eclampsia'] === 1) {
            $foundMotherReport = true;
            break;
        }
    }
    assertTest($foundMotherReport === true, "Mother identified in Maternal Health Report with Pre-Eclampsia High-Risk flag");

    // B. EPI Vaccine Coverage Report
    $epiReport = $queryMethod->invoke($reportController, 'epi_coverage', date('Y-m-01'), date('Y-m-d'));
    assertTest(count($epiReport) >= 1, "EPI Childhood Immunization Coverage Report returned registered infants");
    $foundInfantEPI = false;
    foreach ($epiReport as $er) {
        if ((int)$er['patient_id'] === $infantId && !empty($er['bcg_date']) && !empty($er['penta1_date'])) {
            $foundInfantEPI = true;
            break;
        }
    }
    assertTest($foundInfantEPI === true, "Infant found in EPI Coverage Report with BCG and Pentavalent 1 dates");

    // C. Morbidity / Chronic Disease Registry
    $morbidityReport = $queryMethod->invoke($reportController, 'chronic_morbidity', date('Y-m-01'), date('Y-m-d'));
    assertTest(count($morbidityReport) >= 1, "Chronic Morbidity Registry Report returned IHP disease cases");
    $foundMorbidity = false;
    foreach ($morbidityReport as $mbr) {
        if ((int)$mbr['patient_id'] === $motherId) {
            $pmh = is_array($mbr['past_medical_history']) ? $mbr['past_medical_history'] : (json_decode($mbr['past_medical_history'] ?? '[]', true) ?: []);
            if (isset($pmh['Hypertension']) && isset($pmh['Allergy'])) {
                $foundMorbidity = true;
                break;
            }
        }
    }
    assertTest($foundMorbidity === true, "Mother found in Morbidity Registry with diagnosed Hypertension and Allergy list");

    // -------------------------------------------------------------
    // 9. Database Backup Engine Verification
    // -------------------------------------------------------------
    $backupController = new BackupController();
    $backupDir = dirname(__DIR__) . '/storage/backups';
    
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'admin';
    $_SESSION['user_name'] = 'Admin';
    $generatedBackupFile = $backupController->generateBackupSql();
    assertTest(!empty($generatedBackupFile) && file_exists($generatedBackupFile), "Generated database backup SQL dump in storage/backups/");

    // Check generated SQL files
    $backupFiles = glob($backupDir . '/*.sql');
    assertTest(!empty($backupFiles), "Generated database backup SQL dump in storage/backups/");
    
    if (!empty($backupFiles)) {
        usort($backupFiles, function($a, $b) { return filemtime($b) - filemtime($a); });
        $latestBackup = file_get_contents($backupFiles[0]);
        
        $hasIHPTable = strpos($latestBackup, 'patient_medical_histories') !== false;
        $hasPrenatalTable = strpos($latestBackup, 'prenatal_records') !== false;
        $hasPrenatalVisits = strpos($latestBackup, 'prenatal_visits') !== false;
        $hasObstetric = strpos($latestBackup, 'past_obstetric_histories') !== false;
        $hasWellbaby = strpos($latestBackup, 'wellbaby_records') !== false;
        $hasGrowth = strpos($latestBackup, 'child_growth_logs') !== false;
        $hasImmunization = strpos($latestBackup, 'immunizations') !== false;

        assertTest($hasIHPTable && $hasPrenatalTable && $hasPrenatalVisits && $hasObstetric && $hasWellbaby && $hasGrowth && $hasImmunization, "SQL backup file contains all 7 clinical tables (IHP, Prenatal, Visits, Obstetric, Wellbaby, Growth, Immunizations)");
    }

    // -------------------------------------------------------------
    // 10. Clean Up Test Records
    // -------------------------------------------------------------
    echo "\nCleaning up Phase 6 audit test records...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; DELETE FROM patients WHERE id IN ($motherId, $fatherId, $infantId); SET FOREIGN_KEY_CHECKS = 1;");
    echo "  [+] Cleaned up test family records and all cascaded clinical data.\n";

} catch (Exception $e) {
    echo "\n[ERROR]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $failCount++;
}

echo "\n=======================================================\n";
echo "PHASE 6 TEST RESULTS: $passCount PASSED, $failCount FAILED\n";
echo "=======================================================\n\n";

exit($failCount > 0 ? 1 : 0);
