<?php
/**
 * QA Verification for PCB Ledger Update Dates Modal Refactor
 */

$root = dirname(__DIR__);
require_once $root . '/app/helpers.php';

$showFile = $root . '/app/Views/patients/show.php';
$controllerFile = $root . '/app/Controllers/PcbLedgerController.php';

$showContent = file_get_contents($showFile);
$controllerContent = file_get_contents($controllerFile);

$tests = [];

function assertTest($name, $condition, &$tests) {
    if ($condition) {
        $tests[] = ['name' => $name, 'status' => 'PASS'];
        echo "[PASS] {$name}\n";
    } else {
        $tests[] = ['name' => $name, 'status' => 'FAIL'];
        echo "[FAIL] {$name}\n";
    }
}

echo "=== Running PCB Ledger Modal Refactor QA Tests ===\n\n";

// 1. Check old accordion collapse removal
assertTest(
    "Old collapse element #obligatedEditCollapse is completely removed",
    strpos($showContent, 'id="obligatedEditCollapse"') === false,
    $tests
);

// 2. Check old collapse toggle removal
assertTest(
    "Old data-bs-toggle=\"collapse\" targeting #obligatedEditCollapse is removed",
    strpos($showContent, 'data-bs-target="#obligatedEditCollapse"') === false,
    $tests
);

// 3. Check new modal trigger exists
assertTest(
    "Modal trigger button data-bs-toggle=\"modal\" data-bs-target=\"#obligatedEditModal\" exists",
    strpos($showContent, 'data-bs-toggle="modal" data-bs-target="#obligatedEditModal"') !== false,
    $tests
);

// 4. Check modal definition exists
assertTest(
    "Modal container with id=\"obligatedEditModal\" exists",
    strpos($showContent, '<div class="modal fade" id="obligatedEditModal"') !== false,
    $tests
);

// 5. Check modal uses modal-lg and centered dialog
assertTest(
    "Modal dialog is styled with modal-dialog-centered and modal-lg",
    strpos($showContent, 'modal-dialog modal-dialog-centered modal-lg') !== false,
    $tests
);

// 6. Check CSRF field is present in modal form
assertTest(
    "Modal form contains csrf_field()",
    strpos($showContent, 'action="<?= url(\'/patients/\' . $patient[\'id\'] . \'/pcb/obligated\') ?>"') !== false
    && strpos($showContent, '<?= csrf_field() ?>') !== false,
    $tests
);

// 7. Check all quarterly fields exist in modal form
$requiredFields = [
    'is_hypertensive',
    'bp_q1', 'bp_q2', 'bp_q3', 'bp_q4',
    'cbe_q1', 'cbe_q2', 'cbe_q3', 'cbe_q4',
    'via_q1', 'via_q2', 'via_q3', 'via_q4',
    'remarks'
];

foreach ($requiredFields as $field) {
    assertTest(
        "Modal form contains input field: {$field}",
        strpos($showContent, "name=\"{$field}\"") !== false,
        $tests
    );
}

// 8. Check CSRF check in PcbLedgerController
assertTest(
    "PcbLedgerController@saveObligated validates CSRF token",
    strpos($controllerContent, 'hash_equals(csrf_token(), $token)') !== false,
    $tests
);

$passed = count(array_filter($tests, fn($t) => $t['status'] === 'PASS'));
$total = count($tests);

echo "\nSummary: {$passed}/{$total} tests passed.\n";

if ($passed !== $total) {
    exit(1);
}
exit(0);
