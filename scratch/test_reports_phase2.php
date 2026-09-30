<?php
// scratch/test_reports_phase2.php
// Verification test for Reports Remediation Phase 2

require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Controllers/ReportController.php';

$testsPassed = 0;
$testsFailed = 0;

function assertTrue($condition, $message) {
    global $testsPassed, $testsFailed;
    if ($condition) {
        echo "  [PASS] $message\n";
        $testsPassed++;
    } else {
        echo "  [FAIL] $message\n";
        $testsFailed++;
    }
}

echo "=== Running Reports Remediation Phase 2 Automated Tests ===\n\n";

// 1. Test computeMorbidityBreakdown logic
echo "Test Group 1: computeMorbidityBreakdown in ReportController\n";
$reflection = new ReflectionClass('App\Controllers\ReportController');
$method = $reflection->getMethod('computeMorbidityBreakdown');
$method->setAccessible(true);
$controller = new App\Controllers\ReportController();

$emptyBreakdown = $method->invoke($controller, []);
assertTrue(empty($emptyBreakdown), "Empty results yield empty morbidity breakdown");

$dummyConsultations = [
    ['assessment' => 'Upper Respiratory Tract Infection (URTI)'],
    ['assessment' => 'Upper Respiratory Tract Infection (URTI)'],
    ['assessment' => 'Upper Respiratory Tract Infection (URTI)'],
    ['assessment' => 'Essential Primary Hypertension'],
    ['assessment' => 'Essential Primary Hypertension'],
    ['assessment' => 'Acute Gastroenteritis (AGE)'],
    ['assessment' => 'Type 2 Diabetes Mellitus'],
    ['assessment' => 'Contact Dermatitis'],
    ['assessment' => ''], // should be skipped or handled cleanly
];

$breakdown = $method->invoke($controller, $dummyConsultations);
assertTrue(count($breakdown) === 5, "Breakdown includes all 5 distinct non-empty diagnoses");
assertTrue($breakdown[0]['diagnosis'] === 'Upper Respiratory Tract Infection (URTI)' && $breakdown[0]['cases'] === 3, "Top diagnosis is URTI with count 3");
assertTrue($breakdown[0]['percentage'] === 37.5, "URTI percentage is 37.5% (3/8 valid assessments)");
assertTrue($breakdown[1]['diagnosis'] === 'Essential Primary Hypertension' && $breakdown[1]['cases'] === 2, "2nd diagnosis is Hypertension with count 2");
assertTrue($breakdown[1]['percentage'] === 25.0, "Hypertension percentage is 25.0%");

// Test Top 10 cap
$manyDiagnoses = [];
for ($i = 1; $i <= 15; $i++) {
    for ($c = 0; $c < $i; $c++) {
        $manyDiagnoses[] = ['assessment' => "Condition $i"];
    }
}
$topBreakdown = $method->invoke($controller, $manyDiagnoses);
assertTrue(count($topBreakdown) === 10, "Breakdown is strictly capped at top 10 items");
assertTrue($topBreakdown[0]['diagnosis'] === 'Condition 15', "Rank 1 is the most frequent diagnosis");

// 2. View Inspection Tests
echo "\nTest Group 2: View Ergonomics & Elements in reports/index.php\n";
$viewFile = file_get_contents(__DIR__ . '/../app/Views/reports/index.php');

assertTrue(strpos($viewFile, 'position: sticky;') !== false && strpos($viewFile, 'top: 1.25rem;') !== false, "Sticky sidebar CSS rules are present");
assertTrue(strpos($viewFile, 'id="quickDatePresets"') !== false, "Quick Date presets wrapper container is present");
assertTrue(strpos($viewFile, 'Top 10 Leading Causes of Morbidity') !== false, "DOH FHSIS Top 10 Morbidity widget heading is present");
assertTrue(strpos($viewFile, '$morbidityBreakdown') !== false, "Morbidity breakdown loop exists in view");

