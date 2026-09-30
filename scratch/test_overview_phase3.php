<?php

require_once __DIR__ . '/../app/helpers.php';

function test_render_overview_phase3($patient, $latestVitals) {
    $formatDateSafe = function($val) {
        return !empty($val) ? date('M d, Y', strtotime($val)) : '';
    };
    $consultationsHistory = [];
    $latestConsultation = null;
    $latestConsultationPrescriptions = [];
    $medicalHistory = [];
    $familyMembers = [];
    $cdsAlerts = [];
    
    ob_start();
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

echo "=== Running Phase 3 Ergonomics, Action Parity & Accessibility Tests ===\n\n";

$patient = [
    'id' => 101,
    'first_name' => 'Kathryn',
    'last_name' => 'Agoncillo',
    'family_no' => 'FAM-7788',
    'civil_status' => 'Married',
    'address' => 'Barangay Sinalhan, Santa Rosa City',
    'contact_no' => '09171234567',
    'philhealth_no' => '12-345678901-2',
    'father_name' => 'Roberto Agoncillo',
    'mother_name' => 'Maria Clara',
    'spouse_name' => 'Daniel Padilla',
    'emergency_name' => 'Daniel Padilla',
    'emergency_relationship' => 'Spouse',
    'emergency_no' => '09189876543',
    'created_at' => '2026-01-15 08:30:00',
    'creator_name' => 'Admin'
];

$latestVitals = [
    'id' => 50,
    'patient_id' => 101,
    'bp_systolic' => 120,
    'bp_diastolic' => 80,
    'heart_rate' => 75,
    'temperature' => '36.60',
    'respiratory_rate' => 18,
    'oxygen_saturation' => 99,
    'weight' => 60.0,
    'height' => 158.0,
    'bmi' => '24.03',
    'waist_circumference' => 72.0,
    'recorded_at' => date('Y-m-d H:i:s'),
    'recorder_name' => 'Nurse Joy'
];

$html = test_render_overview_phase3($patient, $latestVitals);

// 1. Verify 1-Click Clipboard Copy Buttons
assertCondition("Primary Contact has copy button with data-clipboard", 
    strpos($html, 'data-clipboard="09171234567"') !== false &&
    strpos($html, 'title="Copy phone number"') !== false
);

assertCondition("PhilHealth PIN has copy button with data-clipboard", 
    strpos($html, 'data-clipboard="12-345678901-2"') !== false &&
    strpos($html, 'title="Copy PhilHealth PIN"') !== false
);

assertCondition("Emergency Phone has copy button with data-clipboard", 
    strpos($html, 'data-clipboard="09189876543"') !== false &&
    strpos($html, 'title="Copy emergency phone"') !== false
);

// 2. Verify WCAG 2.2 AA Contrast Micro-Labels in HTML
assertCondition("overview-micro-label used on Residential Address", strpos($html, 'overview-micro-label d-block">Residential Address</span>') !== false);
assertCondition("overview-micro-label used on Primary Contact", strpos($html, 'overview-micro-label d-block">Primary Contact Number</span>') !== false);
assertCondition("overview-micro-label used on Civil Status", strpos($html, 'overview-micro-label d-block">Civil Status</span>') !== false);
assertCondition("overview-micro-label used on PhilHealth PIN", strpos($html, 'overview-micro-label d-block">PhilHealth Identification No. (PIN)</span>') !== false);
assertCondition("overview-micro-label used on Emergency Contact", strpos($html, 'overview-micro-label d-block">Emergency Contact</span>') !== false);
assertCondition("overview-micro-label used on Blood Pressure tile", strpos($html, 'overview-micro-label d-block"><i class="bi bi-activity me-1"></i>Blood Pressure</span>') !== false);
assertCondition("overview-micro-label used on SpO2 tile", strpos($html, 'overview-micro-label d-block"><i class="bi bi-droplet-half me-1"></i>Oxygen Saturation</span>') !== false);

// 3. Verify CSS rules in index.css
$css = file_get_contents(__DIR__ . '/../public/assets/css/index.css');
assertCondition("index.css defines .overview-micro-label with #4b5563", strpos($css, '.overview-micro-label') !== false && strpos($css, '#4b5563') !== false);
assertCondition("index.css defines .copy-clipboard-btn", strpos($css, '.copy-clipboard-btn') !== false && strpos($css, 'cursor: pointer') !== false);

// 4. Verify JS Clipboard event listener in scripts.php
$scripts = file_get_contents(__DIR__ . '/../app/Views/patients/partials/scripts.php');
assertCondition("scripts.php contains copy-clipboard-btn listener", strpos($scripts, '.copy-clipboard-btn') !== false && strpos($scripts, 'navigator.clipboard.writeText') !== false);

// 5. Verify Equalized Card Heights
assertCondition("Card 1 has h-100", strpos($html, 'card border rounded-3 h-100 shadow-xs') !== false);

echo "\nResult: $testsPassed / $totalTests tests passed.\n";

if ($testsPassed === $totalTests) {
    echo "SUCCESS: All Phase 3 Ergonomics, Action Parity & Accessibility requirements verified!\n";
    exit(0);
} else {
    echo "FAILURE: Some tests did not pass.\n";
    exit(1);
}
