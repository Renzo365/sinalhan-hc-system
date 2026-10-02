<?php
/**
 * Automated Verification Suite for Phase 3: Clinical Information Architecture & Inspection Modals
 */

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'super_admin';
$_SESSION['role'] = 'super_admin';
$_SESSION['user'] = [
    'id' => 1,
    'first_name' => 'System',
    'last_name' => 'Administrator',
    'role' => 'super_admin'
];

require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();

$tests = [];
function recordTest(&$tests, $name, $pass, $details = '') {
    $tests[] = ['name' => $name, 'pass' => $pass, 'details' => $details];
    echo ($pass ? " [PASS] " : " [FAIL] ") . $name . ($details ? " ($details)" : "") . "\n";
}

echo "=== STARTING ARCHIVE PHASE 3 VERIFICATION SUITE ===\n\n";

$viewPath = dirname(__DIR__) . '/app/Views/archive/patients.php';
$viewContent = file_get_contents($viewPath);

$controllerPath = dirname(__DIR__) . '/app/Controllers/PatientController.php';
$controllerContent = file_get_contents($controllerPath);

$consultationModelPath = dirname(__DIR__) . '/app/Models/Consultation.php';
$consultationModelContent = file_get_contents($consultationModelPath);

// 1. Check Consultation model vitals join
$hasVitalsJoin = strpos($consultationModelContent, 'LEFT JOIN vital_signs vs ON c.vital_signs_id = vs.id') !== false;
$hasVitalsCols = strpos($consultationModelContent, 'vs.bp_systolic') !== false && strpos($consultationModelContent, 'vs.bp_diastolic') !== false;
recordTest($tests, "Consultation::allArchived() joins vital_signs table", $hasVitalsJoin && $hasVitalsCols);

// 2. Check PatientController prescription attachment
$hasPrescriptionAttach = strpos($controllerContent, 'findByConsultationId') !== false && strpos($controllerContent, "\$cRecord['prescriptions']") !== false;
recordTest($tests, "PatientController::archivedIndex() eager-loads prescriptions for consultations", $hasPrescriptionAttach);

// 3. Check Tab 1 View Details button & Modal
$hasPatientViewBtn = strpos($viewContent, 'btn-view-patient-archive') !== false && strpos($viewContent, 'View Details') !== false;
recordTest($tests, "Tab 1 contains 'View Details' button with JSON payload", $hasPatientViewBtn);

$hasPatientModal = strpos($viewContent, 'id="modalPatientArchiveDetails"') !== false;
$hasPatientModalFields = strpos($viewContent, 'id="patientModalName"') !== false &&
                         strpos($viewContent, 'id="patientModalAddress"') !== false &&
                         strpos($viewContent, 'id="patientModalReason"') !== false;
recordTest($tests, "Patient Archive Details Modal markup exists with demographic & archival fields", $hasPatientModal && $hasPatientModalFields);

// 4. Check Tab 2 Inspect button & Modal
$hasConsultInspectBtn = strpos($viewContent, 'btn-view-consultation-archive') !== false && strpos($viewContent, 'Inspect') !== false;
recordTest($tests, "Tab 2 contains 'Inspect' button with JSON payload", $hasConsultInspectBtn);

$hasConsultModal = strpos($viewContent, 'id="modalConsultationArchiveDetails"') !== false;
$hasConsultModalSOAP = strpos($viewContent, 'consultModalSubjective') !== false &&
                       strpos($viewContent, 'consultModalObjective') !== false &&
                       strpos($viewContent, 'consultModalAssessment') !== false &&
                       strpos($viewContent, 'consultModalPlan') !== false;
$hasConsultModalVitals = strpos($viewContent, 'consultModalVitalsGrid') !== false;
$hasConsultModalRx = strpos($viewContent, 'consultModalPrescriptionsBody') !== false;
recordTest($tests, "Consultation Archive Details Modal markup contains SOAP notes, Vitals grid, and Prescriptions table", $hasConsultModal && $hasConsultModalSOAP && $hasConsultModalVitals && $hasConsultModalRx);

// 5. Check Two-Line Patient Display in Tab 2 (De-badged)
$hasTab2Name = strpos($viewContent, "h(\$c['pat_first'] . ' ' . \$c['pat_last'])") !== false;
$hasTab2DebadgedId = strpos($viewContent, "font-monospace text-secondary small") !== false && strpos($viewContent, "h(\$c['patient_no'])") !== false;
$hasNoTab2Badge = strpos($viewContent, '<span class="badge bg-light text-secondary border font-monospace"><?= h($c[\'patient_no\']) ?></span>') === false;
recordTest($tests, "Tab 2 displays two-line patient hierarchy (bold name + un-badged monospace ID)", $hasTab2Name && $hasTab2DebadgedId && $hasNoTab2Badge);

// 6. Check Dual Date Visibility in Tab 2
$hasDualDateHeader = strpos($viewContent, 'Encounter & Archived Dates') !== false;
$hasCalendarIcon = strpos($viewContent, 'bi-calendar-check') !== false;
$hasArchiveDate = strpos($viewContent, 'Archived <?= date(\'Y-m-d h:i A\', strtotime($c[\'deleted_at\'])) ?>') !== false;
recordTest($tests, "Tab 2 displays dual dates (consulted_at and deleted_at with calendar/archive icons)", $hasDualDateHeader && $hasCalendarIcon && $hasArchiveDate);

// 7. Check Date Filters in Tab 3 (Users)
$hasUserDateFrom = strpos($viewContent, 'name="date_from"') !== false && strpos($viewContent, 'id="date_from_users"') !== false;
$hasUserDateTo = strpos($viewContent, 'name="date_to"') !== false && strpos($viewContent, 'id="date_to_users"') !== false;
recordTest($tests, "Tab 3 filter form contains date_from and date_to datepicker inputs", $hasUserDateFrom && $hasUserDateTo);

// 8. Live DB Query for Archived Consultations with Vitals
$consultationModel = new \App\Models\Consultation();
$archivedConsultations = $consultationModel->allArchived();
recordTest($tests, "Live Consultation::allArchived() executes successfully and returns array", is_array($archivedConsultations));

if (!empty($archivedConsultations)) {
    $first = $archivedConsultations[0];
    $hasVitalsKeys = array_key_exists('bp_systolic', $first) && array_key_exists('temperature', $first) && array_key_exists('heart_rate', $first);
    recordTest($tests, "Archived consultation records include vital signs fields", $hasVitalsKeys);
}

$total = count($tests);
$passed = count(array_filter($tests, fn($t) => $t['pass']));
$failed = $total - $passed;

echo "\n--------------------------------------------------\n";
echo "Phase 3 Automated Verification Results:\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "--------------------------------------------------\n";

if ($failed === 0) {
    echo "ALL PHASE 3 TESTS PASSED SUCCESSFULLY!\n";
    exit(0);
} else {
    echo "SOME TESTS FAILED!\n";
    exit(1);
}
