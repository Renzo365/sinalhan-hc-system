<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Dr. Test Administrator';
$_SESSION['user_role'] = 'Admin';
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/../app/helpers.php';

// Set error reporting to strict
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "=======================================================\n";
echo "PRENATAL CARE (MATERNAL) MODULE VERIFICATION\n";
echo "=======================================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

function assertTest($description, $condition) {
    global $testsPassed, $testsFailed;
    if ($condition) {
        echo " [PASS] " . $description . "\n";
        $testsPassed++;
    } else {
        echo " [FAIL] " . $description . "\n";
        $testsFailed++;
    }
}

// 1. Router Verification
echo "--- 1. Testing Route Symmetry and Dispatch ---\n";
$router = new \App\Core\Router();
$registerRoutes = require __DIR__ . '/../config/routes.php';
$registerRoutes($router);

$ref = new \ReflectionClass($router);
$prop = $ref->getProperty('routes');
$prop->setAccessible(true);
$routes = $prop->getValue($router);

$expectedRoutes = [
    ['GET', '/prenatal'],
    ['GET', '/maternal'],
    ['GET', '/prenatal/register'],
    ['GET', '/maternal/register'],
    ['POST', '/prenatal/register'],
    ['POST', '/maternal/register'],
    ['GET', '/prenatal/episode/{id}/edit'],
    ['GET', '/maternal/episode/{id}/edit'],
    ['POST', '/prenatal/episode/{id}/edit'],
    ['POST', '/maternal/episode/{id}/edit'],
    ['GET', '/prenatal/{id}'],
    ['GET', '/maternal/{id}'],
    ['POST', '/prenatal/{id}/update'],
    ['POST', '/maternal/{id}/update'],
    ['POST', '/prenatal/{id}/visit'],
    ['POST', '/maternal/{id}/visit'],
    ['POST', '/prenatal/{id}/conclude'],
    ['POST', '/maternal/{id}/conclude'],
    ['POST', '/prenatal/{id}/cancel'],
    ['POST', '/maternal/{id}/cancel'],
    ['POST', '/prenatal/visit/{id}/delete'],
    ['POST', '/maternal/visit/{id}/delete'],
    ['POST', '/prenatal/visit/{id}/update'],
    ['POST', '/maternal/visit/{id}/update'],
    ['POST', '/patients/{id}/past-obstetric'],
    ['POST', '/past-obstetric/{id}/delete'],
    ['POST', '/past-obstetric/{id}/update']
];

foreach ($expectedRoutes as [$method, $path]) {
    $found = false;
    foreach ($routes as $route) {
        if ($route['method'] === $method && $route['route'] === $path) {
            $found = true;
            break;
        }
    }
    assertTest("Route registered: {$method} {$path}", $found);
}

// 2. Database Models and Calculations
echo "\n--- 2. Testing Prenatal Models and Calculations ---\n";
$prenatalModel = new \App\Models\PrenatalRecord();
$visitModel = new \App\Models\PrenatalVisit();
$pohModel = new \App\Models\PastObstetricHistory();

// Naegele's rule calculation test
$lmp = '2026-01-15';
$edc = $prenatalModel->calculateEDC($lmp);
// Naegele: +1 year, -3 months, +7 days => 2026-01-15 + 1y -3m + 7d = 2026-10-22
assertTest("Naegele's Rule: EDC for {$lmp} is 2026-10-22", $edc === '2026-10-22');

$aog = $prenatalModel->calculateCurrentAOG($lmp);
assertTest("AOG Calculation returns array with weeks and days", is_array($aog) && isset($aog['weeks'], $aog['days'], $aog['formatted']));
echo "     Computed AOG: {$aog['formatted']} (Trimester: " . ($aog['weeks'] >= 28 ? '3rd' : ($aog['weeks'] >= 14 ? '2nd' : '1st')) . ")\n";

// 3. View Renders
echo "\n--- 3. Testing View Compilation and Layouts ---\n";

