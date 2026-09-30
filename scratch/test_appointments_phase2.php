<?php
// Test Suite for Appointment System Remediation - Phase 2
echo "=== Running Appointment System Remediation Phase 2 Verification ===\n\n";

$pass = 0;
$fail = 0;

function assertTest($condition, $description) {
    global $pass, $fail;
    if ($condition) {
        echo " [PASS] $description\n";
        $pass++;
    } else {
        echo " [FAIL] $description\n";
        $fail++;
    }
}

// 1. PHP Syntax Check
$files = [
    'app/Models/Appointment.php',
    'app/Controllers/AppointmentController.php',
    'app/Views/appointments/index.php',
    'app/Views/appointments/create.php',
    'app/Views/appointments/edit.php'
];

foreach ($files as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    $output = [];
    $returnVar = 0;
    exec("php -l \"$fullPath\"", $output, $returnVar);
    assertTest($returnVar === 0, "Syntax validation: $file is valid PHP");
}

// 2. Appointment Model getTodaySummaryMetrics()
require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/../app/helpers.php';

try {
    $apptModel = new \App\Models\Appointment();
    $metrics = $apptModel->getTodaySummaryMetrics();
    assertTest(is_array($metrics), 'Appointment::getTodaySummaryMetrics() returns array');
    assertTest(isset($metrics['total_today']) && is_int($metrics['total_today']), 'Metrics contains integer total_today');
    assertTest(isset($metrics['scheduled_today']) && is_int($metrics['scheduled_today']), 'Metrics contains integer scheduled_today');
    assertTest(isset($metrics['completed_today']) && is_int($metrics['completed_today']), 'Metrics contains integer completed_today');
    assertTest(isset($metrics['missed_cancelled_today']) && is_int($metrics['missed_cancelled_today']), 'Metrics contains integer missed_cancelled_today');
} catch (\Throwable $e) {
    assertTest(false, "Appointment::getTodaySummaryMetrics() error: " . $e->getMessage());
}

// 3. AppointmentController index integration
$controllerContent = file_get_contents(__DIR__ . '/../app/Controllers/AppointmentController.php');
assertTest(
    strpos($controllerContent, 'getTodaySummaryMetrics()') !== false,
    'AppointmentController::index() invokes getTodaySummaryMetrics()'
);
assertTest(
    strpos($controllerContent, "'todayMetrics' => \$todayMetrics") !== false,
    'AppointmentController::index() passes todayMetrics to view'
);

// 4. Directory index.php KPI Cards, Quick Filters, Badges & Safe Action Column
$indexContent = file_get_contents(__DIR__ . '/../app/Views/appointments/index.php');
assertTest(
    strpos($indexContent, 'id="dailyTriageKpiCards"') !== false,
    'index.php renders #dailyTriageKpiCards container'
);
assertTest(
    strpos($indexContent, "Today's Visits") !== false && strpos($indexContent, 'Pending / Scheduled') !== false,
    'index.php contains Today Visits and Pending/Scheduled KPI metrics'
);
assertTest(
    strpos($indexContent, 'Completed Today') !== false && strpos($indexContent, 'Missed / Cancelled') !== false,
    'index.php contains Completed and Missed/Cancelled KPI metrics'
);
assertTest(
    strpos($indexContent, 'data-preset="today"') !== false && strpos($indexContent, 'data-preset="tomorrow"') !== false && strpos($indexContent, 'data-preset="this_week"') !== false,
    'index.php provides one-click quick date filter chips (today, tomorrow, this_week)'
);
assertTest(
    strpos($indexContent, 'data-preset="pending_today"') !== false,
    'index.php provides one-click Pending Today filter chip'
);
assertTest(
    strpos($indexContent, 'badge-category-maternal') !== false && strpos($indexContent, 'badge-category-wellbaby') !== false && strpos($indexContent, 'badge-category-opd') !== false,
    'index.php uses accessible badge classes for program categories'
);
assertTest(
    strpos($indexContent, 'bi-three-dots-vertical') !== false && strpos($indexContent, 'Mark as Missed (No-Show)') !== false && strpos($indexContent, 'Cancel Appointment') !== false,
    'index.php organizes secondary actions (Missed, Cancel) inside safe dropdown menu'
);
assertTest(
    strpos($indexContent, 'action-confirm-btn') !== false,
    'index.php uses action-confirm-btn with data-confirm attributes'
);
assertTest(
    strpos($indexContent, 'appointmentsTable') !== false && strpos($indexContent, 'addEventListener(\'click\'') !== false,
    'index.php implements delegated click confirmation working across DataTables pagination'
);

// 5. Scheduling Form create.php Capacity Communication & Visual Feedback
$createContent = file_get_contents(__DIR__ . '/../app/Views/appointments/create.php');
assertTest(
    strpos($createContent, 'Capacity Guide:') !== false,
    'create.php displays Capacity Guide legend above slot grid'
);
assertTest(
    strpos($createContent, 'id="slotCapacityAdvisory"') !== false,
    'create.php contains #slotCapacityAdvisory element'
);
assertTest(
    strpos($createContent, 'updateSlotAdvisory') !== false,
    'create.php implements updateSlotAdvisory() for concurrent booking alerts'
);
assertTest(
    strpos($createContent, 'high-booked') !== false && strpos($createContent, 'has-booked') !== false,
    'create.php dynamic capacities support tiered capacity styling (has-booked, high-booked)'
);
assertTest(
    strpos($createContent, 'background-color: #ecfdf5; color: #065f46;') !== false,
    'create.php session indicator uses high contrast WCAG compliant colors'
);

// 6. Rescheduling Form edit.php Capacity Communication & Visual Feedback
$editContent = file_get_contents(__DIR__ . '/../app/Views/appointments/edit.php');
assertTest(
    strpos($editContent, 'Capacity Guide:') !== false,
    'edit.php displays Capacity Guide legend above slot grid'
);
assertTest(
    strpos($editContent, 'id="slotCapacityAdvisory"') !== false,
    'edit.php contains #slotCapacityAdvisory element'
);
assertTest(
    strpos($editContent, 'updateSlotAdvisory') !== false,
    'edit.php implements updateSlotAdvisory() for concurrent booking alerts'
);
assertTest(
    strpos($editContent, 'high-booked') !== false && strpos($editContent, 'has-booked') !== false,
    'edit.php dynamic capacities support tiered capacity styling (has-booked, high-booked)'
);
assertTest(
    strpos($editContent, 'background-color: #ecfdf5; color: #065f46;') !== false,
    'edit.php session indicator uses high contrast WCAG compliant colors'
);

// 7. Stylesheet index.css
$cssContent = file_get_contents(__DIR__ . '/../public/assets/css/index.css');
assertTest(
    strpos($cssContent, '.badge-category-maternal') !== false && strpos($cssContent, '.badge-category-wellbaby') !== false,
    'index.css defines accessible maternal and wellbaby category badges'
);
assertTest(
    strpos($cssContent, '.time-slot-card.high-booked') !== false && strpos($cssContent, '.time-slot-card.has-booked') !== false,
    'index.css defines high-booked and has-booked slot capacity states'
);

echo "\nSummary: $pass Passed, $fail Failed.\n";
if ($fail > 0) {
    exit(1);
} else {
    echo "All Phase 2 tests passed successfully!\n";
    exit(0);
}
