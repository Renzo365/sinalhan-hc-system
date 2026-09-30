<?php
/**
 * Test script for Consultation Remediation Phase 2:
 * Clinical Decision Support & Safety
 */

echo "======================================================\n";
echo "   CONSULTATION PHASE 2 COMPREHENSIVE VERIFICATION    \n";
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

// 1. Check PHP syntax on both files
$createSyntax = shell_exec('C:\xampp\php\php.exe -l ' . escapeshellarg(__DIR__ . '/../app/Views/consultations/create.php'));
$editSyntax = shell_exec('C:\xampp\php\php.exe -l ' . escapeshellarg(__DIR__ . '/../app/Views/consultations/edit.php'));
assertCondition("create.php has no PHP syntax errors", strpos($createSyntax, 'No syntax errors detected') !== false);
assertCondition("edit.php has no PHP syntax errors", strpos($editSyntax, 'No syntax errors detected') !== false);

$createCode = file_get_contents(__DIR__ . '/../app/Views/consultations/create.php');
$editCode = file_get_contents(__DIR__ . '/../app/Views/consultations/edit.php');

// 2. Strict Constraint: NO SOAP BADGES
assertCondition("create.php contains NO '[S]' badge", strpos($createCode, '[S]') === false);
assertCondition("create.php contains NO '[O]' badge", strpos($createCode, '[O]') === false);
assertCondition("create.php contains NO '[A]' badge", strpos($createCode, '[A]') === false);
assertCondition("create.php contains NO '[P]' badge", strpos($createCode, '[P]') === false);

assertCondition("edit.php contains NO '[S]' badge", strpos($editCode, '[S]') === false);
assertCondition("edit.php contains NO '[O]' badge", strpos($editCode, '[O]') === false);
assertCondition("edit.php contains NO '[A]' badge", strpos($editCode, '[A]') === false);
assertCondition("edit.php contains NO '[P]' badge", strpos($editCode, '[P]') === false);

// 3. Field error helper & accessible anchor links in both views
assertCondition("create.php has \$fieldErrorMap", strpos($createCode, '$fieldErrorMap = [') !== false);
assertCondition("create.php has \$hasFieldError helper", strpos($createCode, '$hasFieldError = function(') !== false);
assertCondition("create.php has \$getFieldError helper", strpos($createCode, '$getFieldError = function(') !== false);
assertCondition("create.php has error-jump-link anchor", strpos($createCode, 'class="text-danger text-decoration-underline fw-semibold error-jump-link"') !== false);

assertCondition("edit.php has \$fieldErrorMap", strpos($editCode, '$fieldErrorMap = [') !== false);
assertCondition("edit.php has \$hasFieldError helper", strpos($editCode, '$hasFieldError = function(') !== false);
assertCondition("edit.php has \$getFieldError helper", strpos($editCode, '$getFieldError = function(') !== false);
assertCondition("edit.php has error-jump-link anchor", strpos($editCode, 'class="text-danger text-decoration-underline fw-semibold error-jump-link"') !== false);

// 4. Inline validation attributes & feedback for required fields
$requiredFields = ['consulting_provider', 'consulted_at', 'subjective', 'objective', 'assessment', 'plan'];
foreach ($requiredFields as $field) {
    assertCondition("create.php has inline invalid check for {$field}", strpos($createCode, "\$hasFieldError('{$field}') ? 'is-invalid' : ''") !== false);
    assertCondition("create.php has invalid-feedback element for {$field}", strpos($createCode, "\$getFieldError('{$field}')") !== false);
    assertCondition("edit.php has inline invalid check for {$field}", strpos($editCode, "\$hasFieldError('{$field}') ? 'is-invalid' : ''") !== false);
    assertCondition("edit.php has invalid-feedback element for {$field}", strpos($editCode, "\$getFieldError('{$field}')") !== false);
}

// 5. Prescription allergy alert banner in both views
assertCondition("create.php has #rxAllergyAlertBanner", strpos($createCode, 'id="rxAllergyAlertBanner"') !== false);
assertCondition("edit.php has #rxAllergyAlertBanner", strpos($editCode, 'id="rxAllergyAlertBanner"') !== false);
assertCondition("create.php has .allergy-warning-pill", strpos($createCode, 'allergy-warning-pill') !== false);
assertCondition("edit.php has .allergy-warning-pill", strpos($editCode, 'allergy-warning-pill') !== false);

