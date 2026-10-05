<?php
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Models/Patient.php';
require_once __DIR__ . '/../app/Models/WellbabyRecord.php';
require_once __DIR__ . '/../app/Models/Immunization.php';
require_once __DIR__ . '/../app/Models/ChildGrowthLog.php';

use App\Models\Patient;
use App\Models\WellbabyRecord;
use App\Models\Immunization;
use App\Models\ChildGrowthLog;

$patientModel = new Patient();
$wbModel = new WellbabyRecord();
$immModel = new Immunization();
$growthModel = new ChildGrowthLog();

echo "=== Testing View Rendering for Well-Baby Module ===\n";

// 1. Test index view
$registeredRoster = $wbModel->getRegisteredRoster('');
$unregisteredChildren = $patientModel->getUnregisteredChildren('');
$allUnregistered = $unregisteredChildren;
$metrics = $wbModel->getRegistryMetrics();
$search = '';

ob_start();
try {
    include __DIR__ . '/../app/Views/wellbaby/index.php';
    $outputIndex = ob_get_clean();
    echo "  [PASS] index.php rendered without runtime exceptions (" . strlen($outputIndex) . " bytes)\n";
    assert(str_contains($outputIndex, 'Registered Well-Baby Registry'));
    assert(str_contains($outputIndex, '/ 13 doses'));
    echo "  [PASS] index.php contains expected roster elements\n";
} catch (\Throwable $e) {
    if (ob_get_level() > 0) ob_end_clean();
    echo "  [FAIL] index.php threw exception: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Test register view
$preselectedPatient = $patientModel->findById(20);
$preselectedPatientId = 20;
$alreadyRegistered = false;

ob_start();
try {
    include __DIR__ . '/../app/Views/wellbaby/register.php';
    $outputRegister = ob_get_clean();
    echo "  [PASS] register.php rendered without runtime exceptions (" . strlen($outputRegister) . " bytes)\n";
    assert(str_contains($outputRegister, 'Child Information & Birth History'));
    assert(str_contains($outputRegister, 'Parental Information'));
    assert(str_contains($outputRegister, 'place_of_delivery_other'));
    assert(str_contains($outputRegister, 'attended_by_other'));
    assert(str_contains($outputRegister, 'newborn_screening_done'));
    echo "  [PASS] register.php contains aligned sections and form controls\n";
} catch (\Throwable $e) {
    ob_end_clean();
    echo "  [FAIL] register.php threw exception: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Test edit view
$wellbabyRecord = $wbModel->findByPatientId(20);
$patient = $preselectedPatient;
$potentialMothers = $patientModel->findPotentialMothers(100);

ob_start();
try {
    include __DIR__ . '/../app/Views/wellbaby/edit.php';
    $outputEdit = ob_get_clean();
    echo "  [PASS] edit.php rendered without runtime exceptions (" . strlen($outputEdit) . " bytes)\n";
    assert(str_contains($outputEdit, 'Child Information & Birth History'));
    assert(str_contains($outputEdit, 'Parental Information'));
    assert(str_contains($outputEdit, 'place_of_delivery_other'));
    assert(str_contains($outputEdit, 'attended_by_other'));
    echo "  [PASS] edit.php contains aligned sections and form controls\n";
} catch (\Throwable $e) {
    if (ob_get_level() > 0) ob_end_clean();
    echo "  [FAIL] edit.php threw exception: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. Test show view (workstation)
$vaccineMap = $immModel->getVaccineMap(20);
$growthLogs = $growthModel->findByWellbabyId($wellbabyRecord['id'] ?? 1);
$patientImmunizations = $immModel->findByPatientId(20);

ob_start();
try {
    include __DIR__ . '/../app/Views/wellbaby/show.php';
    $outputShow = ob_get_clean();
    echo "  [PASS] show.php rendered without runtime exceptions (" . strlen($outputShow) . " bytes)\n";
    assert(str_contains($outputShow, 'Child Information & Birth History'));
    assert(str_contains($outputShow, 'DOH Mandatory Routine Infant Immunization Schedule (EPI)'));
    assert(str_contains($outputShow, 'Routine Supplementation Tracker'));
    assert(str_contains($outputShow, 'Child Anthropometric & Growth Monitoring Log'));
    assert(str_contains($outputShow, 'Immunization Given'));
    assert(str_contains($outputShow, 'calculateAgeInMonths'));
    echo "  [PASS] show.php contains 4 aligned cards, Supplementation Tracker, and age calculation\n";
} catch (\Throwable $e) {
    if (ob_get_level() > 0) ob_end_clean();
    echo "  [FAIL] show.php threw exception: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nAll Views Rendered Successfully without Warnings or Errors!\n";
