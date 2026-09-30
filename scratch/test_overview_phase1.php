<?php

require_once __DIR__ . '/../app/helpers.php';

function test_render_vitals_section($latestVitals, $patient = ['id' => 1, 'address' => 'Sample', 'contact_no' => '123', 'civil_status' => 'Single']) {
    // Dummy variables for other parts of tab_overview.php
    $familyMembers = [];
    $medicalHistory = [];
    $ihpProgress = ['progress_pct' => 0];
    
    ob_start();
    // Include tab_overview
    include __DIR__ . '/../app/Views/patients/partials/tab_overview.php';
    return ob_get_clean();
}

$testsPassed = 0;
$totalTests = 0;

function assertCondition($name, $condition, $details = '') {
    global $testsPassed, $totalTests;
    $totalTests++;
    if ($condition) {
        echo " [PASS] $name\n";
        $testsPassed++;
    } else {
        echo " [FAIL] $name: $details\n";
    }
}

echo "=== Running Phase 1 Vital Signs Clinical Triage Tests ===\n\n";

// Test Case 1: Acute abnormal vitals matching audit findings
$auditVitals = [
    'id' => 10,
    'patient_id' => 1,
    'bp_systolic' => 150,
    'bp_diastolic' => 95,
    'heart_rate' => 108,
    'temperature' => '39.10',
    'respiratory_rate' => 24,
    'oxygen_saturation' => 92,
    'weight' => 88.5,
    'height' => 156.0,
    'bmi' => '36.36',
    'waist_circumference' => 98.0,
    'recorded_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
    'recorder_name' => 'Nurse Joy'
];

$html1 = test_render_vitals_section($auditVitals);

assertCondition("Fever alert badge rendered", strpos($html1, 'Fever') !== false && strpos($html1, 'bg-danger') !== false);
assertCondition("Hypertension alert badge rendered", strpos($html1, 'Hypertension') !== false && strpos($html1, 'bg-danger-subtle') !== false);
assertCondition("Tachycardia alert badge rendered", strpos($html1, 'Tachycardia') !== false && strpos($html1, 'bg-warning-subtle') !== false);
assertCondition("Tachypnea alert badge rendered", strpos($html1, 'Tachypnea') !== false);
assertCondition("Hypoxia Risk badge rendered for SpO2 92%", strpos($html1, 'Hypoxia Risk') !== false && strpos($html1, '92%') !== false);
assertCondition("BMI Obese badge rendered", strpos($html1, 'Obese') !== false);
assertCondition("Recorded Yesterday warning badge rendered", strpos($html1, 'Recorded Yesterday &bull; Retake Recommended for Today') !== false);
assertCondition("All 8 tiles present in grid", 
    strpos($html1, 'Blood Pressure') !== false &&
    strpos($html1, 'Heart Rate') !== false &&
    strpos($html1, 'Temperature') !== false &&
    strpos($html1, 'Resp Rate') !== false &&
    strpos($html1, 'Oxygen Saturation') !== false &&
    strpos($html1, 'Weight / Height') !== false &&
    strpos($html1, 'Body Mass Index') !== false &&
    strpos($html1, 'Waistline') !== false
);

// Test Case 2: Normal vitals recorded today
$normalVitals = [
    'id' => 11,
    'patient_id' => 1,
    'bp_systolic' => 118,
    'bp_diastolic' => 78,
    'heart_rate' => 72,
    'temperature' => '36.70',
    'respiratory_rate' => 16,
    'oxygen_saturation' => 99,
    'weight' => 55.0,
    'height' => 160.0,
    'bmi' => '21.48',
    'waist_circumference' => 70.0,
    'recorded_at' => date('Y-m-d H:i:s'),
    'recorder_name' => 'Dr. Smith'
];

$html2 = test_render_vitals_section($normalVitals);

assertCondition("Normal vitals show Recorded Today badge", strpos($html2, 'Recorded Today') !== false);
assertCondition("Normal vitals show Normal badges", strpos($html2, 'Normal (60-100)') !== false && strpos($html2, 'Normal (12-20)') !== false && strpos($html2, 'Normal (&ge;95%)') !== false);
assertCondition("Normal vitals has Normal BMI", strpos($html2, 'Normal') !== false && strpos($html2, 'Hypertension') === false);

// Test Case 3: Bradypnea, Bradycardia, Low Fever, Stale vitals (14 days ago)
$staleAbnormalVitals = [
    'id' => 12,
    'patient_id' => 1,
    'bp_systolic' => 88,
    'bp_diastolic' => 55,
    'heart_rate' => 52,
    'temperature' => '37.80',
    'respiratory_rate' => 10,
    'oxygen_saturation' => 97,
    'weight' => 50.0,
    'height' => 160.0,
    'bmi' => '19.53',
    'waist_circumference' => 68.0,
    'recorded_at' => date('Y-m-d H:i:s', strtotime('-14 days')),
    'recorder_name' => 'Nurse Joy'
];

$html3 = test_render_vitals_section($staleAbnormalVitals);

assertCondition("Stale vitals warning badge rendered", strpos($html3, 'Stale Vitals') !== false);
assertCondition("Hypotension badge rendered", strpos($html3, 'Hypotension') !== false);
assertCondition("Bradycardia badge rendered", strpos($html3, 'Bradycardia') !== false);
assertCondition("Low Fever badge rendered", strpos($html3, 'Low Fever') !== false);
assertCondition("Bradypnea badge rendered", strpos($html3, 'Bradypnea') !== false);

// Test Case 4: No vitals recorded
$html4 = test_render_vitals_section(false);

assertCondition("Empty state rendered when no vitals", strpos($html4, 'No vital signs recorded yet for this patient.') !== false);
assertCondition("Record First Vital Signs button present in empty state", strpos($html4, 'Record First Vital Signs') !== false);

echo "\nResult: $testsPassed / $totalTests tests passed.\n";

if ($testsPassed === $totalTests) {
    echo "SUCCESS: All Phase 1 Vital Signs Clinical Triage requirements verified!\n";
    exit(0);
} else {
    echo "FAILURE: Some tests did not pass.\n";
    exit(1);
}
