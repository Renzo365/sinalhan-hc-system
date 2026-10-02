<?php
/**
 * Test Archive Phase 2 Implementation
 */

$viewPath = dirname(__DIR__) . '/app/Views/archive/patients.php';
$content = file_get_contents($viewPath);

$tests = [];
function recordTest(&$tests, $name, $pass, $details = '') {
    $tests[] = ['name' => $name, 'pass' => $pass, 'details' => $details];
    echo ($pass ? " [PASS] " : " [FAIL] ") . $name . ($details ? " ($details)" : "") . "\n";
}

echo "=== STARTING ARCHIVE PHASE 2 VERIFICATION SUITE ===\n\n";

// 1. Check searching: false
$hasSearchingFalse = substr_count($content, '"searching": false');
recordTest($tests, "All 3 DataTables have 'searching': false", $hasSearchingFalse === 3, "Found $hasSearchingFalse occurrences");

// 2. Check delegated click handlers
$hasDelegatedPatient = strpos($content, "\$(document).on('click', '.btn-restore-patient'") !== false;
recordTest($tests, "Delegated click handler for .btn-restore-patient", $hasDelegatedPatient);

$hasDelegatedConsultation = strpos($content, "\$(document).on('click', '.btn-restore-consultation'") !== false;
recordTest($tests, "Delegated click handler for .btn-restore-consultation", $hasDelegatedConsultation);

$hasDelegatedUser = strpos($content, "\$(document).on('click', '.btn-restore-user'") !== false;
recordTest($tests, "Delegated click handler for .btn-restore-user", $hasDelegatedUser);

// 3. Check outline buttons
$hasRestoreRecordBtn = strpos($content, 'Restore Record') !== false && strpos($content, 'btn-outline-success') !== false;
recordTest($tests, "Standardized 'Restore Record' outline button in Tab 1", $hasRestoreRecordBtn);

$hasRestoreEncounterBtn = strpos($content, 'Restore Encounter') !== false && strpos($content, 'btn-outline-success') !== false;
recordTest($tests, "Standardized 'Restore Encounter' outline button in Tab 2", $hasRestoreEncounterBtn);

$hasRestoreAccountBtn = strpos($content, 'Restore Account') !== false && strpos($content, 'btn-outline-success') !== false;
recordTest($tests, "Standardized 'Restore Account' outline button in Tab 3", $hasRestoreAccountBtn);

// 4. Check medical teal styling
$hasTealTabStyle = strpos($content, '#archiveTabs .nav-link.active') !== false && strpos($content, 'var(--color-primary, #0d9488)') !== false;
recordTest($tests, "Medical teal tab navigation styling applied", $hasTealTabStyle);

// 5. Check super admin safeguard
$hasSuperAdminCheck = strpos($content, 'is_super_admin()') !== false && strpos($content, 'Super Admin Only') !== false;
recordTest($tests, "Super Admin safeguard for restoring admin accounts in Tab 3", $hasSuperAdminCheck);

$total = count($tests);
$passed = count(array_filter($tests, fn($t) => $t['pass']));
$failed = $total - $passed;

echo "\n--------------------------------------------------\n";
echo "Phase 2 Automated Verification Results:\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "--------------------------------------------------\n";

if ($failed === 0) {
    echo "ALL PHASE 2 TESTS PASSED SUCCESSFULLY!\n";
    exit(0);
} else {
    echo "SOME TESTS FAILED!\n";
    exit(1);
}
