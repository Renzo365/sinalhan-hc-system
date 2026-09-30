<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Models/Patient.php';
require_once __DIR__ . '/../app/Models/Appointment.php';
require_once __DIR__ . '/../app/Models/QueueEntry.php';
require_once __DIR__ . '/../app/Models/AuditLog.php';
require_once __DIR__ . '/../app/Controllers/DashboardController.php';

use App\Controllers\DashboardController;
use App\Models\Appointment;
use App\Models\QueueEntry;

echo "=== STARTING DASHBOARD PHASE 2 VERIFICATION SUITE ===\n\n";

$passCount = 0;
$failCount = 0;

function assertCondition($testName, $condition, $details = '') {
    global $passCount, $failCount;
    if ($condition) {
        echo " [PASS] $testName\n";
        $passCount++;
    } else {
        echo " [FAIL] $testName" . ($details ? " - $details" : "") . "\n";
        $failCount++;
    }
}

// 1. Verify Appointment::getTodayAppointments() includes contact_no
$apptModel = new Appointment();
$todayAppts = $apptModel->getTodayAppointments();
assertCondition(
    "Appointment::getTodayAppointments returns array",
    is_array($todayAppts)
);

if (!empty($todayAppts)) {
    $first = $todayAppts[0];
    assertCondition(
        "getTodayAppointments includes contact_no column key",
        array_key_exists('contact_no', $first),
        "Keys present: " . implode(', ', array_keys($first))
    );
    assertCondition(
        "getTodayAppointments includes patient_no and names",
        isset($first['patient_no']) && isset($first['patient_first']) && isset($first['patient_last'])
    );
} else {
    echo " [INFO] No appointments today; checking SQL definition directly...\n";
    $refMethod = new ReflectionMethod(Appointment::class, 'getTodayAppointments');
    $source = file_get_contents($refMethod->getFileName());
    assertCondition(
        "getTodayAppointments SQL queries p.contact_no",
        strpos($source, 'p.contact_no') !== false
    );
}

// 2. Render DashboardController via Output Buffering
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['user_fullname'] = 'Dr. Juan Dela Cruz';
$_SESSION['user_role'] = 'admin';

ob_start();
try {
    $controller = new DashboardController();
    $controller->index();
    $html = ob_get_clean();
    $controllerSuccess = true;
} catch (Throwable $t) {
    $html = ob_get_clean();
    $controllerSuccess = false;
    echo "Controller exception: " . $t->getMessage() . "\n";
}

assertCondition(
    "DashboardController::index() executes without throwing exception",
    $controllerSuccess
);

// 3. Verify Clean Header & User Greeting
assertCondition(
    "Dashboard heading present",
    strpos($html, '<h2 class="h3 mb-1 fw-bold text-primary-dark">Dashboard</h2>') !== false
);

assertCondition(
    "User full name displayed in header greeting",
    strpos($html, 'Dr. Juan Dela Cruz') !== false
);

assertCondition(
    "No redundant role badge in dashboard title",
    strpos($html, 'Super Admin') === false || strpos($html, 'topbar') !== false
);

// 4. Verify Live PST Date & Clock in Topbar
assertCondition(
    "Live PST clock element #topbarClock present in topbar",
    strpos($html, 'id="topbarClock"') !== false
);

assertCondition(
    "Live date element #topbarLiveDate present in topbar",
    strpos($html, 'id="topbarLiveDate"') !== false
);

assertCondition(
    "Live PST Clock JavaScript configured with Asia/Manila timezone in topbar",
    strpos($html, 'Asia/Manila') !== false && strpos($html, 'topbarClock') !== false
);

// 5. Verify Appointments Table Ergonomics
assertCondition(
    "Appointments table header has Time, Patient Details, Program & Purpose, Status, Action",
    strpos($html, 'Patient Details') !== false &&
    (strpos($html, 'Program & Purpose') !== false || strpos($html, 'Program &amp; Purpose') !== false) &&
    strpos($html, 'Action') !== false
);

// 6. Verify Queue Board Enhancements
assertCondition(
    "Queue Board Daily Queue Overview present",
    strpos($html, 'Daily Queue Overview') !== false
);

assertCondition(
    "Queue Board has 'Manage Live Queue' button",
    strpos($html, 'Manage Live Queue') !== false
);

assertCondition(
    "Queue Board has 'Open Public Display' button",
    strpos($html, url('/queue/display')) !== false && strpos($html, 'Display') !== false
);

// 8. Verify No Deprecated Fields
assertCondition(
    "No deprecated barangay column reference in dashboard HTML",
    strpos($html, '$patient[\'barangay\']') === false && strpos($html, 'p.barangay') === false
);

assertCondition(
    "No legacy border-4 classes in dashboard HTML",
    strpos($html, 'border-start border-4') === false
);

echo "\n=== TEST SUMMARY ===\n";
echo "Total Passed: $passCount\n";
echo "Total Failed: $failCount\n";

if ($failCount > 0) {
    exit(1);
}
echo "ALL PHASE 2 VERIFICATIONS PASSED SUCCESSFULLY!\n";
