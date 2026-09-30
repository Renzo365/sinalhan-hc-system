<?php

require_once __DIR__ . '/../app/helpers.php';

function test_render_overview_full($patient, $latestVitals, $consultationsHistory, $latestConsultation, $latestConsultationPrescriptions, $medicalHistory, $familyMembers = [], $cdsAlerts = []) {
    $formatDateSafe = function($val) {
        return !empty($val) ? date('M d, Y', strtotime($val)) : '';
    };
    
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

echo "=== Running Phase 2 Recent Encounter & Risk-Triaged Medical History Tests ===\n\n";

$patient = [
    'id' => 101,
    'first_name' => 'Kathryn',
    'last_name' => 'Agoncillo',
    'family_no' => 'FAM-7788',
    'civil_status' => 'Married',
    'address' => 'Barangay Sinalhan, Santa Rosa City',
    'contact_no' => '09171234567',
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
    'bp_systolic' => 130,
    'bp_diastolic' => 85,
    'heart_rate' => 80,
    'temperature' => '36.80',
    'respiratory_rate' => 18,
    'oxygen_saturation' => 98,
    'weight' => 62.0,
    'height' => 158.0,
    'bmi' => '24.84',
    'waist_circumference' => 74.0,
    'recorded_at' => date('Y-m-d H:i:s'),
    'recorder_name' => 'Nurse Joy'
];

$latestConsultation = [
    'id' => 205,
    'patient_id' => 101,
    'consulted_at' => '2026-09-28 10:15:00',
    'status' => 'Completed',
    'clinician_name' => 'Dr. House',
    'assessment' => "Acute Bronchitis with Bronchospasm\nEssential Hypertension, Stage 1",
    'subjective' => 'Patient reports 3 days of productive cough, wheezing at night, and mild chest tightness.'
];

$consultationsHistory = [$latestConsultation];

$latestConsultationPrescriptions = [
    [
        'id' => 1,
        'consultation_id' => 205,
        'medicine_name' => 'Salbutamol',
        'dosage' => '100mcg MDI',
        'frequency' => '2 puffs PRN',
        'instructions' => 'Inhale 2 puffs every 4-6 hours as needed for wheezing'
    ],
    [
        'id' => 2,
        'consultation_id' => 205,
        'medicine_name' => 'Amoxicillin',
        'dosage' => '500mg capsule',
        'frequency' => 'TID (3x a day)',
        'instructions' => 'Take 1 capsule every 8 hours for 7 days after meals'
    ]
];

$medicalHistory = [
    'patient_id' => 101,
    'past_medical_history' => [
        'Allergy' => 'Severe Penicillin (Anaphylaxis)',
        'Hypertension' => 'Stage 1 on Amlodipine',
        'Bronchial Asthma' => 'Moderate Persistent',
        'Pulmonary Tuberculosis (PTB)' => 'Completed Category 1 Treatment 2024',
        'Diabetes Mellitus' => 'Type 2 on Metformin',
        'Hepatitis A' => 'Resolved 2018',
        'Varicella' => 'Childhood'
    ],
    'smoking_status' => 'Non-Smoker',
    'alcohol_status' => 'Occasional',
    'family_history' => [
        'Hypertension' => 'Both parents',
        'Diabetes Mellitus' => 'Maternal grandmother'
    ]
];

$familyMembers = [
    [
        'id' => 102,
        'last_name' => 'Padilla',
        'first_name' => 'Daniel',
        'suffix' => 'Jr.',
        'age' => 32,
        'sex' => 'Male'
    ]
];

// Test 1: Render encounter with consultation and stratified past illnesses
$html1 = test_render_overview_full($patient, $latestVitals, $consultationsHistory, $latestConsultation, $latestConsultationPrescriptions, $medicalHistory, $familyMembers);

// Verify Recent Clinical Encounter Card
assertCondition("Recent Clinical Encounter Hub is present", strpos($html1, 'Recent Clinical Encounter') !== false);
assertCondition("Encounter date rendered properly", strpos($html1, 'Sep 28, 2026') !== false);
assertCondition("Encounter status Completed rendered", strpos($html1, 'Completed') !== false);
assertCondition("Clinician Dr. House rendered", strpos($html1, 'Dr. House') !== false);
assertCondition("Assessment / Impression rendered", strpos($html1, 'Acute Bronchitis with Bronchospasm') !== false && strpos($html1, 'Essential Hypertension') !== false);
assertCondition("Chief complaint / subjective rendered", strpos($html1, '3 days of productive cough') !== false);
assertCondition("View Record button present with correct ID and modal trigger", strpos($html1, 'view-consultation-btn') !== false && strpos($html1, 'data-consultation-id="205"') !== false);
assertCondition("+ New Consultation Entry primary button present", strpos($html1, '+ New Consultation Entry') !== false && strpos($html1, '/patients/101/consultations/create') !== false);
assertCondition("Prescriptions rendered with medicine names", strpos($html1, 'Salbutamol') !== false && strpos($html1, 'Amoxicillin') !== false);
assertCondition("Prescription dosage & instructions rendered", strpos($html1, '100mcg MDI') !== false && strpos($html1, 'Inhale 2 puffs') !== false);
assertCondition("Prescription count badge rendered", strpos($html1, '2 items') !== false);

// Verify Risk-Stratified IHP Past Illnesses
assertCondition("Prominent Allergy Alert Banner rendered", strpos($html1, 'Known Patient Allergy') !== false && strpos($html1, 'Severe Penicillin (Anaphylaxis)') !== false);
assertCondition("Active & Chronic Conditions section rendered", strpos($html1, 'Active &amp; Chronic Conditions:') !== false);
assertCondition("Hypertension chronic badge with heart icon rendered", strpos($html1, 'Hypertension (Stage 1 on Amlodipine)') !== false && strpos($html1, 'bi-heart-pulse') !== false);
assertCondition("Asthma chronic badge with lungs icon rendered", strpos($html1, 'Bronchial Asthma (Moderate Persistent)') !== false && strpos($html1, 'bi-lungs') !== false);
assertCondition("PTB chronic badge with virus icon rendered", strpos($html1, 'Pulmonary Tuberculosis (PTB)') !== false && strpos($html1, 'bi-virus') !== false);
assertCondition("Diabetes chronic badge with droplet icon rendered", strpos($html1, 'Diabetes Mellitus (Type 2 on Metformin)') !== false && strpos($html1, 'bi-droplet') !== false);
assertCondition("Other Past Illnesses section rendered", strpos($html1, 'Other Past Illnesses:') !== false && strpos($html1, 'Hepatitis A (Resolved 2018)') !== false && strpos($html1, 'Varicella (Childhood)') !== false);

// Verify Card 2 (Emergency Contacts) Edit button & Redundancy removal
assertCondition("Card 2 header has Edit button", strpos($html1, 'Edit Family & Emergency Contacts') !== false && strpos($html1, '/patients/101/edit') !== false);
assertCondition("Card 2 NO LONGER has redundant Household Code row", strpos($html1, 'Family Group # FAM-7788 (Click to view members)') === false);
assertCondition("Card 4 still contains Household Members", strpos($html1, 'Household Members') !== false && strpos($html1, 'Padilla, Daniel Jr.') !== false);

// Test 2: Patient with NO previous consultations and NO past illnesses
$html2 = test_render_overview_full($patient, $latestVitals, [], null, [], false, []);

assertCondition("Empty state rendered when no consultations", strpos($html2, 'No consultation encounters recorded yet for this patient.') !== false);
assertCondition("Begin First Clinical Consultation CTA button present in empty state", strpos($html2, 'Begin First Clinical Consultation') !== false);
assertCondition("No IHP medical history recorded rendered", strpos($html2, 'No IHP medical history recorded.') !== false);

echo "\nResult: $testsPassed / $totalTests tests passed.\n";

if ($testsPassed === $totalTests) {
    echo "SUCCESS: All Phase 2 Recent Encounter & Risk-Triaged Medical History requirements verified!\n";
    exit(0);
} else {
    echo "FAILURE: Some tests did not pass.\n";
    exit(1);
}
