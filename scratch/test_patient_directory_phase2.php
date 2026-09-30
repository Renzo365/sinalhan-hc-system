<?php
// Test Suite for Patient Directory Phase 2: Table Ergonomics, Clickable Identifiers, Contact/Address & Copy Polish

echo "=== PHASE 2 TEST SUITE: PATIENT DIRECTORY TABLE ERGONOMICS & POLISH ===\n\n";

$passed = 0;
$failed = 0;

function assertCondition($cond, $desc) {
    global $passed, $failed;
    if ($cond) {
        echo " [PASS] {$desc}\n";
        $passed++;
    } else {
        echo " [FAIL] {$desc}\n";
        $failed++;
    }
}

// 1. Syntax check
$phpLint = shell_exec('php -l app/Views/patients/index.php');
assertCondition(strpos($phpLint, 'No syntax errors detected') !== false, "Syntax check: app/Views/patients/index.php");

$viewContent = file_get_contents('app/Views/patients/index.php');

// 2. Clickable patient_no badge link
assertCondition(strpos($viewContent, 'class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace text-decoration-none') !== false, "Patient No rendered as styled badge link");
assertCondition(strpos($viewContent, 'bi-person-vcard') !== false, "Patient No badge contains vcard icon");

// 3. Contact Number and Address subtext under patient name
assertCondition(strpos($viewContent, 'bi-geo-alt') !== false, "Address rendered with geo-alt location icon");
assertCondition(strpos($viewContent, 'bi-telephone') !== false, "Contact number rendered with telephone icon");
assertCondition(strpos($viewContent, 'text-truncate') !== false, "Long addresses protected with text-truncate");

// 4. Physical envelope copy stutter fix
assertCondition(strpos($viewContent, 'Env #<?=') === false, "Copy stutter 'Env #' eliminated");
assertCondition(strpos($viewContent, '#<?= h(ltrim($p[\'envelope_no\'], \'#\')) ?>') !== false, "Envelope rendered cleanly as #ENV-XXXX");

// 5. DOB and numeric data-order on age
assertCondition(strpos($viewContent, 'data-order="<?= (int)$p[\'age\'] ?>"') !== false, "Age cell includes data-order with integer age for DataTables numeric sorting");
assertCondition(strpos($viewContent, 'date(\'M d, Y\', strtotime($p[\'dob\']))') !== false, "DOB rendered in friendly format under age");

// 6. PhilHealth status column and badges
assertCondition(strpos($viewContent, '<th>PhilHealth</th>') !== false, "Table header contains PhilHealth column");
assertCondition(strpos($viewContent, 'bi-shield-check') !== false, "Member status rendered with shield-check badge");
assertCondition(strpos($viewContent, 'bi-shield-plus') !== false, "Dependent status rendered with shield-plus badge");
assertCondition(strpos($viewContent, 'bi-dash-circle') !== false, "None/unenrolled status rendered with dash-circle badge");

// 7. DataTables columnDefs updated
assertCondition(strpos($viewContent, '"targets": 7') !== false, "DataTables non-orderable targets set to column 7 (Action column)");

// 8. Clean of deprecated barangay field
assertCondition(stripos($viewContent, 'barangay') === false, "Zero traces of 'barangay' field in patient index view");

echo "\n--------------------------------------------------\n";
echo "Phase 2 Automated Verification Results:\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "--------------------------------------------------\n";

if ($failed > 0) {
    exit(1);
}
