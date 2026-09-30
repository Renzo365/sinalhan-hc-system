<?php
// Verification Script for Queue System Remediation: Phase 1
require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/../app/helpers.php';

echo "=== Running Daily Operations Queue Remediation Phase 1 Verification ===\n\n";

$pass = 0;
$fail = 0;

function assertCheck($condition, $description) {
    global $pass, $fail;
    if ($condition) {
        echo " [PASS] $description\n";
        $pass++;
    } else {
        echo " [FAIL] $description\n";
        $fail++;
    }
}

// 1. PHP Syntax validation
$filesToLint = [
    'app/Views/queue/index.php',
    'app/Controllers/QueueController.php',
    'app/Models/QueueEntry.php'
];

foreach ($filesToLint as $f) {
    $full = __DIR__ . '/../' . $f;
    $output = [];
    $ret = 0;
    exec("php -l \"$full\"", $output, $ret);
    assertCheck($ret === 0, "Syntax validation: $f is valid PHP");
}

// 2. Database Schema Check
$db = \App\Core\Database::getInstance()->getConnection();
$cols = $db->query("SHOW COLUMNS FROM queue_entries LIKE 'priority_level'")->fetchAll(PDO::FETCH_ASSOC);
assertCheck(!empty($cols), 'queue_entries table has priority_level column');
if (!empty($cols)) {
    assertCheck($cols[0]['Default'] === 'Regular', 'priority_level column defaults to "Regular"');
}

// 3. View Verification (app/Views/queue/index.php)
$indexContent = file_get_contents(__DIR__ . '/../app/Views/queue/index.php');

assertCheck(
    strpos($indexContent, '<select name="patient_id" id="patient_id"') === false,
    'index.php removed legacy unsearchable <select name="patient_id">'
);
assertCheck(
    strpos($indexContent, 'id="patientSearchInput"') !== false,
    'index.php has #patientSearchInput live search field'
);
assertCheck(
    strpos($indexContent, 'id="searchResultsContainer"') !== false,
    'index.php has #searchResultsContainer dropdown container'
);
assertCheck(
    strpos($indexContent, 'id="searchLoadingSpinner"') !== false,
    'index.php has #searchLoadingSpinner loading indicator'
);
assertCheck(
    strpos($indexContent, 'id="patientIdentityCard"') !== false,
    'index.php has #patientIdentityCard compact summary card'
);
assertCheck(
    strpos($indexContent, 'id="btnChangePatient"') !== false,
    'index.php has #btnChangePatient action button'
);
assertCheck(
    strpos($indexContent, 'id="selectedPatientId"') !== false,
    'index.php has #selectedPatientId hidden input'
);
assertCheck(
    strpos($indexContent, 'name="service_type"') !== false,
    'index.php includes service_type selection input'
);

$expectedServices = [
    'General OPD',
    'Prenatal Care',
    'Well Baby Immunization',
    'Senior Care',
    'Family Planning',
    'Dental Care',
    'NCD / Hypertension'
];
$allServicesPresent = true;
foreach ($expectedServices as $svc) {
    if (strpos($indexContent, 'value="' . $svc . '"') === false) {
        $allServicesPresent = false;
        break;
    }
}
assertCheck($allServicesPresent, 'index.php provides all 7 clinical service options in service_type');

assertCheck(
    strpos($indexContent, 'name="priority_level"') === false,
    'index.php removed priority_level selector (Health center operates on pure FCFS)'
);

assertCheck(
    strpos($indexContent, 'FCFS Order') !== false,
    'index.php clearly displays FCFS Order badge'
);

assertCheck(
    strpos($indexContent, '<th class="text-start">Service</th>') !== false,
    'index.php active queue table has Service column header'
);
assertCheck(
    strpos($indexContent, '"targets": 6') !== false,
    'index.php DataTables config updates non-orderable targets to column index 6'
);

// 4. Controller Verification (app/Controllers/QueueController.php)
$controllerContent = file_get_contents(__DIR__ . '/../app/Controllers/QueueController.php');
assertCheck(
    strpos($controllerContent, 'patientModel->allActive()') === false,
    'QueueController::index() does NOT perform allActive() patient loading'
);
assertCheck(
    strpos($controllerContent, 'serviceType') !== false,
    'QueueController::store() processes and validates service_type'
);

// 5. Functional DB test
$queueModel = new \App\Models\QueueEntry();
$todayList = $queueModel->findAllToday();
assertCheck(is_array($todayList), 'QueueEntry::findAllToday() successfully executes and returns an array');

echo "\nSummary: $pass Passed, $fail Failed.\n";
if ($fail > 0) {
    exit(1);
} else {
    echo "All Phase 1 tests passed successfully!\n";
    exit(0);
}
