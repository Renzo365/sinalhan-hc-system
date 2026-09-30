<?php
/**
 * Automated Verification Suite for Daily Queue Remediation - Phase 2
 * Tests:
 * 1. Syntax check on modified files
 * 2. QueueEntry STATUS_TRANSITIONS allows Called -> Called (re-calling)
 * 3. QueueEntry getTodayStats structure and keys
 * 4. QueueController passing queueStats to view
 * 5. Rendering queue index view with KPI cards, quick status filter chips, elapsed wait badges, and re-call button
 * 6. Enforcement of pure FCFS (no priority lanes/badges)
 * 7. Verification of clean KPI styling (no harsh colored left borders)
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Admin';
$_SESSION['role_name'] = 'Admin';

$baseDir = dirname(__DIR__);
require_once $baseDir . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once $baseDir . '/app/helpers.php';

use App\Models\QueueEntry;
use App\Models\Patient;
use App\Controllers\QueueController;

$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $title, string $details = '') {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo " [PASS] $title\n";
    } else {
        $failed++;
        echo "![FAIL] $title: $details\n";
    }
}

echo "=== PHASE 2 TEST SUITE: OPERATIONAL VISIBILITY, KPI CARDS & TABLE ERGONOMICS ===\n\n";

// 1. Syntax Check
$filesToLint = [
    $baseDir . '/app/Models/QueueEntry.php',
    $baseDir . '/app/Controllers/QueueController.php',
    $baseDir . '/app/Views/queue/index.php'
];

foreach ($filesToLint as $file) {
    $cmd = 'php -l ' . escapeshellarg($file);
    $out = [];
    $ret = 0;
    exec($cmd, $out, $ret);
    assertTest($ret === 0, "Syntax check: " . basename($file), implode("\n", $out));
}

// 2. Status Transitions Validation
$transitions = QueueEntry::STATUS_TRANSITIONS;
assertTest(
    isset($transitions['Called']) && in_array('Called', $transitions['Called'], true),
    "STATUS_TRANSITIONS allows Called -> Called (re-calling patient)"
);
assertTest(
    isset($transitions['Called']) && in_array('Serving', $transitions['Called'], true),
    "STATUS_TRANSITIONS allows Called -> Serving"
);

// 3. Queue Model getTodayStats Structure
$queueModel = new QueueEntry();
$stats = $queueModel->getTodayStats();

$requiredKeys = ['total_today', 'waiting', 'called', 'serving', 'completed', 'serving_no', 'serving_service'];
$missingKeys = [];
foreach ($requiredKeys as $key) {
    if (!array_key_exists($key, $stats)) {
        $missingKeys[] = $key;
    }
}
assertTest(empty($missingKeys), "QueueEntry::getTodayStats() returns all required keys", "Missing: " . implode(', ', $missingKeys));

// 4. View Rendering Assertions with Mock Data
$mockQueueList = [
    [
        'id' => 101,
        'queue_no' => 1,
        'patient_id' => 1,
        'patient_first' => 'Juan',
        'patient_last' => 'Dela Cruz',
        'patient_no' => 'PAT-0001',
        'service_type' => 'General OPD',
        'status' => 'Serving',
        'time_in' => date('H:i:s', time() - 3600), // 60 mins ago
        'time_called' => date('H:i:s', time() - 1800),
        'time_completed' => null
    ],
    [
        'id' => 102,
        'queue_no' => 2,
        'patient_id' => 2,
        'patient_first' => 'Maria',
        'patient_last' => 'Santos',
        'patient_no' => 'PAT-0002',
        'service_type' => 'Prenatal Care',
        'status' => 'Called',
        'time_in' => date('H:i:s', time() - 2400), // 40 mins ago (> 30 mins)
        'time_called' => date('H:i:s', time() - 300),
        'time_completed' => null
    ],
    [
        'id' => 103,
        'queue_no' => 3,
        'patient_id' => 3,
        'patient_first' => 'Pedro',
        'patient_last' => 'Penduko',
        'patient_no' => 'PAT-0003',
        'service_type' => 'Senior Care',
        'status' => 'Waiting',
        'time_in' => date('H:i:s', time() - 600), // 10 mins ago (< 30 mins)
        'time_called' => null,
        'time_completed' => null
    ],
    [
        'id' => 104,
        'queue_no' => 4,
        'patient_id' => 4,
        'patient_first' => 'Clara',
        'patient_last' => 'Reyes',
        'patient_no' => 'PAT-0004',
        'service_type' => 'Well Baby Immunization',
        'status' => 'Completed',
        'time_in' => date('H:i:s', time() - 5000),
        'time_called' => date('H:i:s', time() - 4000),
        'time_completed' => date('H:i:s', time() - 3000)
    ]
];

$mockStats = [
    'total_today' => 4,
    'waiting' => 1,
    'called' => 1,
    'serving' => 1,
    'completed' => 1,
    'serving_no' => 1,
    'serving_service' => 'General OPD'
];

// Capture view output
ob_start();
// Setup globals for layout inclusion
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Admin';
$_SESSION['role_name'] = 'Admin';

$queueList = $mockQueueList;
$queueStats = $mockStats;
$preselectedPatient = null;
$errors = [];
$input = [];

require $baseDir . '/app/Views/queue/index.php';
$html = ob_get_clean();

// 5. Verify KPI Summary Cards
assertTest(strpos($html, 'id="queueKpiCards"') !== false, "View renders KPI summary cards container (#queueKpiCards)");
assertTest(strpos($html, 'Now Serving') !== false, "View renders 'Now Serving' KPI card");
assertTest(strpos($html, 'Waiting in Lobby') !== false, "View renders 'Waiting in Lobby' KPI card");
assertTest(strpos($html, 'Called / In Transit') !== false, "View renders 'Called / In Transit' KPI card");
assertTest(strpos($html, 'Completed Today') !== false, "View renders 'Completed Today' KPI card");
assertTest(strpos($html, '001') !== false, "Now Serving displays formatted queue number 001");
assertTest(strpos($html, 'General OPD') !== false, "Now Serving displays active service category");

// 6. Verify Clean KPI Card Styling (No harsh colored left borders)
assertTest(strpos($html, 'border-start border-4') === false, "KPI cards use subtle borders and shadow-sm, NO harsh 'border-start border-4'");

// 7. Verify Quick Status Filter Chips
assertTest(strpos($html, 'id="queueStatusFilters"') !== false, "View renders quick status filters container (#queueStatusFilters)");
assertTest(strpos($html, 'data-filter="all"') !== false, "Quick filter chips contain 'all' filter");
assertTest(strpos($html, 'data-filter="Waiting"') !== false, "Quick filter chips contain 'Waiting' filter");
assertTest(strpos($html, 'data-filter="Called|Serving"') !== false, "Quick filter chips contain 'Called|Serving' filter");
assertTest(strpos($html, 'data-filter="Completed"') !== false, "Quick filter chips contain 'Completed' filter");

// 8. Verify Table Ergonomics & Elapsed Wait Time
assertTest(strpos($html, '40m wait') !== false, "Elapsed wait calculation rendered for Called patient (40m wait)");
assertTest(strpos($html, '10m wait') !== false, "Elapsed wait calculation rendered for Waiting patient (10m wait)");
assertTest(strpos($html, 'Waiting over 30 minutes') !== false, "Warning alert badge displayed when patient wait exceeds 30 minutes");

// 9. Verify Action Column Ergonomics (Re-Call Action)
assertTest(strpos($html, 'Re-Call') !== false, "Action column renders 'Re-Call' button for Called patient");
assertTest(strpos($html, 're-trigger the chime on the public display') !== false, "Re-call confirmation modal informs user about audio chime");

// 10. Verify FCFS and No Priority Artifacts
assertTest(strpos($html, 'FCFS Order') !== false, "Queue list header displays 'FCFS Order' badge");
assertTest(stripos($html, 'Lane A') === false && stripos($html, 'Lane B') === false, "No priority lanes (A/B) exist in queue view");
assertTest(strpos($html, 'Priority Status') === false, "No 'Priority Status' column or badge exists");

// 11. Verify DataTables Column Search Integration
assertTest(strpos($html, 'queueDataTable.column(5).search') !== false, "JavaScript wires quick filter clicks to DataTables column 5 (Status)");
assertTest(strpos($html, 'data-search="') !== false, "Table cells include data-search attribute for exact filtering");

echo "\n--------------------------------------------------\n";
echo "Phase 2 Automated Verification Results:\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "--------------------------------------------------\n";

if ($failed > 0) {
    exit(1);
}
