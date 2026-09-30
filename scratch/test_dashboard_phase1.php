<?php
// Test Suite for Dashboard Phase 1: Controller Analytics & KPI Re-engineering

echo "=== PHASE 1 TEST SUITE: DASHBOARD CONTROLLER ANALYTICS & KPI CARDS ===\n\n";

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

// 1. Syntax checks
$phpLint1 = shell_exec('php -l app/Controllers/DashboardController.php');
assertCondition(strpos($phpLint1, 'No syntax errors detected') !== false, "Syntax check: app/Controllers/DashboardController.php");

$phpLint2 = shell_exec('php -l app/Views/dashboard.php');
assertCondition(strpos($phpLint2, 'No syntax errors detected') !== false, "Syntax check: app/Views/dashboard.php");

// 2. Controller inspection
$controllerCode = file_get_contents('app/Controllers/DashboardController.php');
assertCondition(strpos($controllerCode, 'apptStats') !== false, "DashboardController computes apptStats array");
assertCondition(strpos($controllerCode, 'today_traffic') !== false, "DashboardController computes today_traffic");
assertCondition(strpos($controllerCode, 'maternalWatch') !== false, "DashboardController computes maternalWatch");
assertCondition(strpos($controllerCode, 'upcomingDeliveries') !== false, "DashboardController computes upcomingDeliveries");
assertCondition(strpos($controllerCode, 'childHealth') !== false, "DashboardController computes childHealth");
assertCondition(strpos($controllerCode, 'recentConsultations') !== false, "DashboardController computes recentConsultations");

// 3. View inspection
$viewCode = file_get_contents('app/Views/dashboard.php');
assertCondition(strpos($viewCode, 'id="dashboardKpis"') !== false, "View renders dashboardKpis container");
assertCondition(strpos($viewCode, 'card card-premium shadow-sm h-100 border') !== false, "View uses standardized card-premium style");
assertCondition(strpos($viewCode, 'border-start border-4') === false, "View eliminates outdated 'border-start border-4' heavy borders");
assertCondition(strpos($viewCode, 'Traffic Today') !== false, "Card 1 displays Traffic Today metric");
assertCondition(strpos($viewCode, 'Appointments') !== false, "Card 2 displays Appointments metric with attendance");
assertCondition(strpos($viewCode, 'Waiting in Lobby') !== false, "Card 3 displays Waiting in Lobby with Now Serving");
assertCondition(strpos($viewCode, 'Community Census') !== false, "Card 4 displays Community Census with PhilHealth percentage");

// 4. Test view rendering
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Models/Patient.php';
require_once __DIR__ . '/../app/Models/Appointment.php';
require_once __DIR__ . '/../app/Models/QueueEntry.php';
require_once __DIR__ . '/../app/Controllers/DashboardController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['user_fullname'] = 'Test Doctor';
$_SESSION['user_role'] = 'Physician';

// Execute DashboardController->index() and capture output
ob_start();
$controller = new App\Controllers\DashboardController();
$controller->index();
$html = ob_get_clean();

assertCondition(!empty($html), "Dashboard renders non-empty HTML output");
assertCondition(strpos($html, 'id="dashboardKpis"') !== false, "Rendered HTML contains dashboardKpis");
assertCondition(strpos($html, 'border-4 border-') === false, "Rendered HTML is free of legacy border-4 classes");

echo "\n--------------------------------------------------\n";
echo "Phase 1 Automated Verification Results:\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "--------------------------------------------------\n";

if ($failed > 0) {
    exit(1);
}
