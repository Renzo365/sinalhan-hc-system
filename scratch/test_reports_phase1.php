<?php
// Test Suite for Reports Phase 1: Clinical Legibility, Tooltips & Print Engine

echo "=== PHASE 1 TEST SUITE: CLINICAL LEGIBILITY, TOOLTIPS & PRINT ENGINE ===\n\n";

$passed = 0;
$failed = 0;

function assertCondition($cond, $desc) {
    global $passed, $failed;
    if ($cond) {
        echo " [PASS] {$desc}\n";
        $passed++;
    } else {
        echo " [FAIL] {$desc}\n";
        $failed++;
    }
}

// 1. Syntax check
$phpLint = shell_exec('php -l app/Controllers/ReportController.php');
assertCondition(strpos($phpLint, 'No syntax errors detected') !== false, "Syntax check: ReportController.php");

$phpLint2 = shell_exec('php -l app/Views/reports/index.php');
assertCondition(strpos($phpLint2, 'No syntax errors detected') !== false, "Syntax check: reports/index.php");

// 2. Inspect ReportController.php
$controllerCode = file_get_contents('app/Controllers/ReportController.php');
assertCondition(strpos($controllerCode, 'topDiagSub') !== false, "ReportController computes topDiagSub with case count & percentage");
assertCondition(strpos($controllerCode, 'full_title') !== false, "ReportController provides full_title for Top Diagnosis card");

// 3. Inspect print styles in reports/index.php
$viewCode = file_get_contents('app/Views/reports/index.php');
assertCondition(strpos($viewCode, 'size: landscape;') !== false, "@media print specifies landscape page orientation");
assertCondition(strpos($viewCode, 'margin: 10mm 8mm;') !== false, "@media print specifies standard margins");
assertCondition(strpos($viewCode, 'overflow: visible !important;') !== false, "@media print un-truncates text for complete legibility");
assertCondition(strpos($viewCode, 'max-width: none !important;') !== false, "@media print clears max-width constraints");
assertCondition(strpos($viewCode, 'print-metrics-summary') !== false, "View renders print-metrics-summary for printable executive numbers");
assertCondition(strpos($viewCode, 'Republic of the Philippines') !== false, "Print header includes official Republic of the Philippines standard");
assertCondition(strpos($viewCode, 'City Health Office') !== false, "Print header includes City Health Office & Brgy Sinalhan");

// 4. Inspect tooltips in consultations table
assertCondition(strpos($viewCode, 'title="<?= h($row[\'subjective\']) ?>"') !== false, "Consultations table adds hover tooltip to subjective complaint");
assertCondition(strpos($viewCode, 'title="<?= h($row[\'assessment\']) ?>"') !== false, "Consultations table adds hover tooltip to diagnosis assessment");
assertCondition(strpos($viewCode, 'title="<?= !empty($m[\'full_title\'])') !== false, "KPI metric cards render full_title hover tooltip");

// 5. Inspect standardized patient links
assertCondition(strpos($viewCode, 'badge bg-light text-primary border text-decoration-none" title="View Patient Profile"') !== false, "Patient IDs rendered with styled clickable badge links");
assertCondition(strpos($viewCode, 'title="View Child Profile"') !== false, "EPI Child ID rendered with styled clickable badge link");

// 6. Test ReportController computeReportMetrics via Reflection
require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Controllers/ReportController.php';

$rc = new \App\Controllers\ReportController();
$ref = new \ReflectionClass($rc);
$computeMetrics = $ref->getMethod('computeReportMetrics');
$computeMetrics->setAccessible(true);

$mockConsultations = [
    ['patient_id' => 1, 'clinician_name' => 'Dr. Cruz', 'assessment' => 'Essential Hypertension - Stage 2 (Uncontrolled)'],
    ['patient_id' => 2, 'clinician_name' => 'Dr. Cruz', 'assessment' => 'Essential Hypertension - Stage 2 (Uncontrolled)'],
    ['patient_id' => 3, 'clinician_name' => 'Nurse Joy', 'assessment' => 'Bronchial Asthma in Mild Acute Exacerbation'],
    ['patient_id' => 4, 'clinician_name' => 'Dr. Cruz', 'assessment' => 'Acute Gastroenteritis (AGE) with Mild Dehydration'],
];

$metrics = $computeMetrics->invoke($rc, 'consultations', $mockConsultations);
$topCard = null;
foreach ($metrics as $m) {
    if ($m['label'] === 'Top Diagnosis') {
        $topCard = $m;
        break;
    }
}

assertCondition($topCard !== null, "computeReportMetrics returns Top Diagnosis card");
assertCondition($topCard['full_title'] === 'Essential Hypertension - Stage 2 (Uncontrolled)', "Top Diagnosis retains complete condition name in full_title");
assertCondition(strpos($topCard['sub'], '2 case(s) (50%)') !== false, "Top Diagnosis calculates volume and percentage (2 cases, 50%)");

echo "\n--------------------------------------------------\n";
echo "Phase 1 Automated Verification Results:\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "--------------------------------------------------\n";

exit($failed > 0 ? 1 : 0);
