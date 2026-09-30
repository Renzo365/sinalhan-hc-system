<?php
// Test Suite for Appointment System Remediation - Phase 3
echo "=== Running Appointment System Remediation Phase 3 Verification ===\n\n";

$pass = 0;
$fail = 0;

function assertTest($condition, $description) {
    global $pass, $fail;
    if ($condition) {
        echo " [PASS] $description\n";
        $pass++;
    } else {
        echo " [FAIL] $description\n";
        $fail++;
    }
}

// 1. PHP Syntax Check
$files = [
    'app/Views/appointments/create.php',
    'app/Views/appointments/edit.php'
];

foreach ($files as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    $output = [];
    $returnVar = 0;
    exec("php -l \"$fullPath\"", $output, $returnVar);
    assertTest($returnVar === 0, "Syntax validation: $file is valid PHP");
}

// 2. Patient Search & Identity Card in create.php (Unified Design Pattern)
$createContent = file_get_contents(__DIR__ . '/../app/Views/appointments/create.php');
assertTest(
    strpos($createContent, 'id="patientSearchInput"') !== false,
    'create.php has patientSearchInput input for live searching'
);
assertTest(
    strpos($createContent, 'id="btnClearSearch"') !== false,
    'create.php has btnClearSearch reset button'
);
assertTest(
    strpos($createContent, 'id="searchResultsContainer"') !== false,
    'create.php displays real-time searchResultsContainer dropdown'
);
assertTest(
    strpos($createContent, 'id="patientIdentityCard"') !== false,
    'create.php has patientIdentityCard summary element'
);
assertTest(
    strpos($createContent, 'id="btnChangePatient"') !== false,
    'create.php has btnChangePatient action button'
);
assertTest(
    strpos($createContent, 'renderSearchResults') !== false && strpos($createContent, 'selectPatient') !== false,
    'create.php script implements renderSearchResults and selectPatient functions'
);

// 3. Double-Booking Confirmation Guards
assertTest(
    strpos($createContent, 'Concurrent Appointment Warning') !== false && strpos($createContent, 'doubleBookingConfirmed') !== false,
    'create.php implements SweetAlert2 concurrent appointment confirmation guard'
);
$editContent = file_get_contents(__DIR__ . '/../app/Views/appointments/edit.php');
assertTest(
    strpos($editContent, 'Concurrent Reschedule Warning') !== false && strpos($editContent, 'doubleBookingConfirmed') !== false,
    'edit.php implements SweetAlert2 concurrent reschedule confirmation guard'
);

// 4. Header Hierarchy Polish (No duplicate titles)
assertTest(
    strpos($createContent, 'Appointment Booking Details') !== false,
    'create.php card header uses non-redundant contextual title "Appointment Booking Details"'
);
assertTest(
    strpos($editContent, 'Appointment Update Details') !== false,
    'edit.php card header uses non-redundant contextual title "Appointment Update Details"'
);

// 5. Initial Status Options Streamlining
// In create.php, Cancelled and Missed options must NOT exist in the status dropdown
$createStatusBlock = substr($createContent, strpos($createContent, 'id="status"'), 300);
assertTest(
    strpos($createStatusBlock, 'value="Cancelled"') === false,
    'create.php initial status does NOT offer "Cancelled" status'
);
assertTest(
    strpos($createStatusBlock, 'value="Missed"') === false,
    'create.php initial status does NOT offer "Missed" status'
);
assertTest(
    strpos($createStatusBlock, 'value="Scheduled"') !== false && strpos($createStatusBlock, 'value="Completed"') !== false,
    'create.php offers Scheduled and Completed options for booking'
);

// In edit.php, all 4 statuses must remain valid
$editStatusBlock = substr($editContent, strpos($editContent, 'id="status"'), 800);
assertTest(
    strpos($editStatusBlock, 'value="Scheduled"') !== false &&
    strpos($editStatusBlock, 'value="Completed"') !== false &&
    strpos($editStatusBlock, 'value="Cancelled"') !== false &&
    strpos($editStatusBlock, 'value="Missed"') !== false,
    'edit.php retains all 4 lifecycle statuses (Scheduled, Completed, Cancelled, Missed)'
);

echo "\nSummary: $pass Passed, $fail Failed.\n";
if ($fail > 0) {
    exit(1);
} else {
    echo "All Phase 3 tests passed successfully!\n";
    exit(0);
}