// 3. DataTables data-order attributes check
echo "\nTest Group 3: DataTables data-order attributes across tables\n";
assertTrue(strpos($viewFile, '<td data-order="<?= h($row[\'queue_date\']) ?>"><?= h($row[\'queue_date\']) ?></td>') !== false, "daily_visits queue_date has data-order");
assertTrue(strpos($viewFile, '<td data-order="<?= h($row[\'time_in\']) ?>">') !== false, "daily_visits time_in has data-order");
assertTrue(strpos($viewFile, '<td data-order="<?= h($row[\'time_called\'] ?: \'99:99:99\') ?>">') !== false, "daily_visits time_called has data-order");
assertTrue(strpos($viewFile, '<td data-order="<?= h($row[\'time_completed\'] ?: \'99:99:99\') ?>">') !== false, "daily_visits time_completed has data-order");

assertTrue(strpos($viewFile, '<td data-order="<?= date(\'Y-m-d H:i:s\', strtotime($row[\'consulted_at\'])) ?>"><?= date(\'Y-m-d\', strtotime($row[\'consulted_at\'])) ?></td>') !== false, "consultations consulted_at has data-order");

assertTrue(strpos($viewFile, '<td data-order="<?= date(\'Y-m-d H:i:s\', strtotime($row[\'created_at\'])) ?>"><?= date(\'Y-m-d\', strtotime($row[\'created_at\'])) ?></td>') !== false, "registrations created_at has data-order");
assertTrue(strpos($viewFile, '<td data-order="<?= date(\'Y-m-d\', strtotime($row[\'dob\'])) ?>"><?= h($row[\'dob\']) ?></td>') !== false, "registrations dob has data-order");

assertTrue(strpos($viewFile, '<td class="fw-bold" data-order="<?= h($row[\'date\']) ?>"><?= h($row[\'date\']) ?></td>') !== false, "queue_summary date has data-order");

assertTrue(strpos($viewFile, '<td data-order="<?= date(\'Y-m-d H:i:s\', strtotime($row[\'recorded_at\'])) ?>"><?= date(\'Y-m-d\', strtotime($row[\'recorded_at\'])) ?></td>') !== false, "vitals recorded_at has data-order");

assertTrue(strpos($viewFile, '<td data-order="<?= date(\'Y-m-d\', strtotime($row[\'lmp\'])) ?>"><?= date(\'M d, Y\', strtotime($row[\'lmp\'])) ?></td>') !== false, "maternal_health lmp has data-order");
assertTrue(strpos($viewFile, '<td class="fw-bold text-primary" data-order="<?= date(\'Y-m-d\', strtotime($row[\'edc\'])) ?>"><?= date(\'M d, Y\', strtotime($row[\'edc\'])) ?></td>') !== false, "maternal_health edc has data-order");

assertTrue(strpos($viewFile, '<td data-order="<?= date(\'Y-m-d\', strtotime($row[\'dob\'])) ?>"><?= date(\'M d, Y\', strtotime($row[\'dob\'])) ?></td>') !== false, "epi_coverage dob has data-order");

// 4. Clinician column formatting and role badge parser
echo "\nTest Group 4: Clinician column alignment and role pill logic\n";
assertTrue(strpos($viewFile, '<th class="text-start" style="min-width: 175px;">Clinician / Provider</th>') !== false, "Clinician table header is left-aligned with minimum width");
assertTrue(strpos($viewFile, 'BHW|Nurse|Midwife|Doctor|MD|RN|RM') !== false, "Regex for role parsing matches clinician roles");
assertTrue(strpos($viewFile, '<td class="text-start" style="min-width: 175px;">') !== false, "Clinician table cell is left-aligned with minimum width to avoid clipping");

echo "\n======================================================\n";
echo "Results: $testsPassed Passed, $testsFailed Failed\n";
echo "======================================================\n";

if ($testsFailed > 0) {
    exit(1);
}
exit(0);
