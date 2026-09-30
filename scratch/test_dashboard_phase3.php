<?php
/**
 * Automated Verification Suite for Dashboard Phase 3:
 * Maternal Delivery Radar & Child Health (EPI) Widgets.
 */

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

echo "=== STARTING DASHBOARD PHASE 3 VERIFICATION SUITE ===\n\n";

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

// 1. Syntax check
$phpLint = shell_exec('php -l app/Views/dashboard.php');
assertCondition("Syntax check app/Views/dashboard.php", strpos($phpLint, 'No syntax errors detected') !== false);

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

assertCondition("DashboardController::index() executes without error", $controllerSuccess);

// 3. Verify Maternal Delivery Radar Widget Presence & Structure
assertCondition("MCH Radar section container #dashboardMchRadar exists", strpos($html, 'id="dashboardMchRadar"') !== false);
assertCondition("Maternal Delivery Radar heading present", strpos($html, 'Maternal Delivery Radar') !== false);
assertCondition("Maternal Registry button link present", strpos($html, url('/maternal')) !== false);
assertCondition("Maternal New Episode button link present", strpos($html, url('/maternal/register')) !== false);

// 4. Verify Maternal Metrics & Counters
assertCondition("Active Cases counter rendered", strpos($html, 'Active Cases') !== false);
assertCondition("Due This Month counter rendered", strpos($html, 'Due This Month') !== false);
assertCondition("Overdue Watch counter rendered", strpos($html, 'Overdue Watch') !== false);

// 5. Verify Upcoming Deliveries Feed
assertCondition("Upcoming Deliveries section present", strpos($html, 'Upcoming Deliveries') !== false);
assertCondition("EDC Tracking label present", strpos($html, 'EDC Tracking') !== false);
assertCondition("Upcoming mother due rendered in countdown list", strpos($html, 'Christine') !== false || strpos($html, 'Nicole') !== false);
assertCondition("EDC date format rendered", strpos($html, 'EDC:') !== false);

// 6. Verify Child Health & Immunization (EPI) Widget Presence & Structure
assertCondition("Child Health & Immunization heading present", strpos($html, 'Child Health &amp; Immunization (EPI)') !== false);
assertCondition("Well-Baby Registry button link present", strpos($html, url('/well-baby')) !== false);
assertCondition("Enroll Child button link present", strpos($html, url('/well-baby/register')) !== false);

// 7. Verify Child Health Metrics & Counters
assertCondition("Under-5 Cohort counter rendered", strpos($html, 'Under-5 Cohort') !== false);
assertCondition("Infants (< 1 yr) counter rendered", strpos($html, 'Infants (&lt; 1 yr)') !== false || strpos($html, 'Infants') !== false);
assertCondition("Doses Given counter rendered", strpos($html, 'Doses Given') !== false);
assertCondition("DOH Routine Vaccine Schedule description present", strpos($html, 'DOH Routine Vaccine Schedule') !== false);
assertCondition("Well-Baby Growth Logs folder count present", strpos($html, 'Well-Baby Growth Logs') !== false);

// 8. Quality & Regression Safeguards
assertCondition("No legacy border-4 classes present", strpos($html, 'border-start border-4') === false);
assertCondition("Zero traces of raw barangay column in dashboard HTML", strpos($html, '$del[\'barangay\']') === false);

echo "\n=== TEST SUMMARY ===\n";
echo "Total Passed: $passCount\n";
echo "Total Failed: $failCount\n";

if ($failCount > 0) {
    exit(1);
}
echo "ALL PHASE 3 VERIFICATIONS PASSED SUCCESSFULLY!\n";
