<?php
/**
 * Test Suite: Consultation Phase 4 Verification
 * Verifies Accessibility (ARIA labels & focus management), Sticky Bottom Actions Dock,
 * Top Header Save Button Mirroring, CSS Contrast Hardening, and Parity between create.php and edit.php.
 */

$rootDir = dirname(__DIR__);
$createFile = $rootDir . '/app/Views/consultations/create.php';
$editFile = $rootDir . '/app/Views/consultations/edit.php';
$cssFile = $rootDir . '/public/assets/css/index.css';

$createContent = file_get_contents($createFile);
$editContent = file_get_contents($editFile);
$cssContent = file_get_contents($cssFile);

$passed = 0;
$failed = 0;

function assertTest($name, $condition, $failDetails = '') {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] $name\n";
        $passed++;
    } else {
        echo " [FAIL] $name" . ($failDetails ? " - $failDetails" : "") . "\n";
        $failed++;
    }
}

echo "======================================================\n";
echo "   CONSULTATION PHASE 4 COMPREHENSIVE VERIFICATION    \n";
echo "======================================================\n\n";

// 1. PHP Syntax
$createLint = shell_exec("php -l " . escapeshellarg($createFile));
assertTest("create.php has no PHP syntax errors", strpos($createLint, 'No syntax errors detected') !== false);

$editLint = shell_exec("php -l " . escapeshellarg($editFile));
assertTest("edit.php has no PHP syntax errors", strpos($editLint, 'No syntax errors detected') !== false);

// 2. Strict Constraint: NO SOAP BADGES
foreach (['[S]', '[O]', '[A]', '[P]'] as $badge) {
    assertTest("create.php contains NO '$badge' badge", strpos($createContent, $badge) === false);
    assertTest("edit.php contains NO '$badge' badge", strpos($editContent, $badge) === false);
}

// 3. ARIA Labels in create.php
assertTest("create.php has aria-label=\"Medicine Name\"", strpos($createContent, 'aria-label="Medicine Name"') !== false);
assertTest("create.php has aria-label=\"Dosage\"", strpos($createContent, 'aria-label="Dosage"') !== false);
assertTest("create.php has aria-label=\"Frequency\"", strpos($createContent, 'aria-label="Frequency"') !== false);
assertTest("create.php has aria-label=\"Duration\"", strpos($createContent, 'aria-label="Duration"') !== false);
assertTest("create.php has aria-label=\"Instructions / Sig\"", strpos($createContent, 'aria-label="Instructions / Sig"') !== false);
assertTest("create.php has aria-label=\"Remove medication row\"", strpos($createContent, 'aria-label="Remove medication row"') !== false);

// 4. ARIA Labels in edit.php
assertTest("edit.php has aria-label=\"Medicine Name\"", strpos($editContent, 'aria-label="Medicine Name"') !== false);
assertTest("edit.php has aria-label=\"Dosage\"", strpos($editContent, 'aria-label="Dosage"') !== false);
assertTest("edit.php has aria-label=\"Frequency\"", strpos($editContent, 'aria-label="Frequency"') !== false);
assertTest("edit.php has aria-label=\"Duration\"", strpos($editContent, 'aria-label="Duration"') !== false);
assertTest("edit.php has aria-label=\"Instructions / Sig\"", strpos($editContent, 'aria-label="Instructions / Sig"') !== false);
assertTest("edit.php has aria-label=\"Remove medication row\"", strpos($editContent, 'aria-label="Remove medication row"') !== false);

// 5. Focus Management & return tr in addPrescriptionRow
assertTest("create.php returns tr from addPrescriptionRow", strpos($createContent, 'return tr;') !== false);
assertTest("edit.php returns tr from addPrescriptionRow", strpos($editContent, 'return tr;') !== false);
assertTest("create.php auto-focuses medicine_name on add click", strpos($createContent, "const medInput = newRow.querySelector('input[name*=\"[medicine_name]\"]');") !== false && strpos($createContent, "medInput.focus();") !== false);
assertTest("edit.php auto-focuses medicine_name on add click", strpos($editContent, "const medInput = newRow.querySelector('input[name*=\"[medicine_name]\"]');") !== false && strpos($editContent, "medInput.focus();") !== false);

// 6. Sticky Bottom Action Bar & Dirty Indicator
assertTest("create.php has #consultationBottomBar", strpos($createContent, 'id="consultationBottomBar"') !== false);
assertTest("edit.php has #consultationBottomBar", strpos($editContent, 'id="consultationBottomBar"') !== false);
assertTest("create.php uses sticky-form-action-bar on bottom bar", strpos($createContent, 'sticky-form-action-bar') !== false);
assertTest("edit.php uses sticky-form-action-bar on bottom bar", strpos($editContent, 'sticky-form-action-bar') !== false);
assertTest("create.php has #consultationDirtyIndicator", strpos($createContent, 'id="consultationDirtyIndicator"') !== false);
assertTest("edit.php has #consultationDirtyIndicator", strpos($editContent, 'id="consultationDirtyIndicator"') !== false);
assertTest("create.php has window.updateConsultationDirtyBadge", strpos($createContent, 'window.updateConsultationDirtyBadge = function') !== false);
assertTest("edit.php has window.updateConsultationDirtyBadge", strpos($editContent, 'window.updateConsultationDirtyBadge = function') !== false);

// 7. Header Navigation & Sticky Bottom Action Submit
assertTest("create.php has clean header back navigation and bottom submit button", strpos($createContent, 'btn-cancel-consultation') !== false && strpos($createContent, 'id="btnSubmitConsultation"') !== false && strpos($createContent, 'id="btnTopSaveConsultation"') === false);
assertTest("edit.php has clean header back navigation and bottom update button", strpos($editContent, 'btn-cancel-consultation') !== false && strpos($editContent, 'id="btnSubmitConsultation"') !== false && strpos($editContent, 'id="btnTopSaveConsultation"') === false);

// 8. CSS Contrast Hardening & Rules
assertTest("index.css has .text-pink with color: #b02a6b", preg_match('/\.text-pink\s*\{\s*color:\s*#b02a6b\s*!important/i', $cssContent) === 1);
assertTest("index.css has .btn-outline-pink with color: #b02a6b", preg_match('/\.btn-outline-pink\s*\{[^}]*color:\s*#b02a6b\s*!important/i', $cssContent) === 1);
assertTest("index.css contains #consultationBottomBar rule", strpos($cssContent, '#consultationBottomBar') !== false);

echo "\n------------------------------------------------------\n";
echo "SUMMARY: Passed: $passed | Failed: $failed\n";
echo "------------------------------------------------------\n";

if ($failed === 0) {
    echo ">>> PHASE 4 VERIFICATION SUCCEEDED! <<<\n";
    exit(0);
} else {
    echo ">>> PHASE 4 VERIFICATION FAILED! <<<\n";
    exit(1);
}
