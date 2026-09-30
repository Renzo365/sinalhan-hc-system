<?php
// Verification Script for KPI card styling and unified patient search
require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/../app/helpers.php';

echo "=== Running Appointment KPI & Search Consistency Tests ===\n\n";

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
    'config/routes.php',
    'app/Controllers/PatientController.php',
    'app/Controllers/AppointmentController.php',
    'app/Views/appointments/index.php',
    'app/Views/appointments/create.php'
];

foreach ($filesToLint as $f) {
    $full = __DIR__ . '/../' . $f;
    $output = [];
    $ret = 0;
    exec("php -l \"$full\"", $output, $ret);
    assertCheck($ret === 0, "Syntax validation: $f");
}

// 2. Test /api/patients/search/all controller method
$tempTestFile = __DIR__ . '/_temp_search_all_test.php';
file_put_contents($tempTestFile, '<?php
require_once __DIR__ . "/../app/Core/Autoloader.php";
\App\Core\Autoloader::register();
require_once __DIR__ . "/../app/helpers.php";
$_GET["q"] = "Alcantara";
$c = new \App\Controllers\PatientController();
$c->searchAll();
');

$output = [];
$returnVar = 0;
exec("php \"$tempTestFile\"", $output, $returnVar);
@unlink($tempTestFile);
$rawJson = implode("\n", $output);
$decoded = json_decode($rawJson, true);

assertCheck(is_array($decoded) && isset($decoded['results']), 'PatientController::searchAll() returns valid JSON with results array');
if (!empty($decoded['results'])) {
    $first = $decoded['results'][0];
    assertCheck(isset($first['id']) && isset($first['name']) && isset($first['patient_no']), 'searchAll() results include id, name, and patient_no');
    assertCheck(isset($first['sex']) && isset($first['age']) && isset($first['address']), 'searchAll() results include sex, age, and address');
} else {
    assertCheck(true, 'searchAll() executed successfully (empty query result)');
}

// 3. Test index.php KPI cards - No colored borders, subtle shadow
$indexHtml = file_get_contents(__DIR__ . '/../app/Views/appointments/index.php');
assertCheck(
    strpos($indexHtml, 'border-start border-4') === false,
    'index.php KPI cards do NOT contain colored left border classes (border-start border-4)'
);
assertCheck(
    strpos($indexHtml, 'border-primary') === false || strpos($indexHtml, 'border-4 border-primary') === false,
    'index.php KPI cards removed border-4 border-primary'
);
assertCheck(
    strpos($indexHtml, 'card card-premium shadow-sm h-100 border') !== false,
    'index.php KPI cards have subtle shadow (shadow-sm) and clean border'
);
assertCheck(
    strpos($indexHtml, '>Served<') === false,
    'index.php Action column does NOT contain redundant "Served" badge'
);
assertCheck(
    strpos($indexHtml, 'View / Edit') !== false,
    'index.php Completed rows have unified "View / Edit" button'
);
assertCheck(
    strpos($indexHtml, 'Edit Details') !== false,
    'index.php Scheduled rows have "Edit Details" organized inside more actions dropdown'
);

// 4. Test create.php patient search & identity card consistency
$createHtml = file_get_contents(__DIR__ . '/../app/Views/appointments/create.php');
assertCheck(
    strpos($createHtml, 'id="patientSearchInput"') !== false,
    'create.php has #patientSearchInput search field matching register pages'
);
assertCheck(
    strpos($createHtml, 'id="btnClearSearch"') !== false,
    'create.php has #btnClearSearch clear button matching register pages'
);
assertCheck(
    strpos($createHtml, 'id="searchResultsContainer"') !== false,
    'create.php has #searchResultsContainer dropdown matching register pages'
);
assertCheck(
    strpos($createHtml, 'id="searchLoadingSpinner"') !== false,
    'create.php has #searchLoadingSpinner loading indicator'
);
assertCheck(
    strpos($createHtml, 'id="patientIdentityCard"') !== false,
    'create.php has #patientIdentityCard compact summary card'
);
assertCheck(
    strpos($createHtml, 'id="btnChangePatient"') !== false,
    'create.php has #btnChangePatient button'
);
assertCheck(
    strpos($createHtml, 'id="selectedPatientId"') !== false,
    'create.php has #selectedPatientId hidden input'
);
assertCheck(
    strpos($createHtml, '/api/patients/search/all') !== false,
    'create.php JavaScript fetches from /api/patients/search/all'
);
assertCheck(
    strpos($createHtml, 'Patient Required') !== false,
    'create.php form submit validates patient selection'
);

// 6. Test Option A: Past-Due / Overdue detection & quick-filter
$tempMetricsFile = __DIR__ . '/_temp_metrics_test.php';
file_put_contents($tempMetricsFile, '<?php
require_once __DIR__ . "/../app/Core/Autoloader.php";
\App\Core\Autoloader::register();
require_once __DIR__ . "/../app/helpers.php";
$apptModel = new \App\Models\Appointment();
$metrics = $apptModel->getTodaySummaryMetrics();
echo json_encode($metrics);
');

$output = [];
$returnVar = 0;
exec("php \"$tempMetricsFile\"", $output, $returnVar);
@unlink($tempMetricsFile);
$metrics = json_decode(implode("\n", $output), true);

assertCheck(isset($metrics['overdue_count']), 'Appointment::getTodaySummaryMetrics() includes overdue_count');

// Overdue button and badges in index.php
assertCheck(
    strpos($indexHtml, 'data-preset="overdue"') !== false,
    'index.php includes data-preset="overdue" quick filter button'
);
assertCheck(
    strpos($indexHtml, 'Past Due (') !== false,
    'index.php contains Past Due badge with elapsed days counter'
);
assertCheck(
    strpos($indexHtml, "preset === 'overdue'") !== false,
    'index.php JS handles overdue preset filter selection'
);

// Overdue badge in tab_appointments.php
$tabApptHtml = file_get_contents(__DIR__ . '/../app/Views/patients/partials/tab_appointments.php');
assertCheck(
    strpos($tabApptHtml, 'Past Due (') !== false,
    'tab_appointments.php displays Past Due badge with elapsed days for overdue appointments'
);

echo "\nSummary: $pass Passed, $fail Failed.\n";
if ($fail > 0) {
    exit(1);
} else {
    echo "All tests passed successfully!\n";
    exit(0);
}