// 6. JavaScript allergy cross-check & multi-threshold vitals
assertCondition("create.php has ALLERGY_GROUPS cross-checking matrix", strpos($createCode, 'const ALLERGY_GROUPS = [') !== false);
assertCondition("edit.php has ALLERGY_GROUPS cross-checking matrix", strpos($editCode, 'const ALLERGY_GROUPS = [') !== false);
assertCondition("create.php has checkAllergyConflict function", strpos($createCode, 'function checkAllergyConflict(') !== false);
assertCondition("edit.php has checkAllergyConflict function", strpos($editCode, 'function checkAllergyConflict(') !== false);

// 7. Multi-threshold vitals criteria in both views
assertCondition("create.php checks hypertension (sys >= 140 || dia >= 90)", strpos($createCode, 'sys >= 140 || dia >= 90') !== false);
assertCondition("edit.php checks hypertension (sys >= 140 || dia >= 90)", strpos($editCode, 'sys >= 140 || dia >= 90') !== false);
assertCondition("create.php checks fever (tVal >= 38.0)", strpos($createCode, 'tVal >= 38.0') !== false);
assertCondition("edit.php checks fever (tVal >= 38.0)", strpos($editCode, 'tVal >= 38.0') !== false);
assertCondition("create.php checks tachycardia (hrVal > 100)", strpos($createCode, 'hrVal > 100') !== false);
assertCondition("edit.php checks tachycardia (hrVal > 100)", strpos($editCode, 'hrVal > 100') !== false);
assertCondition("create.php checks tachypnea (rrVal > 20)", strpos($createCode, 'rrVal > 20') !== false);
assertCondition("edit.php checks tachypnea (rrVal > 20)", strpos($editCode, 'rrVal > 20') !== false);
assertCondition("create.php checks hypoxia (spo2Val < 95)", strpos($createCode, 'spo2Val < 95') !== false);
assertCondition("edit.php checks hypoxia (spo2Val < 95)", strpos($editCode, 'spo2Val < 95') !== false);
assertCondition("create.php checks WHO Asian BMI obesity (bmiVal >= 25.0)", strpos($createCode, 'bmiVal >= 25.0') !== false);
assertCondition("edit.php checks WHO Asian BMI obesity (bmiVal >= 25.0)", strpos($editCode, 'bmiVal >= 25.0') !== false);

// 8. Auto-focus on error and smooth scroll
assertCondition("create.php auto-focuses first .is-invalid", strpos($createCode, "document.querySelector('.is-invalid')") !== false);
assertCondition("edit.php auto-focuses first .is-invalid", strpos($editCode, "document.querySelector('.is-invalid')") !== false);
assertCondition("create.php handles .error-jump-link click", strpos($createCode, "document.querySelectorAll('.error-jump-link')") !== false);
assertCondition("edit.php handles .error-jump-link click", strpos($editCode, "document.querySelectorAll('.error-jump-link')") !== false);

// 9. Node / Javascript unit test of allergy cross-checking algorithm
$jsTestScript = <<<'JS'
const ALLERGY_GROUPS = [
    {
        name: 'Penicillins / Beta-lactams',
        triggers: ['penicillin', 'amoxicillin', 'amox', 'ampicillin', 'cloxacillin', 'co-amoxiclav', 'augmentin', 'piperacillin', 'beta-lactam', 'pen'],
        meds: ['penicillin', 'amoxicillin', 'amox', 'ampicillin', 'cloxacillin', 'co-amoxiclav', 'augmentin', 'piperacillin', 'cefalexin', 'cephalexin', 'cefuroxime', 'ceftriaxone', 'cefixime', 'cefaclor']
    },
    {
        name: 'Cephalosporins',
        triggers: ['cephalosporin', 'cefalexin', 'cephalexin', 'cefuroxime', 'ceftriaxone', 'cefixime', 'cefaclor'],
        meds: ['cefalexin', 'cephalexin', 'cefuroxime', 'ceftriaxone', 'cefixime', 'cefaclor', 'penicillin', 'amoxicillin']
    },
    {
        name: 'NSAIDs / Aspirin',
        triggers: ['nsaid', 'aspirin', 'ibuprofen', 'mefenamic', 'naproxen', 'ketorolac', 'diclofenac', 'celecoxib', 'ponstan', 'advil', 'alaxan'],
        meds: ['aspirin', 'ibuprofen', 'mefenamic', 'naproxen', 'ketorolac', 'diclofenac', 'celecoxib', 'ponstan', 'advil', 'alaxan']
    },
    {
        name: 'Sulfonamides (Sulfa drugs)',
        triggers: ['sulfa', 'sulfonamide', 'cotrimoxazole', 'bactrim', 'sulfamethoxazole', 'trimethoprim', 'sulfadiazine'],
        meds: ['sulfa', 'cotrimoxazole', 'bactrim', 'sulfamethoxazole', 'trimethoprim', 'sulfadiazine']
    },
    {
        name: 'Paracetamol / Acetaminophen',
        triggers: ['paracetamol', 'acetaminophen', 'biogesic', 'tempra', 'calpol'],
        meds: ['paracetamol', 'acetaminophen', 'biogesic', 'tempra', 'calpol']
    },
    {
        name: 'Macrolides',
        triggers: ['azithromycin', 'clarithromycin', 'erythromycin', 'macrolide'],
        meds: ['azithromycin', 'clarithromycin', 'erythromycin']
    },
    {
        name: 'Fluoroquinolones',
        triggers: ['ciprofloxacin', 'cipro', 'levofloxacin', 'ofloxacin', 'quinolone'],
        meds: ['ciprofloxacin', 'cipro', 'levofloxacin', 'ofloxacin']
    }
];

