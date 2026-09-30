<?php
/**
 * Test script for Consultation Remediation Phase 3:
 * Clinical Ergonomics & Prescribing Speed
 */

echo "======================================================\n";
echo "   CONSULTATION PHASE 3 COMPREHENSIVE VERIFICATION    \n";
echo "======================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertCondition($desc, $cond) {
    global $passCount, $failCount;
    if ($cond) {
        echo " [PASS] $desc\n";
        $passCount++;
    } else {
        echo " [FAIL] $desc\n";
        $failCount++;
    }
}

// 1. Check PHP syntax on both views
$createSyntax = shell_exec('C:\xampp\php\php.exe -l ' . escapeshellarg(__DIR__ . '/../app/Views/consultations/create.php'));
$editSyntax = shell_exec('C:\xampp\php\php.exe -l ' . escapeshellarg(__DIR__ . '/../app/Views/consultations/edit.php'));
assertCondition("create.php has no PHP syntax errors", strpos($createSyntax, 'No syntax errors detected') !== false);
assertCondition("edit.php has no PHP syntax errors", strpos($editSyntax, 'No syntax errors detected') !== false);

$createCode = file_get_contents(__DIR__ . '/../app/Views/consultations/create.php');
$editCode = file_get_contents(__DIR__ . '/../app/Views/consultations/edit.php');
$cssCode = file_get_contents(__DIR__ . '/../public/assets/css/index.css');

// 2. Strict User Constraint: NO SOAP BADGES
assertCondition("create.php contains NO '[S]' badge", strpos($createCode, '[S]') === false);
assertCondition("create.php contains NO '[O]' badge", strpos($createCode, '[O]') === false);
assertCondition("create.php contains NO '[A]' badge", strpos($createCode, '[A]') === false);
assertCondition("create.php contains NO '[P]' badge", strpos($createCode, '[P]') === false);

assertCondition("edit.php contains NO '[S]' badge", strpos($editCode, '[S]') === false);
assertCondition("edit.php contains NO '[O]' badge", strpos($editCode, '[O]') === false);
assertCondition("edit.php contains NO '[A]' badge", strpos($editCode, '[A]') === false);
assertCondition("edit.php contains NO '[P]' badge", strpos($editCode, '[P]') === false);

// 3. CSS Ergonomics Classes
assertCondition("index.css contains .consultation-snippet-btn", strpos($cssCode, '.consultation-snippet-btn') !== false);
assertCondition("index.css contains .sig-chip", strpos($cssCode, '.sig-chip') !== false);
assertCondition("index.css contains .sig-instruction-chip", strpos($cssCode, '.sig-instruction-chip') !== false);
assertCondition("index.css contains .btn-xs", strpos($cssCode, '.btn-xs') !== false);

// 4. Formulary Datalist Autocomplete
assertCondition("create.php has <datalist id=\"commonMedicinesDatalist\">", strpos($createCode, 'id="commonMedicinesDatalist"') !== false);
assertCondition("edit.php has <datalist id=\"commonMedicinesDatalist\">", strpos($editCode, 'id="commonMedicinesDatalist"') !== false);
assertCondition("create.php connects medicine input to datalist", strpos($createCode, 'list="commonMedicinesDatalist"') !== false);
assertCondition("edit.php connects medicine input to datalist", strpos($editCode, 'list="commonMedicinesDatalist"') !== false);

// 5. Formulary contains core Philippine primary care medications
$essentialMeds = ['Amoxicillin', 'Co-amoxiclav', 'Cefalexin', 'Paracetamol', 'Mefenamic Acid', 'Losartan', 'Amlodipine', 'Metformin', 'Salbutamol', 'Cetirizine', 'Oral Rehydration Salts'];
foreach ($essentialMeds as $med) {
    assertCondition("create.php formulary contains {$med}", strpos($createCode, $med) !== false);
    assertCondition("edit.php formulary contains {$med}", strpos($editCode, $med) !== false);
}

// 6. Quick PE Snippets Ribbon
assertCondition("create.php has Quick PE snippets ribbon", strpos($createCode, 'Quick PE:') !== false);
assertCondition("edit.php has Quick PE snippets ribbon", strpos($editCode, 'Quick PE:') !== false);
assertCondition("create.php has '✓ Normal PE' snippet button", strpos($createCode, '✓ Normal PE') !== false);
assertCondition("edit.php has '✓ Normal PE' snippet button", strpos($editCode, '✓ Normal PE') !== false);
assertCondition("create.php has '+ Clear Lungs' snippet button", strpos($createCode, '+ Clear Lungs') !== false);
assertCondition("edit.php has '+ Clear Lungs' snippet button", strpos($editCode, '+ Clear Lungs') !== false);

