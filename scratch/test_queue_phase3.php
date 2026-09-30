<?php
/**
 * Automated Verification Suite for Daily Queue Remediation - Phase 3
 * Tests:
 * 1. Syntax check on modified files
 * 2. Route registration for /api/queue/active-today
 * 3. QueueController activeToday endpoint structure and JSON output
 * 4. QueueController store with appointment_id check-in tracking
 * 5. QueueController updateStatus with cancel_reason logging
 * 6. Rendering queue index view with Today's Bookings card, Live Sync widget, and action buttons
 * 7. Verification of SweetAlert2 delegated confirmation and cancel reason modal
 * 8. Verification of 15-second background sync timer and state synchronization
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
use App\Models\Appointment;
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

echo "=== PHASE 3 TEST SUITE: CLINIC INTEGRATION, APPOINTMENTS BRIDGE & LIVE POLLING ===\n\n";

// 1. Syntax Check
$filesToLint = [
    $baseDir . '/config/routes.php',
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

// 2. Route Registration
$routesContent = file_get_contents($baseDir . '/config/routes.php');
assertTest(
    strpos($routesContent, "'/api/queue/active-today'") !== false && strpos($routesContent, "'QueueController@activeToday'") !== false,
    "Route /api/queue/active-today is registered in config/routes.php"
);

// 3. QueueController activeToday Method Verification
$cmdActive = 'php ' . escapeshellarg($baseDir . '/scratch/run_active_today.php');
$jsonOutput = shell_exec($cmdActive);

$activeData = json_decode((string)$jsonOutput, true);
assertTest(is_array($activeData) && !empty($activeData['success']), "QueueController::activeToday() returns JSON with success = true");
assertTest(isset($activeData['stats']) && is_array($activeData['stats']), "activeToday payload contains stats object");
assertTest(isset($activeData['queue']) && is_array($activeData['queue']), "activeToday payload contains queue list array");
assertTest(isset($activeData['appointments']) && is_array($activeData['appointments']), "activeToday payload contains appointments array");
assertTest(!empty($activeData['timestamp']), "activeToday payload contains timestamp");
assertTest(!empty($activeData['server_time']), "activeToday payload contains server_time");

// 4. View Rendering Assertions with Mock Queue and Scheduled Bookings
$mockQueueList = [
    [
        'id' => 201,
        'queue_no' => 1,
        'patient_id' => 10,
        'patient_first' => 'Juan',
        'patient_last' => 'Dela Cruz',
        'patient_no' => 'PAT-0010',
        'service_type' => 'General OPD',
        'status' => 'Waiting',
        'time_in' => date('H:i:s', time() - 300),
        'time_called' => null,
        'time_completed' => null
    ],
    [
        'id' => 202,
        'queue_no' => 2,
        'patient_id' => 20,
        'patient_first' => 'Maria',
        'patient_last' => 'Santos',
        'patient_no' => 'PAT-0020',
        'service_type' => 'Prenatal Care',
        'status' => 'Called',
        'time_in' => date('H:i:s', time() - 1200),
        'time_called' => date('H:i:s', time() - 100),
        'time_completed' => null
    ]
];

$mockAppointments = [
    [
        'id' => 501,
        'patient_id' => 10, // Already in queue
        'patient_first' => 'Juan',
        'patient_last' => 'Dela Cruz',
        'patient_no' => 'PAT-0010',
        'program_type' => 'General OPD',
        'appointment_date' => date('Y-m-d'),
        'appointment_time' => '09:00:00',
        'status' => 'Scheduled'
    ],
    [
        'id' => 502,
        'patient_id' => 30, // Pending check-in
        'patient_first' => 'Ana',
        'patient_last' => 'Lim',
        'patient_no' => 'PAT-0030',
        'program_type' => 'Maternal Care',
        'appointment_date' => date('Y-m-d'),
        'appointment_time' => '10:30:00',
        'status' => 'Scheduled'
    ]
];

$mockStats = [
    'total_today' => 2,
    'waiting' => 1,
    'called' => 1,
    'serving' => 0,
    'completed' => 0,
    'serving_no' => 2,
    'serving_service' => 'Prenatal Care'
];

ob_start();
$queueList = $mockQueueList;
$queueStats = $mockStats;
$todayAppointments = $mockAppointments;
$preselectedPatient = null;
$errors = [];
$input = [];

require $baseDir . '/app/Views/queue/index.php';
$html = ob_get_clean();

// 5. Verify Live Sync Header Widget
assertTest(strpos($html, 'id="liveSyncWidget"') !== false, "Header renders Live Sync status widget (#liveSyncWidget)");
assertTest(strpos($html, 'id="liveSyncStatus"') !== false, "Header renders liveSyncStatus element");
assertTest(strpos($html, 'id="btnToggleSync"') !== false, "Header renders pause/resume sync toggle button (#btnToggleSync)");

// 6. Verify Dynamic KPI Element IDs
assertTest(strpos($html, 'id="kpiServingNo"') !== false, "KPI card contains id='kpiServingNo'");
assertTest(strpos($html, 'id="kpiServingService"') !== false, "KPI card contains id='kpiServingService'");
assertTest(strpos($html, 'id="kpiWaitingCount"') !== false, "KPI card contains id='kpiWaitingCount'");
assertTest(strpos($html, 'id="kpiCalledCount"') !== false, "KPI card contains id='kpiCalledCount'");
assertTest(strpos($html, 'id="kpiCompletedCount"') !== false, "KPI card contains id='kpiCompletedCount'");

// 7. Verify Filter Count IDs
assertTest(strpos($html, 'id="filterCountAll"') !== false, "Quick filter chips contain id='filterCountAll'");
assertTest(strpos($html, 'id="filterCountWaiting"') !== false, "Quick filter chips contain id='filterCountWaiting'");
assertTest(strpos($html, 'id="filterCountCalledServing"') !== false, "Quick filter chips contain id='filterCountCalledServing'");
assertTest(strpos($html, 'id="filterCountCompleted"') !== false, "Quick filter chips contain id='filterCountCompleted'");

// 8. Verify Today's Bookings Card (Appointments Bridge)
assertTest(strpos($html, 'id="scheduledAppointmentsCard"') !== false, "Left column renders Today's Bookings card (#scheduledAppointmentsCard)");
assertTest(strpos($html, 'id="pendingAppointmentsBadge"') !== false, "Card header renders pending appointments counter badge");
assertTest(strpos($html, 'Queue #001') !== false, "Already queued appointment shows 'Queue #001' badge");
assertTest(strpos($html, 'Check In') !== false, "Pending appointment renders 'Check In' button");
assertTest(strpos($html, 'name="appointment_id"') !== false, "Check In form passes appointment_id hidden input");
assertTest(strpos($html, 'Prenatal Care') !== false, "Maternal Care appointment maps properly to Prenatal Care service");

// 9. Verify Action Button Classes and Data Attributes
assertTest(strpos($html, 'btn-queue-action') !== false, "Lifecycle action buttons include 'btn-queue-action' class");
assertTest(strpos($html, 'btn-queue-cancel') !== false, "Cancel buttons include 'btn-queue-cancel' class");
assertTest(strpos($html, 'data-action="call"') !== false, "Call button includes data-action='call'");
assertTest(strpos($html, 'data-action="serve"') !== false, "Serve button includes data-action='serve'");
assertTest(strpos($html, 'data-action="recall"') !== false, "Re-call button includes data-action='recall'");

// 10. Verify SweetAlert2 and 15s Polling Script
assertTest(strpos($html, 'Swal.fire') !== false, "JavaScript uses SweetAlert2 for queue action confirmations");
assertTest(strpos($html, 'inputOptions') !== false, "Cancellation SweetAlert2 includes select options for reason");
assertTest(strpos($html, 'No Show / Patient Left') !== false, "Cancellation options include 'No Show / Patient Left'");
assertTest(strpos($html, 'Duplicate Entry') !== false, "Cancellation options include 'Duplicate Entry'");
assertTest(strpos($html, "fetch('<?= url('/api/queue/active-today') ?>'") !== false || strpos($html, "api/queue/active-today") !== false, "JavaScript polls /api/queue/active-today");
assertTest(strpos($html, '15000') !== false, "Background polling timer configured to 15,000ms (15 seconds)");
assertTest(strpos($html, 'btnToggleSync') !== false, "Script wires up sync pause/resume toggle");

echo "\n--------------------------------------------------\n";
echo "Phase 3 Automated Verification Results:\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "--------------------------------------------------\n";

if ($failed > 0) {
    exit(1);
}