function checkAllergyConflict(medicineName, allergyStr) {
    if (!allergyStr || !medicineName) return { conflict: false };
    const cleanAllergy = allergyStr.toLowerCase();
    const cleanMed = medicineName.toLowerCase().trim();
    if (cleanMed.length < 2) return { conflict: false };

    for (const group of ALLERGY_GROUPS) {
        const matchesTrigger = group.triggers.some(t => cleanAllergy.includes(t));
        if (matchesTrigger) {
            const matchesMed = group.meds.some(m => cleanMed.includes(m));
            if (matchesMed) {
                return {
                    conflict: true,
                    reason: `Cross-reactivity risk with allergy (${allergyStr}) [${group.name}]`
                };
            }
        }
    }

    const tokens = cleanAllergy.split(/[,;\/]+/).map(s => s.trim()).filter(s => s.length >= 3);
    for (const token of tokens) {
        if (cleanMed.includes(token) || (cleanMed.length >= 4 && token.includes(cleanMed))) {
            return {
                conflict: true,
                reason: `Direct match with patient allergy "${token}"`
            };
        }
    }

    return { conflict: false };
}

// Test cases
const tests = [
    { allergy: 'Penicillin', med: 'Amoxicillin 500mg', expectConflict: true },
    { allergy: 'Penicillin', med: 'Co-Amoxiclav 625mg', expectConflict: true },
    { allergy: 'Penicillin', med: 'Cephalexin 500mg', expectConflict: true },
    { allergy: 'Penicillin', med: 'Paracetamol 500mg', expectConflict: false },
    { allergy: 'Aspirin', med: 'Ibuprofen 400mg', expectConflict: true },
    { allergy: 'Aspirin', med: 'Mefenamic Acid', expectConflict: true },
    { allergy: 'Aspirin', med: 'Amoxicillin 500mg', expectConflict: false },
    { allergy: 'Sulfa', med: 'Cotrimoxazole', expectConflict: true },
    { allergy: 'Sulfa', med: 'Bactrim Forte', expectConflict: true },
    { allergy: 'Paracetamol', med: 'Biogesic 500mg', expectConflict: true },
    { allergy: 'Ciprofloxacin', med: 'Levofloxacin 500mg', expectConflict: true },
    { allergy: 'Doxycycline', med: 'Doxycycline 100mg', expectConflict: true }
];

let allPassed = true;
for (const t of tests) {
    const res = checkAllergyConflict(t.med, t.allergy);
    if (res.conflict !== t.expectConflict) {
        console.error(`FAIL: Allergy '${t.allergy}' with Med '${t.med}' expected conflict=${t.expectConflict} but got ${res.conflict}`);
        allPassed = false;
    }
}
if (allPassed) {
    console.log('ALL_JS_TESTS_PASSED');
}
JS;

file_put_contents(__DIR__ . '/test_allergy_algo.js', $jsTestScript);
$nodeOutput = shell_exec('node ' . escapeshellarg(__DIR__ . '/test_allergy_algo.js'));
unlink(__DIR__ . '/test_allergy_algo.js');
assertCondition("JS Allergy cross-check unit tests (12 clinical scenarios) all passed", strpos($nodeOutput, 'ALL_JS_TESTS_PASSED') !== false);

echo "\n------------------------------------------------------\n";
echo "SUMMARY: Passed: $passCount | Failed: $failCount\n";
echo "------------------------------------------------------\n";

if ($failCount === 0) {
    echo ">>> PHASE 2 VERIFICATION SUCCEEDED! <<<\n";
    exit(0);
} else {
    echo ">>> SOME CHECKS FAILED! <<<\n";
    exit(1);
}
