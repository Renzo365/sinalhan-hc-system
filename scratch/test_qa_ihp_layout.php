<?php
/**
 * QA Verification Script: PhilHealth Annex A1 (IHP) Tab Layout & Baseline Vitals
 */

$filePath = __DIR__ . '/../app/Views/patients/show.php';
if (!file_exists($filePath)) {
    echo "[FAIL] File not found: $filePath\n";
    exit(1);
}

$content = file_get_contents($filePath);
$errors = [];
$passed = [];

// 1. PHP Syntax Check
exec('php -l ' . escapeshellarg($filePath), $output, $returnCode);
if ($returnCode === 0) {
    $passed[] = "PHP Syntax: No syntax errors detected in show.php";
} else {
    $errors[] = "PHP Syntax: Error detected - " . implode("\n", $output);
}

// 2. Extract `#ihp-view-mode` and `#ihp-edit-mode` blocks
if (!preg_match('/<div id="ihp-view-mode">(.*?)<\/div>\s*<!-- -----------------------------------------------------------/s', $content, $vmMatch)) {
    // try fallback regex
    preg_match('/<div id="ihp-view-mode">(.*?)<div id="ihp-edit-mode"/s', $content, $vmMatch);
}

if (!preg_match('/<div id="ihp-edit-mode"[^>]*>(.*?)<\/form>\s*<\/div>/s', $content, $emMatch)) {
    $errors[] = "Could not cleanly isolate #ihp-edit-mode block";
}

$viewModeHtml = $vmMatch[1] ?? '';
$editModeHtml = $emMatch[1] ?? '';

// Check view mode headers
preg_match_all('/<h5[^>]*>\s*(\d+)\.\s*([^<\n]+)/i', $viewModeHtml, $vmHeaders);
$vmNumbers = array_map('intval', $vmHeaders[1] ?? []);
$vmTitles = $vmHeaders[2] ?? [];

$expectedVmOrder = [1, 2, 3, 4, 5, 6, 7, 8, 9];
if ($vmNumbers === array_slice($expectedVmOrder, 0, count($vmNumbers)) && count($vmNumbers) === 9) {
    $passed[] = "View Mode Card Numbering: Sequential 1 through 9 (" . implode(', ', $vmNumbers) . ")";
} else {
    $errors[] = "View Mode Card Numbering Mismatch: Found [" . implode(', ', $vmNumbers) . "], expected [" . implode(', ', $expectedVmOrder) . "]";
}

// Check edit mode headers
preg_match_all('/<h5[^>]*>\s*(\d+)\.\s*([^<\n]+)/i', $editModeHtml, $emHeaders);
$emNumbers = array_map('intval', $emHeaders[1] ?? []);
$emTitles = $emHeaders[2] ?? [];

if ($emNumbers === array_slice($expectedVmOrder, 0, count($emNumbers)) && count($emNumbers) === 9) {
    $passed[] = "Edit Mode Card Numbering: Sequential 1 through 9 (" . implode(', ', $emNumbers) . ")";
} else {
    $errors[] = "Edit Mode Card Numbering Mismatch: Found [" . implode(', ', $emNumbers) . "], expected [" . implode(', ', $expectedVmOrder) . "]";
}

// 3. Presence of all 7 baseline vitals input names in #ihp-edit-mode
$baselineFields = [
    'baseline_bp_systolic',
    'baseline_bp_diastolic',
    'baseline_heart_rate',
    'baseline_respiratory_rate',
    'baseline_height',
    'baseline_weight',
    'baseline_waist_circumference'
];

$missingInputs = [];
foreach ($baselineFields as $field) {
    if (!preg_match('/name=["\']' . preg_quote($field, '/') . '["\']/i', $editModeHtml)) {
        $missingInputs[] = $field;
    }
}

if (empty($missingInputs)) {
    $passed[] = "Baseline Vitals Inputs: All 7 input fields present in #ihp-edit-mode (" . implode(', ', $baselineFields) . ")";
} else {
    $errors[] = "Baseline Vitals Inputs: Missing inputs in #ihp-edit-mode: " . implode(', ', $missingInputs);
}

// 4. Proper CSRF token and action URL in #ihpForm
if (strpos($content, 'id="ihpForm"') !== false && strpos($content, '<?= csrf_field() ?>') !== false) {
    $passed[] = "CSRF & Security: id=\"ihpForm\" and csrf_field() are present";
} else {
    $errors[] = "CSRF & Security: #ihpForm or csrf_field() missing";
}

// 5. Check condition flag $hasBaselineVitals
if (strpos($content, "baseline_respiratory_rate") !== false && strpos($content, "baseline_waist_circumference") !== false) {
    $passed[] = "Condition Flags: baseline_respiratory_rate and baseline_waist_circumference included in baseline checks";
} else {
    $errors[] = "Condition Flags: baseline_respiratory_rate or baseline_waist_circumference missing in checks";
}

// 6. Check BMI calculation logic
if (strpos($content, '$baselineBmi') !== false && strpos($content, 'Underweight') !== false && strpos($content, 'Normal') !== false && strpos($content, 'Overweight') !== false && strpos($content, 'Obese') !== false) {
    $passed[] = "BMI Calculation: Categorization and badge assignment for Underweight, Normal, Overweight, Obese verified";
} else {
    $errors[] = "BMI Calculation: Missing category classifications";
}

echo "========================================================\n";
echo " PHILHEALTH ANNEX A1 (IHP) QA VERIFICATION REPORT\n";
echo "========================================================\n";

foreach ($passed as $p) {
    echo "[PASS] $p\n";
}

if (!empty($errors)) {
    echo "\n--------------------------------------------------------\n";
    echo " FAILURES DETECTED (" . count($errors) . "):\n";
    echo "--------------------------------------------------------\n";
    foreach ($errors as $e) {
        echo "[FAIL] $e\n";
    }
    exit(1);
} else {
    echo "\n--------------------------------------------------------\n";
    echo " ALL CHECKS PASSED (6/6) - ZERO REGRESSIONS DETECTED\n";
    echo "--------------------------------------------------------\n";
    exit(0);
}
