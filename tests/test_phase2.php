<?php

// Check if run from CLI
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

$dbConfig = require dirname(__DIR__) . '/config/database.php';

use App\Models\Patient;
use App\Models\PrenatalRecord;
use App\Models\WellbabyRecord;

echo "\n=======================================================\n";
echo "    PHASE 2 INTAKE & HOUSEHOLD DIRECTORY TEST SUITE    \n";
echo "=======================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($condition, $testName) {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] $testName\n";
        $passCount++;
    } else {
        echo "  [FAIL] $testName\n";
        $failCount++;
    }
}

try {
    // 0. Clean up any remnants of prior test runs
    $pdo = new PDO("mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}", $dbConfig['username'], $dbConfig['password']);
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; DELETE FROM patients WHERE first_name LIKE 'TestP2%' OR family_no = 'FAM-SINALHAN-999'; SET FOREIGN_KEY_CHECKS = 1;");

    $patientModel = new Patient();
    $prenatalModel = new PrenatalRecord();
    $wbModel = new WellbabyRecord();

    // 1. Test Model & Controller Integration
    $controller = new \App\Controllers\PatientController();
    assertTest($controller instanceof \App\Controllers\PatientController, "PatientController initialized");

    // Reflection to test protected validatePatientData
    $refMethod = new \ReflectionMethod(\App\Controllers\PatientController::class, 'validatePatientData');
    $refMethod->setAccessible(true);

    // 2. Test Validation: Invalid Suffix
    $invalidData = [
        'first_name' => 'TestP2',
        'last_name' => 'Patient',
        'dob' => '1995-01-01',
        'sex' => 'Male',
        'civil_status' => 'Single',
        'barangay' => 'Sinalhan',
        'address' => 'Sample Address',
        'suffix' => 'VeryLongSuffixThatExceedsTwentyChars'
    ];
    $errors = $refMethod->invoke($controller, $invalidData);
    assertTest(count($errors) > 0, "Validation caught invalid suffix length");

    // 3. Test Validation: Invalid Education Attainment
    $invalidData['suffix'] = 'Jr.';
    $invalidData['education_attainment'] = 'Invalid Education Degree';
    $errors = $refMethod->invoke($controller, $invalidData);
    assertTest(count($errors) > 0, "Validation caught invalid educational attainment");

    // 4. Test Validation: Future Family DOB
    $invalidData['education_attainment'] = 'High School';
    $invalidData['father_name'] = 'Test Father';
    $invalidData['father_dob'] = date('Y-m-d', strtotime('+1 year'));
    $errors = $refMethod->invoke($controller, $invalidData);
    assertTest(count($errors) > 0, "Validation caught future father DOB");

    // 5. Test Creating Mother, Father, and Child in Same Household (FAM-SINALHAN-999)
    $motherData = [
        'first_name' => 'TestP2Mother',
        'middle_name' => 'Santos',
        'last_name' => 'Dela Cruz',
        'suffix' => null,
        'dob' => '1996-08-10',
        'sex' => 'Female',
        'civil_status' => 'Married',
        'blood_type' => 'O+',
        'religion' => 'Roman Catholic',
        'occupation' => 'Homemaker',
        'education_attainment' => 'High School',
        'family_no' => 'FAM-SINALHAN-999',
        'contact_no' => '09171112233',
        'barangay' => 'Sinalhan',
        'address' => 'Blk 1 Lot 5 Purok 4',
        'phic_status' => 'Member',
        'phic_type' => 'Sponsored - NHTS',
        'philhealth_no' => '12-888888888-1',
        'father_name' => 'Pedro Santos',
        'father_dob' => '1965-05-12',
        'mother_name' => 'Juana Santos',
        'mother_dob' => '1968-07-20',
        'spouse_name' => 'TestP2Father Dela Cruz',
        'spouse_dob' => '1994-03-15',
        'emergency_name' => 'TestP2Father Dela Cruz',
        'emergency_relationship' => 'Spouse',
        'emergency_no' => '09182223344',
        'created_by' => 1
    ];

    $motherId = $patientModel->create($motherData);
    assertTest($motherId > 0, "Created Mother with Family No FAM-SINALHAN-999 (ID: $motherId)");

    $fatherData = [
        'first_name' => 'TestP2Father',
        'middle_name' => 'Reyes',
        'last_name' => 'Dela Cruz',
        'suffix' => 'Sr.',
        'dob' => '1994-03-15',
        'sex' => 'Male',
        'civil_status' => 'Married',
        'blood_type' => 'A+',
        'religion' => 'Roman Catholic',
        'occupation' => 'Driver',
        'education_attainment' => 'Vocational',
        'family_no' => 'FAM-SINALHAN-999',
        'contact_no' => '09182223344',
        'barangay' => 'Sinalhan',
        'address' => 'Blk 1 Lot 5 Purok 4',
        'phic_status' => 'Dependent',
        'phic_type' => 'Sponsored - NHTS',
        'spouse_name' => 'TestP2Mother Dela Cruz',
        'spouse_dob' => '1996-08-10',
        'emergency_name' => 'TestP2Mother Dela Cruz',
        'emergency_relationship' => 'Spouse',
        'emergency_no' => '09171112233',
        'created_by' => 1
    ];

    $fatherId = $patientModel->create($fatherData);
    assertTest($fatherId > 0, "Created Father with Family No FAM-SINALHAN-999 (ID: $fatherId)");

    $childData = [
        'first_name' => 'TestP2Child',
        'middle_name' => 'Santos',
        'last_name' => 'Dela Cruz',
        'suffix' => 'Jr.',
        'dob' => date('Y-m-d', strtotime('-1 year')),
        'sex' => 'Male',
        'civil_status' => 'Single',
        'blood_type' => 'O+',
        'family_no' => 'FAM-SINALHAN-999',
        'barangay' => 'Sinalhan',
        'address' => 'Blk 1 Lot 5 Purok 4',
        'phic_status' => 'Dependent',
        'father_name' => 'TestP2Father Dela Cruz',
        'mother_name' => 'TestP2Mother Santos',
        'emergency_name' => 'TestP2Mother Dela Cruz',
        'emergency_relationship' => 'Mother',
        'emergency_no' => '09171112233',
        'created_by' => 1
    ];

    $childId = $patientModel->create($childData);
    assertTest($childId > 0, "Created Child with Family No FAM-SINALHAN-999 (ID: $childId)");

    // 6. Test Directory Search by Family Number (Household Clustering)
    $familySearch = $patientModel->allActive(['search' => 'FAM-SINALHAN-999']);
    assertTest(count($familySearch) === 3, "Directory search for 'FAM-SINALHAN-999' returned all 3 family members");

    // 7. Test Directory Search by PhilHealth PIN
    $phicSearch = $patientModel->allActive(['search' => '12-888888888-1']);
    assertTest(count($phicSearch) === 1 && $phicSearch[0]['id'] == $motherId, "Directory search by PhilHealth PIN '12-888888888-1' found Mother");

    // 8. Test Dynamic Program Badge Attribution
    // Before active pregnancy, mother is adult OPD
    $motherRow = null;
    $childRow = null;
    foreach ($familySearch as $row) {
        if ($row['id'] == $motherId) $motherRow = $row;
        if ($row['id'] == $childId) $childRow = $row;
    }
    assertTest($motherRow['program_badge']['tag'] === 'opd', "Mother initial program badge is General OPD");
    assertTest($childRow['program_badge']['tag'] === 'wellbaby', "Child (1 yr) program badge is Well Baby");

    // Add active pregnancy episode for Mother
    $lmp = date('Y-m-d', strtotime('-12 weeks'));
    $prenatalModel->createEpisode([
        'patient_id' => $motherId,
        'husband_name' => 'TestP2Father Dela Cruz',
        'lmp' => $lmp,
        'edc' => $prenatalModel->calculateEDC($lmp),
        'is_active' => 1,
        'created_by' => 1
    ]);

    // Re-query mother
    $refreshedList = $patientModel->allActive(['search' => 'TestP2Mother']);
    assertTest(count($refreshedList) === 1 && $refreshedList[0]['program_badge']['tag'] === 'prenatal', "Mother badge updated dynamically to 'prenatal' with active episode");

    // 9. Test Program Category Filter Dropdown
    $prenatalFilter = $patientModel->allActive(['program_type' => 'prenatal', 'search' => 'TestP2']);
    assertTest(count($prenatalFilter) === 1 && $prenatalFilter[0]['id'] == $motherId, "Filter 'program_type=prenatal' filtered exclusively to Mother");

    $wellbabyFilter = $patientModel->allActive(['program_type' => 'wellbaby', 'search' => 'TestP2']);
    assertTest(count($wellbabyFilter) === 1 && $wellbabyFilter[0]['id'] == $childId, "Filter 'program_type=wellbaby' filtered exclusively to Child");

    // 10. Test Age Bracket Filter
    $infantFilter = $patientModel->allActive(['age_group' => 'infant', 'search' => 'TestP2']);
    assertTest(count($infantFilter) === 1 && $infantFilter[0]['id'] == $childId, "Filter 'age_group=infant' returned Child");

    // 11. Test Patient Edit Update
    $updateSuccess = $patientModel->update($fatherId, array_merge($fatherData, [
        'occupation' => 'Senior Mechanic',
        'education_attainment' => 'College / Post-Graduate',
        'updated_by' => 1
    ]));
    assertTest($updateSuccess === true, "Updated Father demographic details");

    $updatedFather = $patientModel->findById($fatherId);
    assertTest($updatedFather['occupation'] === 'Senior Mechanic' && $updatedFather['education_attainment'] === 'College / Post-Graduate', "Verified updated fields persisted in database");

    // 12. Cleanup Test Records
    echo "\nCleaning up Phase 2 test records...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; DELETE FROM patients WHERE id IN ($motherId, $fatherId, $childId); SET FOREIGN_KEY_CHECKS = 1;");
    echo "  [+] Cleaned up test patient records and cascaded child entries.\n";

} catch (Exception $e) {
    echo "\n[ERROR]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $failCount++;
}

echo "\n=======================================================\n";
echo "PHASE 2 TEST RESULTS: $passCount PASSED, $failCount FAILED\n";
echo "=======================================================\n\n";

exit($failCount > 0 ? 1 : 0);
