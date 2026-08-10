<?php
/**
 * Automated Verification Test Suite for Patients Module Validation & New Demographics
 */

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();

use App\Models\Patient;

// Helper to access protected method validatePatientData via reflection
$controller = new \App\Controllers\PatientController();
$reflection = new \ReflectionClass($controller);
$validateMethod = $reflection->getMethod('validatePatientData');
$validateMethod->setAccessible(true);

echo "--- STARTING PATIENTS MODULE VALIDATION TEST SUITE ---\n\n";

// Test 1: Rejection of Numbers in Names
echo "Test 1: Testing Rejection of Numbers in Name Fields...\n";
$badNameData = [
    'first_name' => '12133',
    'last_name' => 'Pore',
    'dob' => '1995-05-20',
    'sex' => 'Male',
    'civil_status' => 'Single',
    'address' => 'Purok 6, Sinalhan',
    'barangay' => 'Sinalhan'
];
$errors = $validateMethod->invoke($controller, $badNameData);
if (!empty($errors) && strpos(implode('|', $errors), 'no numbers allowed') !== false) {
    echo "  PASS: Correctly rejected numeric input '12133' in First Name. Errors: " . implode('; ', $errors) . "\n";
} else {
    echo "  FAIL: Failed to reject numeric input in First Name.\n";
    exit(1);
}

// Test 2: Rejection of Invalid Mobile Numbers
echo "\nTest 2: Testing Rejection of Invalid Mobile Numbers...\n";
$badPhoneData = array_merge($badNameData, [
    'first_name' => 'Juan',
    'contact_no' => 'qwe2r21'
]);
$errors = $validateMethod->invoke($controller, $badPhoneData);
if (!empty($errors) && strpos(implode('|', $errors), '11-digit Philippine mobile number') !== false) {
    echo "  PASS: Correctly rejected invalid contact number 'qwe2r21'. Errors: " . implode('; ', $errors) . "\n";
} else {
    echo "  FAIL: Failed to reject letter characters in contact number.\n";
    exit(1);
}

// Test 3: Date of Birth Bounds Validation
echo "\nTest 3: Testing Date of Birth Bounds (Future Date & Pre-1900 Date)...\n";
$futureDobData = array_merge($badNameData, [
    'first_name' => 'Juan',
    'dob' => '2099-12-31'
]);
$errorsFuture = $validateMethod->invoke($controller, $futureDobData);

$oldDobData = array_merge($badNameData, [
    'first_name' => 'Juan',
    'dob' => '1850-01-01'
]);
$errorsOld = $validateMethod->invoke($controller, $oldDobData);

if (!empty($errorsFuture) && !empty($errorsOld)) {
    echo "  PASS: Future DOB correctly rejected: " . implode('; ', $errorsFuture) . "\n";
    echo "  PASS: Pre-1900 DOB correctly rejected: " . implode('; ', $errorsOld) . "\n";
} else {
    echo "  FAIL: Date of birth bounds check failed.\n";
    exit(1);
}

// Test 4: PhilHealth ID Format Validation
echo "\nTest 4: Testing PhilHealth Format Validation (XX-XXXXXXXXX-X)...\n";
$badPhilHealthData = array_merge($badNameData, [
    'first_name' => 'Juan',
    'philhealth_no' => '12-234567897-9' // 9 digits in middle instead of 9? wait, 12-234567897-9 has 9 digits, let's test invalid format '12-345-67'
]);
$badPhilHealthData['philhealth_no'] = '123456';
$errorsPhilHealth = $validateMethod->invoke($controller, $badPhilHealthData);
if (!empty($errorsPhilHealth) && strpos(implode('|', $errorsPhilHealth), 'PhilHealth ID No. must follow the standard 12-digit format') !== false) {
    echo "  PASS: Malformed PhilHealth ID '123456' correctly rejected. Errors: " . implode('; ', $errorsPhilHealth) . "\n";
} else {
    echo "  FAIL: PhilHealth format validation failed.\n";
    exit(1);
}

// Test 5: End-to-End Persistence of Valid Data & New Demographics
echo "\nTest 5: Testing End-to-End Creation with New Demographics (Blood Type, Occupation, Relationship)...\n";
$validData = [
    'first_name' => 'Maria',
    'middle_name' => 'Santos',
    'last_name' => 'Cruz',
    'dob' => '1990-08-15',
    'sex' => 'Female',
    'civil_status' => 'Married',
    'blood_type' => 'O+',
    'occupation' => 'Sari-Sari Store Owner',
    'contact_no' => '09998698088',
    'barangay' => 'Sinalhan',
    'address' => 'Purok 3, Brgy. Sinalhan, Sta. Rosa City, Laguna',
    'emergency_name' => 'Pedro Cruz',
    'emergency_relationship' => 'Spouse',
    'emergency_no' => '09189998877',
    'philhealth_no' => '12-345678' . rand(100, 999) . '-2',
    'created_by' => 1
];

$errors = $validateMethod->invoke($controller, $validData);
if (!empty($errors)) {
    echo "  FAIL: Valid data produced validation errors: " . implode('; ', $errors) . "\n";
    exit(1);
}

$patientModel = new Patient();
$newPatientId = $patientModel->create($validData);

if ($newPatientId) {
    $patient = $patientModel->findById($newPatientId);
    if ($patient && 
        $patient['blood_type'] === 'O+' && 
        $patient['occupation'] === 'Sari-Sari Store Owner' && 
        $patient['emergency_relationship'] === 'Spouse' && 
        $patient['contact_no'] === '09998698088') {
        echo "  PASS: Patient #{$patient['patient_no']} successfully registered with Blood Type '{$patient['blood_type']}', Occupation '{$patient['occupation']}', Relationship '{$patient['emergency_relationship']}', and Contact '{$patient['contact_no']}'.\n";
    } else {
        echo "  FAIL: Database verification of new demographic fields failed.\n";
        exit(1);
    }
} else {
    echo "  FAIL: Failed to create patient record.\n";
    exit(1);
}

echo "\n--- ALL PATIENT VALIDATION & DEMOGRAPHIC TESTS PASSED SUCCESSFULLY! ---\n";