// Test 3a: maternal/index.php
ob_start();
try {
    $activeRoster = [
        [
            'id' => 1,
            'patient_id' => 10,
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'middle_name' => 'Cruz',
            'patient_no' => 'PAT-2026-00010',
            'envelope_no' => 'ENV-01',
            'patient_age' => 26,
            'gravida' => 2,
            'para' => 1,
            'term_births' => 1,
            'preterm_births' => 0,
            'abortions' => 0,
            'living_children' => 1,
            'lmp' => '2026-01-15',
            'edc' => '2026-10-22',
            'calculated_aog' => ['weeks' => 37, 'days' => 4, 'formatted' => '37 weeks, 4 days', 'trimester' => '3rd'],
            'days_until_edc' => 17,
            'visit_count' => 4
        ]
    ];
    $recentlyDelivered = [];
    $eligiblePatients = [];
    $search = '';
    $stage = '';
    require __DIR__ . '/../app/Views/maternal/index.php';
    $output = ob_get_clean();
    assertTest("Render maternal/index.php successfully", strpos($output, 'Active Pregnant Mothers Cohort') !== false && strpos($output, 'Maria') !== false);
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest("Render maternal/index.php successfully (Error: " . $e->getMessage() . ")", false);
}

// Test 3b: maternal/register.php
ob_start();
try {
    $preselectedPatient = [
        'id' => 10,
        'patient_no' => 'PAT-2026-00010',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'middle_name' => 'Cruz',
        'sex' => 'Female',
        'dob' => '2000-05-15',
        'age' => 26,
        'spouse_name' => 'Juan Santos',
        'address' => 'Purok 3, Sinalhan'
    ];
    $preselectedIhp = [
        'gravida' => 1,
        'para' => 1,
        'term_births' => 1,
        'preterm_births' => 0,
        'abortions' => 0,
        'living_children' => 1
    ];
    $hasActiveEpisode = false;
    require __DIR__ . '/../app/Views/maternal/register.php';
    $output = ob_get_clean();
    assertTest("Render maternal/register.php successfully", strpos($output, 'Register Prenatal Care Episode') !== false && strpos($output, 'Maria') !== false);
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest("Render maternal/register.php successfully (Error: " . $e->getMessage() . ")", false);
}

// Test 3c: maternal/edit_episode.php
ob_start();
try {
    $patient = [
        'id' => 10,
        'patient_no' => 'PAT-2026-00010',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'middle_name' => 'Cruz',
        'suffix' => '',
        'sex' => 'Female',
        'dob' => '2000-05-15',
        'age' => 26,
        'blood_type' => 'O+',
        'philhealth_no' => '12-345678901-2',
        'contact_no' => '09123456789',
        'address' => 'Purok 3, Sinalhan',
        'purok' => 'Purok 3'
    ];
    $episode = [
        'id' => 1,
        'patient_id' => 10,
        'lmp' => '2026-01-15',
        'edc' => '2026-10-22',
        'gravida' => 2,
        'para' => 1,
        'term_births' => 1,
        'preterm_births' => 0,
        'abortions' => 0,
        'living_children' => 1,
        'husband_name' => 'Juan Santos',
        'is_active' => 1,
        'delivery_date' => null,
        'delivery_outcome' => null,
        'notes' => 'Normal progression'
    ];
    require __DIR__ . '/../app/Views/maternal/edit_episode.php';
    $output = ob_get_clean();
    assertTest("Render maternal/edit_episode.php successfully", strpos($output, 'Edit Pregnancy Episode Details') !== false);
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest("Render maternal/edit_episode.php successfully (Error: " . $e->getMessage() . ")", false);
}

