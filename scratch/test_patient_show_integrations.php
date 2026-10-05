<?php
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once dirname(__DIR__) . '/app/helpers.php';

echo "=== Testing Patient Show View Rendering with Well-Baby Integrations ===" . PHP_EOL;

function mockPatientRender($patient, $wellbabyRecord = null, $growthLogs = []) {
    ob_start();
    $_SESSION['csrf_token'] = 'test-token-123';
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'admin';

    $vitalsHistory = [];
    $latestVitals = null;
    $consultationsHistory = [];
    $latestConsultation = null;
    $latestConsultationPrescriptions = [];
    $appointmentsHistory = [];
    $queueHistory = [];
    $medicalHistory = [];
    $cdsAlerts = ['has_alerts' => false, 'flags' => []];
    $familyMembers = [];
    $activePrenatal = null;
    $allPrenatalEpisodes = [];
    $patientImmunizations = [];
    $pcbObligated = [];
    $pcbServiceLogs = [];
    $pcbYear = 2026;

    try {
        require dirname(__DIR__) . '/app/Views/patients/show.php';
        $output = ob_get_clean();
        return $output;
    } catch (\Throwable $e) {
        ob_end_clean();
        throw $e;
    }
}

// Scenario 1: Unregistered child aged 1 year (DOB: 1 year ago)
$childUnreg = [
    'id' => 991,
    'patient_no' => 'P-2026-0991',
    'last_name' => 'Pendelton',
    'first_name' => 'Tommy',
    'middle_name' => 'Lee',
    'suffix' => '',
    'sex' => 'Male',
    'age' => 1,
    'dob' => date('Y-m-d', strtotime('-14 months')),
    'contact_no' => '09123456789',
    'address' => 'Barangay Sinalhan',
    'blood_type' => 'O+',
    'envelope_no' => 'E-12',
    'family_no' => 'FAM-01',
    'mother_name' => 'Sarah Pendelton',
    'mother_dob' => '1995-05-15',
    'father_name' => 'Thomas Pendelton',
    'father_dob' => '1993-02-10'
];

$out1 = mockPatientRender($childUnreg, null, []);
assert(strpos($out1, 'Eligible (Aged 0–5)') !== false, "Scenario 1 should display 'Eligible (Aged 0–5)' in overview card");
assert(strpos($out1, 'Child is Eligible for Well-Baby Care') !== false, "Scenario 1 should display eligibility banner in immunizations tab");
assert(strpos($out1, 'Enroll Well-Baby') !== false, "Scenario 1 should display Enroll Well-Baby header button");
assert(strpos($out1, 'mos)') !== false, "Scenario 1 should display pediatric age breakdown");
echo "  [PASS] Scenario 1: Unregistered infant (14 months) shows eligibility banners and pediatric age breakdown" . PHP_EOL;

// Scenario 2: Registered child
$childReg = [
    'id' => 992,
    'patient_no' => 'P-2026-0992',
    'last_name' => 'Reyes',
    'first_name' => 'Baby Girl',
    'middle_name' => 'Santos',
    'suffix' => '',
    'sex' => 'Female',
    'age' => 0,
    'dob' => date('Y-m-d', strtotime('-3 months')),
    'contact_no' => '09181112222',
    'address' => 'Barangay Sinalhan',
    'blood_type' => 'A+',
    'envelope_no' => 'E-15',
    'family_no' => 'FAM-02',
    'mother_name' => 'Maria Reyes',
    'mother_dob' => '1998-10-20',
    'father_name' => 'Jose Reyes',
    'father_dob' => '1996-03-12'
];

$mockWbRecord = [
    'id' => 45,
    'patient_id' => 992,
    'mother_patient_id' => null,
    'birth_weight_kg' => '3.10',
    'birth_length_cm' => '49.5',
    'place_of_delivery' => 'Lying-in',
    'newborn_screening_done' => 1,
    'created_at' => date('Y-m-d H:i:s')
];

$out2 = mockPatientRender($childReg, $mockWbRecord, []);
assert(strpos($out2, 'Registered Infant') !== false, "Scenario 2 should display Registered Infant badge in overview");
assert(strpos($out2, 'Enrolled in Well-Baby &amp; Under-5 EPI') !== false, "Scenario 2 should display enrolled banner in immunizations tab");
assert(strpos($out2, 'Well-Baby Workstation') !== false, "Scenario 2 should display Well-Baby Workstation button in header");
echo "  [PASS] Scenario 2: Registered well-baby child shows workstation badges, cards, and direct links" . PHP_EOL;

// Scenario 3: Adult patient (35 yrs)
$adult = [
    'id' => 993,
    'patient_no' => 'P-2026-0993',
    'last_name' => 'Cruz',
    'first_name' => 'Juan',
    'middle_name' => 'Perez',
    'suffix' => 'Jr.',
    'sex' => 'Male',
    'age' => 35,
    'dob' => '1991-04-10',
    'contact_no' => '09193334444',
    'address' => 'Barangay Sinalhan',
    'blood_type' => 'B+',
    'envelope_no' => 'E-20',
    'family_no' => 'FAM-05'
];

$out3 = mockPatientRender($adult, null, []);
assert(strpos($out3, 'Eligible (Aged 0–5)') === false, "Scenario 3 should NOT show Well-Baby card for adult");
assert(strpos($out3, 'Child is Eligible for Well-Baby Care') === false, "Scenario 3 should NOT show Well-Baby banner in immunizations tab");
assert(strpos($out3, 'Enroll Well-Baby') === false, "Scenario 3 should NOT show Enroll Well-Baby header button for adult");
echo "  [PASS] Scenario 3: Adult patient correctly excludes Well-Baby cards and banners" . PHP_EOL;

echo PHP_EOL . "All Patient Show View Scenarios Passed Successfully!" . PHP_EOL;