// 7. Assessment Diagnoses Quick Chips & rows='3'
assertCondition("create.php assessment textarea has rows=\"3\"", strpos($createCode, 'name="assessment"') !== false && strpos($createCode, 'rows="3"') !== false);
assertCondition("edit.php assessment textarea has rows=\"3\"", strpos($editCode, 'name="assessment"') !== false && strpos($editCode, 'rows="3"') !== false);
assertCondition("create.php has Common Diagnoses ribbon", strpos($createCode, 'Diagnoses:') !== false);
assertCondition("edit.php has Common Diagnoses ribbon", strpos($editCode, 'Diagnoses:') !== false);
assertCondition("create.php has '+ URTI' diagnosis chip", strpos($createCode, '+ URTI') !== false);
assertCondition("edit.php has '+ URTI' diagnosis chip", strpos($editCode, '+ URTI') !== false);
assertCondition("create.php has '+ HTN Stage 1' diagnosis chip", strpos($createCode, '+ HTN Stage 1') !== false);
assertCondition("edit.php has '+ HTN Stage 1' diagnosis chip", strpos($editCode, '+ HTN Stage 1') !== false);

// 8. Quick Advice Snippets Ribbon for Plan
assertCondition("create.php has Quick Advice snippets ribbon", strpos($createCode, 'Quick Advice:') !== false);
assertCondition("edit.php has Quick Advice snippets ribbon", strpos($editCode, 'Quick Advice:') !== false);
assertCondition("create.php has '+ URTI Care & Hydration' snippet", strpos($createCode, '+ URTI Care & Hydration') !== false);
assertCondition("edit.php has '+ URTI Care & Hydration' snippet", strpos($editCode, '+ URTI Care & Hydration') !== false);
assertCondition("create.php has '+ HTN Lifestyle & Diet' snippet", strpos($createCode, '+ HTN Lifestyle & Diet') !== false);
assertCondition("edit.php has '+ HTN Lifestyle & Diet' snippet", strpos($editCode, '+ HTN Lifestyle & Diet') !== false);

// 9. SIG Frequency and Instruction Quick Chips
assertCondition("create.php has SIG chips strip", strpos($createCode, 'SIG Chips:') !== false);
assertCondition("edit.php has SIG chips strip", strpos($editCode, 'SIG Chips:') !== false);
$sigChips = ['data-sig="OD (Once daily)"', 'data-sig="BID (Twice daily)"', 'data-sig="TID (3x a day)"', 'data-sig="QID (4x a day)"', 'data-sig="q8h (Every 8 hrs)"', 'data-sig="PRN (As needed)"', 'data-sig="HS (At bedtime)"'];
foreach ($sigChips as $sig) {
    assertCondition("create.php has {$sig}", strpos($createCode, $sig) !== false);
    assertCondition("edit.php has {$sig}", strpos($editCode, $sig) !== false);
}
assertCondition("create.php has After meals instruction chip", strpos($createCode, 'data-instruction="Take after meals"') !== false);
assertCondition("edit.php has After meals instruction chip", strpos($editCode, 'data-instruction="Take after meals"') !== false);

// 10. JavaScript Ergonomics Handlers
assertCondition("create.php has smart dosage extraction regex", strpos($createCode, 'match(/(\\d+(?:\\.\\d+)?\\s*(?:mg|g|mcg|mL|%)') !== false);
assertCondition("edit.php has smart dosage extraction regex", strpos($editCode, 'match(/(\\d+(?:\\.\\d+)?\\s*(?:mg|g|mcg|mL|%)') !== false);
assertCondition("create.php has .consultation-snippet-btn handler", strpos($createCode, "document.querySelectorAll('.consultation-snippet-btn')") !== false);
assertCondition("edit.php has .consultation-snippet-btn handler", strpos($editCode, "document.querySelectorAll('.consultation-snippet-btn')") !== false);
assertCondition("create.php has .sig-chip click handler", strpos($createCode, "document.querySelectorAll('.sig-chip')") !== false);
assertCondition("edit.php has .sig-chip click handler", strpos($editCode, "document.querySelectorAll('.sig-chip')") !== false);
assertCondition("create.php has .sig-instruction-chip click handler", strpos($createCode, "document.querySelectorAll('.sig-instruction-chip')") !== false);
assertCondition("edit.php has .sig-instruction-chip click handler", strpos($editCode, "document.querySelectorAll('.sig-instruction-chip')") !== false);

echo "\n------------------------------------------------------\n";
echo "SUMMARY: Passed: $passCount | Failed: $failCount\n";
echo "------------------------------------------------------\n";

if ($failCount === 0) {
    echo ">>> PHASE 3 VERIFICATION SUCCEEDED! <<<\n";
    exit(0);
} else {
    echo ">>> SOME CHECKS FAILED! <<<\n";
    exit(1);
}
