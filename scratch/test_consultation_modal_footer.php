<?php
/**
 * Test Consultation View Modal Footer Cleanup
 */
$modalsPath = __DIR__ . '/../app/Views/patients/partials/modals.php';
$scriptsPath = __DIR__ . '/../app/Views/patients/partials/scripts.php';

$modalsContent = file_get_contents($modalsPath);
$scriptsContent = file_get_contents($scriptsPath);

$passed = 0;
$failed = 0;

function assertCheck($desc, $cond) {
    global $passed, $failed;
    if ($cond) {
        echo " [PASS] $desc\n";
        $passed++;
    } else {
        echo " [FAIL] $desc\n";
        $failed++;
    }
}

// 1. Modals.php check
assertCheck("modals.php has viewConsultationModal", strpos($modalsContent, 'id="viewConsultationModal"') !== false);
assertCheck("modals.php has Print Record button", strpos($modalsContent, 'Print Record') !== false);
assertCheck("modals.php uses btn-outline-secondary for Print Record", strpos($modalsContent, 'btn-outline-secondary btn-sm px-3 d-inline-flex align-items-center') !== false);

// 2. Scripts.php check
assertCheck("scripts.php does NOT contain duplicate 'Print Note'", strpos($scriptsContent, 'Print Note') === false);
assertCheck("scripts.php retains Edit Consultation button when authorized", strpos($scriptsContent, 'Edit Consultation') !== false);
assertCheck("scripts.php has Close button in footer", strpos($scriptsContent, 'data-bs-dismiss="modal">Close</button>') !== false);

echo "\nSummary: $passed passed, $failed failed.\n";
if ($failed > 0) exit(1);