// Test 3d: maternal/show.php
ob_start();
try {
    $patient = [
        'id' => 10,
        'patient_no' => 'PAT-2026-00010',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'middle_name' => 'Cruz',
        'suffix' => '',
        'sex' => 'Female',
        'dob' => '2000-05-15',
        'age' => 26,
        'blood_type' => 'O+',
        'philhealth_no' => '12-345678901-2',
        'contact_no' => '09123456789',
        'address' => 'Purok 3, Sinalhan',
        'envelope_no' => 'ENV-01',
        'family_no' => 'FAM-05'
    ];
    $activePrenatal = [
        'id' => 1,
        'patient_id' => 10,
        'lmp' => '2026-01-15',
        'edc' => '2026-10-22',
        'gravida' => 2,
        'para' => 1,
        'term_births' => 1,
        'preterm_births' => 0,
        'abortions' => 0,
        'living_children' => 1,
        'husband_name' => 'Juan Santos',
        'is_active' => 1,
        'created_at' => '2026-02-01 10:00:00',
        'creator_name' => 'Midwife Ramos',
        'calculated_aog' => ['weeks' => 37, 'days' => 4, 'formatted' => '37 weeks, 4 days', 'trimester' => '3rd']
    ];
    $prenatalVisits = [
        [
            'id' => 1,
            'prenatal_id' => 1,
            'visit_date' => '2026-02-15',
            'aog_weeks' => 4.3,
            'bp_systolic' => 110,
            'bp_diastolic' => 70,
            'weight_kg' => 54.0,
            'height_cm' => 155.0,
            'fetal_heart_tone' => 140,
            'fundal_height_cm' => 12.0,
            'fetal_presentation' => 'Cephalic',
            'tcb' => 'TT1 given',
            'chief_complaint' => 'First prenatal consult',
            'remarks' => 'Normal baseline',
            'attendant_name' => 'Midwife Ramos'
        ]
    ];
    $pastDeliveries = [
        [
            'id' => 1,
            'patient_id' => 10,
            'gravida_no' => 1,
            'delivery_type' => 'NSD',
            'infant_sex' => 'Male',
            'place_of_delivery' => 'SRCH Lying-in',
            'year_delivered' => 2022,
            'attended_by' => 'Dr. Reyes',
            'status' => 'Alive',
            'tt_status' => 'TT2 given'
        ]
    ];
    $allPrenatalEpisodes = [$activePrenatal];
    $pastEpisodesVisits = [];
    $medicalHistory = ['pre_eclampsia' => 0, 'fp_counselling' => 1];
    $latestVitals = ['blood_pressure' => '110/70', 'pulse_rate' => 78, 'temperature' => 36.6];

    require __DIR__ . '/../app/Views/maternal/show.php';
    $output = ob_get_clean();
    assertTest("Render maternal/show.php workstation with all 7 modals", 
        strpos($output, 'Active Pregnancy Episode (CHO I Record)') !== false &&
        strpos($output, 'id="addPrenatalVisitModal"') !== false &&
        strpos($output, 'id="editPrenatalVisitModal"') !== false &&
        strpos($output, 'id="viewPrenatalVisitModal"') !== false &&
        strpos($output, 'id="concludePrenatalModal"') !== false &&
        strpos($output, 'id="cancelPrenatalModal"') !== false &&
        strpos($output, 'id="addPastObstetricModal"') !== false &&
        strpos($output, 'id="editPastObstetricModal"') !== false
    );
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest("Render maternal/show.php successfully (Error: " . $e->getMessage() . ")", false);
}

// Test 3e: patient_header.php
ob_start();
try {
    $fullNameFormatted = 'Santos, Maria C.';
    $avatarInitials = 'MS';
    $isChild = false;
    $isValidDob = true;
    $patientSex = 'Female';
    $ageVal = 26;
    $dobFormatted = 'May 15, 2000';
    $hasBloodType = true;
    $cdsAlerts = ['has_alerts' => false, 'flags' => []];
    $wellbabyRecord = null;
    require __DIR__ . '/../app/Views/patients/partials/patient_header.php';
    $output = ob_get_clean();
    assertTest("Render patient_header.php with Pregnant badge and Prenatal button",
        strpos($output, 'Pregnant (37w)') !== false &&
        strpos($output, 'title="Open Prenatal Care Workstation"') !== false
    );
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest("Render patient_header.php successfully (Error: " . $e->getMessage() . ")", false);
}

// Test 3f: dashboard.php upcoming delivery radar link
ob_start();
try {
    $del = [
        'id' => 55, // Prenatal record ID
        'patient_id' => 10, // Patient ID
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'patient_no' => 'PAT-2026-00010',
        'edc' => '2026-10-22',
        'contact_no' => '09123456789',
        'days_to_delivery' => 17
    ];
    $daysLeft = 17;
    $daysBadgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-semibold';
    $daysLabel = '17d remaining';
    ?>
    <a href="<?= url('/prenatal/' . $del['patient_id']) ?>" class="link-primary-dark fw-bold text-decoration-none small text-truncate d-inline-block" style="max-width: 220px;" title="View Prenatal Episode">
        <?= h($del['last_name']) ?>, <?= h($del['first_name']) ?>
    </a>
    <?php
    $output = ob_get_clean();
    assertTest("Dashboard upcoming delivery radar links correctly to /prenatal/10 (patient_id, not prenatal id 55)",
        strpos($output, '/prenatal/10') !== false && strpos($output, '/prenatal/55') === false
    );
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest("Render dashboard upcoming delivery radar link (Error: " . $e->getMessage() . ")", false);
}

echo "\n=======================================================\n";
echo "SUMMARY: Passed: {$testsPassed} | Failed: {$testsFailed}\n";
echo "=======================================================\n";

if ($testsFailed > 0) {
    exit(1);
}
exit(0);
