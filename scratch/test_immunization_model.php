<?php
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Models/Immunization.php';

use App\Models\Immunization;

echo "=== Testing Immunization Canonicalization & Mapping ===\n\n";

$tests = [
    ['Rotavirus', null, 'Rotavirus Vaccine (ROTA)'],
    ['ROTA', 1, 'Rotavirus Vaccine (ROTA)'],
    ['Rotavirus Vaccine (ROTA)', 2, 'Rotavirus Vaccine (ROTA)'],
    ['PCV', 1, 'Pneumococcal Conjugate Vaccine (PCV)'],
    ['Pneumococcal Conjugate Vaccine (PCV)', 1, 'Pneumococcal Conjugate Vaccine (PCV)'],
    ['MCV', 1, 'Measles-Rubella (MCV1)'],
    ['MCV', 2, 'Measles-Mumps-Rubella (MCV2)'],
    ['MCV1', null, 'Measles-Rubella (MCV1)'],
    ['MCV 1', null, 'Measles-Rubella (MCV1)'],
    ['MCV2', null, 'Measles-Mumps-Rubella (MCV2)'],
    ['MMR', null, 'Measles-Mumps-Rubella (MCV2)'],
    ['ANTI-MEASLES', null, 'Measles-Rubella (MCV1)'],
    ['BCG', 1, 'BCG'],
    ['Hepatitis B', 1, 'Hepatitis B (Birth Dose)'],
    ['Pentavalent', 1, 'Pentavalent (DTP-HepB-Hib)'],
    ['OPV', 1, 'Oral Polio Vaccine (OPV)'],
    ['IPV', 1, 'Inactivated Polio Vaccine (IPV)'],
    ['Vitamin A', 1, 'Vitamin A'],
    ['Deworming', 1, 'Deworming'],
];

$pass = 0;
$fail = 0;

foreach ($tests as [$input, $dose, $expected]) {
    $actual = Immunization::canonicalizeVaccineName($input, $dose);
    if ($actual === $expected) {
        echo " [PASS] canonicalize('$input', " . var_export($dose, true) . ") => '$actual'\n";
        $pass++;
    } else {
        echo " [FAIL] canonicalize('$input', " . var_export($dose, true) . ") => '$actual' (expected '$expected')\n";
        $fail++;
    }
}

// Test getVaccineMap with patient 20
$immModel = new Immunization();
$map20 = $immModel->getVaccineMap(20);

$expectedKeys = [
    'BCG__1',
    'HEPATITIS_B__1',
    'PENTAVALENT__1',
    'PENTAVALENT__2',
    'PENTAVALENT__3',
    'OPV__1',
    'OPV__2',
    'OPV__3',
    'Rotavirus__1',
    'Rotavirus__2',
    'IPV__1',
    'MCV__1'
];

echo "\nChecking Patient 20 Vaccine Map Keys:\n";
foreach ($expectedKeys as $key) {
    if (isset($map20[$key])) {
        echo " [PASS] Key '$key' present: Date=" . $map20[$key]['administered_date'] . "\n";
        $pass++;
    } else {
        echo " [FAIL] Key '$key' missing in vaccineMap!\n";
        $fail++;
    }
}

echo "\nTotal Passed: $pass, Failed: $fail\n";
if ($fail > 0) exit(1);
