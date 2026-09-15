<?php
/**
 * Automated QA Test Suite: Table Action Buttons & Growth Modal Refactor
 * Target: app/Views/patients/show.php
 *
 * Checks:
 * 1. PHP Syntax validation (php -l)
 * 2. Elimination of kebab dropdowns in target tables and appointment list
 * 3. Direct inline action buttons presence and styling
 * 4. Exact <th> count and colspan matches for vitals and child growth tables
 * 5. #viewGrowthLogModal DOM elements and cards
 * 6. JS event handlers (.btn-view-growth-log click, hidden.bs.modal backdrop cleanup)
 * 7. CSRF retention on all action forms and valid delete/action routes
 */

$root = dirname(__DIR__);
$targetFile = $root . '/app/Views/patients/show.php';

$tests = [];

function assertTest(string $description, bool $passed, array &$tests, string $detail = ''): void {
    if ($passed) {
        $tests[] = ['desc' => $description, 'status' => 'PASS'];
        echo "[PASS] {$description}\n";
    } else {
        $tests[] = ['desc' => $description, 'status' => 'FAIL', 'detail' => $detail];
        echo "[FAIL] {$description}" . ($detail ? " - {$detail}" : '') . "\n";
    }
}

echo "====================================================================\n";
echo " QA AUTOMATED VERIFICATION: TABLE ACTIONS & GROWTH MODAL REFACTOR\n";
echo " Target: app/Views/patients/show.php\n";
echo "====================================================================\n\n";

// -------------------------------------------------------------------------
// 1. PHP Syntax Check
// -------------------------------------------------------------------------
echo "--- 1. PHP Syntax Validation ---\n";
$syntaxOutput = [];
$syntaxReturn = 0;
exec('php -l ' . escapeshellarg($targetFile), $syntaxOutput, $syntaxReturn);
$syntaxStr = implode(' ', $syntaxOutput);
assertTest(
    "PHP Syntax check passes with 0 lint errors",
    $syntaxReturn === 0 && strpos($syntaxStr, 'No syntax errors detected') !== false,
    $tests,
    $syntaxStr
);

// Read file contents for AST/regex verification
$content = file_get_contents($targetFile);
assertTest(
    "Source file app/Views/patients/show.php is readable and non-empty",
    !empty($content) && strlen($content) > 10000,
    $tests
);

// -------------------------------------------------------------------------
// Helper to extract table or div block by ID
// -------------------------------------------------------------------------
function extractSection(string $html, string $id, string $tag = 'table'): ?string {
    $pattern = '/<' . preg_quote($tag, '/') . '[^>]*id="' . preg_quote($id, '/') . '"[^>]*>(.*?)<\/' . preg_quote($tag, '/') . '>/is';
    if (preg_match($pattern, $html, $m)) {
        return $m[0];
    }
    return null;
}

function extractDivSection(string $html, string $id): ?string {
    // Locate <div ... id="$id" ...>
    $pattern = '/<div[^>]*id="' . preg_quote($id, '/') . '"[^>]*>/i';
    if (!preg_match($pattern, $html, $m, PREG_OFFSET_CAPTURE)) {
        return null;
    }
    $start = $m[0][1];
    $cursor = $start + strlen($m[0][0]);
    $openDivs = 1;
    $length = strlen($html);

    while ($cursor < $length && $openDivs > 0) {
        if (preg_match('/<(\/?)div[^>]*>/i', substr($html, $cursor), $divMatch, PREG_OFFSET_CAPTURE)) {
            $cursor += $divMatch[0][1] + strlen($divMatch[0][0]);
            if ($divMatch[1][0] === '/') {
                $openDivs--;
            } else {
                $openDivs++;
            }
        } else {
            break;
        }
    }
    return substr($html, $start, $cursor - $start);
}

// -------------------------------------------------------------------------
// 2. Kebab Dropdown Elimination Verification
// -------------------------------------------------------------------------
echo "\n--- 2. Elimination of Kebab Dropdowns ---\n";

$tablesToCheck = [
    '#consultationsTable'   => extractSection($content, 'consultationsTable', 'table'),
    '#vitalsTable'          => extractSection($content, 'vitalsTable', 'table'),
    '#immunizationsTable'   => extractSection($content, 'immunizationsTable', 'table'),
    '#prenatalVisitsTable'  => extractSection($content, 'prenatalVisitsTable', 'table'),
    '#childGrowthTable'     => extractSection($content, 'childGrowthTable', 'table'),
    '#tab-appointments'     => extractDivSection($content, 'tab-appointments'),
];

foreach ($tablesToCheck as $name => $block) {
    assertTest(
        "Container {$name} successfully extracted from markup",
        !empty($block),
        $tests
    );

    if (!empty($block)) {
        $hasDropdownToggle = (strpos($block, 'dropdown-toggle') !== false);
        $hasDropdownMenu   = (strpos($block, 'dropdown-menu') !== false);
        $hasThreeDots      = (strpos($block, 'bi-three-dots') !== false || strpos($block, 'bi-three-dots-vertical') !== false);
        $hasBsToggleDropdown = (strpos($block, 'data-bs-toggle="dropdown"') !== false);

        assertTest(
            "{$name} has NO dropdown-toggle button or class",
            !$hasDropdownToggle && !$hasBsToggleDropdown,
            $tests,
            "Found dropdown toggle in {$name}"
        );
        assertTest(
            "{$name} has NO dropdown-menu container",
            !$hasDropdownMenu,
            $tests,
            "Found dropdown-menu in {$name}"
        );
        assertTest(
            "{$name} has NO kebab dots icon (bi-three-dots / bi-three-dots-vertical)",
            !$hasThreeDots,
            $tests,
            "Found three dots icon in {$name}"
        );
    }
}

// -------------------------------------------------------------------------
// 3. Presence of Direct Inline Action Buttons
// -------------------------------------------------------------------------
echo "\n--- 3. Presence of Direct Inline Action Buttons ---\n";

$consultationsBlock = $tablesToCheck['#consultationsTable'] ?? '';
assertTest(
    "Consultations table has inline .view-consultation-btn with bi-eye icon",
    strpos($consultationsBlock, 'view-consultation-btn') !== false &&
    strpos($consultationsBlock, 'bi bi-eye') !== false,
    $tests
);
assertTest(
    "Consultations table has inline .btn-archive-consultation with bi-archive icon",
    strpos($consultationsBlock, 'btn-archive-consultation') !== false &&
    strpos($consultationsBlock, 'bi bi-archive') !== false,
    $tests
);

$vitalsBlock = $tablesToCheck['#vitalsTable'] ?? '';
assertTest(
    "Vitals table has inline .btn-view-vitals with bi-eye icon",
    strpos($vitalsBlock, 'btn-view-vitals') !== false &&
    strpos($vitalsBlock, 'bi bi-eye') !== false,
    $tests
);
assertTest(
    "Vitals table has inline .btn-delete-vital with bi-trash icon",
    strpos($vitalsBlock, 'btn-delete-vital') !== false &&
    strpos($vitalsBlock, 'bi bi-trash') !== false,
    $tests
);

$immunizationsBlock = $tablesToCheck['#immunizationsTable'] ?? '';
assertTest(
    "Immunizations table has inline .btn-delete-immunization with bi-trash icon",
    strpos($immunizationsBlock, 'btn-delete-immunization') !== false &&
    strpos($immunizationsBlock, 'bi bi-trash') !== false,
    $tests
);

$prenatalVisitsBlock = $tablesToCheck['#prenatalVisitsTable'] ?? '';
assertTest(
    "Prenatal visits table has inline .btn-delete-prenatal-visit with bi-trash icon",
    strpos($prenatalVisitsBlock, 'btn-delete-prenatal-visit') !== false &&
    strpos($prenatalVisitsBlock, 'bi bi-trash') !== false,
    $tests
);

$childGrowthBlock = $tablesToCheck['#childGrowthTable'] ?? '';
assertTest(
    "Child growth table has inline .btn-view-growth-log with bi-eye icon",
    strpos($childGrowthBlock, 'btn-view-growth-log') !== false &&
    strpos($childGrowthBlock, 'bi bi-eye') !== false,
    $tests
);
assertTest(
    "Child growth table has inline delete submit button with bi-trash icon",
    strpos($childGrowthBlock, 'bi bi-trash') !== false &&
    strpos($childGrowthBlock, 'action="<?= url(\'/wellbaby/growth-log/\'') !== false,
    $tests
);

$appointmentsBlock = $tablesToCheck['#tab-appointments'] ?? '';
assertTest(
    "Appointments list has direct inline reschedule/edit link",
    strpos($appointmentsBlock, '/appointments/\' . $a[\'id\'] . \'/edit') !== false &&
    strpos($appointmentsBlock, 'bi bi-pencil-square') !== false,
    $tests
);
assertTest(
    "Appointments list has direct inline .btn-cancel-appointment button",
    strpos($appointmentsBlock, 'btn-cancel-appointment') !== false &&
    strpos($appointmentsBlock, 'bi bi-x-circle') !== false,
    $tests
);

// -------------------------------------------------------------------------
// 4. Column Counts and Colspan Alignment
// -------------------------------------------------------------------------
echo "\n--- 4. Column Counts & Empty State Colspan Alignment ---\n";

// Vitals Table
if (!empty($vitalsBlock)) {
    preg_match_all('/<th\b[^>]*>(.*?)<\/th>/is', $vitalsBlock, $vitalsThMatches);
    $vitalsThCount = count($vitalsThMatches[0]);
    assertTest(
        "#vitalsTable has exactly 6 <th> columns in thead",
        $vitalsThCount === 6,
        $tests,
        "Actual <th> count: {$vitalsThCount}"
    );

    preg_match('/<td[^>]*colspan="(\d+)"[^>]*>/i', $vitalsBlock, $vitalsColspanMatch);
    $vitalsColspan = isset($vitalsColspanMatch[1]) ? (int)$vitalsColspanMatch[1] : 0;
    assertTest(
        "#vitalsTable empty state uses colspan=\"6\"",
        $vitalsColspan === 6,
        $tests,
        "Actual colspan: {$vitalsColspan}"
    );
}

// Child Growth Table
if (!empty($childGrowthBlock)) {
    preg_match_all('/<th\b[^>]*>(.*?)<\/th>/is', $childGrowthBlock, $growthThMatches);
    $growthThCount = count($growthThMatches[0]);
    assertTest(
        "#childGrowthTable has exactly 7 <th> columns in thead",
        $growthThCount === 7,
        $tests,
        "Actual <th> count: {$growthThCount}"
    );

    preg_match('/<td[^>]*colspan="(\d+)"[^>]*>/i', $childGrowthBlock, $growthColspanMatch);
    $growthColspan = isset($growthColspanMatch[1]) ? (int)$growthColspanMatch[1] : 0;
    assertTest(
        "#childGrowthTable empty state uses colspan=\"7\"",
        $growthColspan === 7,
        $tests,
        "Actual colspan: {$growthColspan}"
    );
}

// -------------------------------------------------------------------------
// 5. #viewGrowthLogModal Structure and Elements
// -------------------------------------------------------------------------
echo "\n--- 5. #viewGrowthLogModal DOM Elements & Anthropometric Cards ---\n";

$modalBlock = extractDivSection($content, 'viewGrowthLogModal');
assertTest(
    "#viewGrowthLogModal element exists in DOM",
    !empty($modalBlock),
    $tests
);

if (!empty($modalBlock)) {
    // Test for individual ID targets
    $requiredModalElements = [
        'modalGrowthDate'        => 'Visit Date text placeholder',
        'modalGrowthAge'         => 'Age text placeholder',
        'modalGrowthWeight'      => 'Weight anthropometric placeholder',
        'modalGrowthHeight'      => 'Height anthropometric placeholder',
        'modalGrowthHead'        => 'Head circumference placeholder',
        'modalGrowthChest'       => 'Chest circumference placeholder',
        'modalGrowthTemp'        => 'Temperature placeholder',
        'modalGrowthFeeding'     => 'Feeding method placeholder',
        'modalGrowthSupplements' => 'Supplements placeholder',
        'modalGrowthTcb'         => 'TCB / developmental milestones remarks container',
    ];

    foreach ($requiredModalElements as $elemId => $desc) {
        assertTest(
            "#viewGrowthLogModal contains #{$elemId} ({$desc})",
            strpos($modalBlock, "id=\"{$elemId}\"") !== false,
            $tests
        );
    }

    // Assert cards representing the anthropometric sections
    assertTest(
        "#viewGrowthLogModal contains 'Weight & Height' card grouping",
        strpos($modalBlock, 'Weight &amp; Height') !== false,
        $tests
    );
    assertTest(
        "#viewGrowthLogModal contains 'Head Circumference' card",
        strpos($modalBlock, 'Head Circumference') !== false,
        $tests
    );
    assertTest(
        "#viewGrowthLogModal contains 'Chest Circumference' card",
        strpos($modalBlock, 'Chest Circumference') !== false,
        $tests
    );
    assertTest(
        "#viewGrowthLogModal contains 'Temperature' card",
        strpos($modalBlock, 'Temperature') !== false,
        $tests
    );
    assertTest(
        "#viewGrowthLogModal contains 'Feeding Method' card",
        strpos($modalBlock, 'Feeding Method') !== false,
        $tests
    );
    assertTest(
        "#viewGrowthLogModal contains 'Supplements' card",
        strpos($modalBlock, 'Supplements') !== false,
        $tests
    );
    assertTest(
        "#viewGrowthLogModal contains 'TCB / Developmental Milestones & Remarks' section",
        strpos($modalBlock, 'TCB / Developmental Milestones &amp; Remarks') !== false,
        $tests
    );
}

// -------------------------------------------------------------------------
// 6. JavaScript Event Handlers & Modal Lifecycle Cleanup
// -------------------------------------------------------------------------
echo "\n--- 6. JS Event Handlers & Modal Lifecycle Cleanup ---\n";

assertTest(
    "JS obtains element reference for viewGrowthLogModalEl",
    strpos($content, "document.getElementById('viewGrowthLogModal')") !== false,
    $tests
);

assertTest(
    "JS attaches 'hidden.bs.modal' cleanup listener to viewGrowthLogModalEl",
    strpos($content, "viewGrowthLogModalEl.addEventListener('hidden.bs.modal'") !== false,
    $tests
);

assertTest(
    "hidden.bs.modal cleanup purges lingering backdrops and restores body scrolling",
    strpos($content, "document.querySelectorAll('.modal-backdrop').forEach(b => b.remove())") !== false &&
    strpos($content, "document.body.classList.remove('modal-open')") !== false,
    $tests
);

assertTest(
    "JS listens to click events on '.btn-view-growth-log'",
    strpos($content, "document.querySelectorAll('.btn-view-growth-log').forEach") !== false,
    $tests
);

// Verify data attribute extraction in JS handler
$expectedDataAttrs = ['data-date', 'data-age', 'data-weight', 'data-height', 'data-head', 'data-chest', 'data-temp', 'data-feeding', 'data-supplements', 'data-tcb'];
foreach ($expectedDataAttrs as $attr) {
    assertTest(
        "JS click handler extracts {$attr} from clicked button",
        strpos($content, "this.getAttribute('{$attr}')") !== false,
        $tests
    );
}

assertTest(
    "JS click handler shows modal via bootstrap.Modal.getOrCreateInstance",
    strpos($content, "bootstrap.Modal.getOrCreateInstance(viewGrowthLogModalEl).show()") !== false,
    $tests
);

// -------------------------------------------------------------------------
// 7. CSRF Retention & Delete/Action Route Security
// -------------------------------------------------------------------------
echo "\n--- 7. CSRF Retention & Action Route Security ---\n";

$hiddenForms = [
    'archiveConsultationForm' => [
        'csrf'  => true,
        'route' => '/consultations/' // action dynamically assigned in script or inline
    ],
    'deleteVitalForm' => [
        'csrf'  => true,
        'route' => '/vital-signs/'
    ],
    'deleteImmunizationForm' => [
        'csrf'  => true,
        'route' => '/immunizations/'
    ],
    'deletePrenatalVisitForm' => [
        'csrf'  => true,
        'route' => '/prenatal/visit/'
    ],
    'cancelAppointmentForm' => [
        'csrf'  => true,
        'route' => '/appointments/'
    ],
];

foreach ($hiddenForms as $formId => $rules) {
    $formBlock = extractSection($content, $formId, 'form');
    assertTest(
        "Hidden action form #{$formId} exists with method='POST'",
        !empty($formBlock) && stripos($formBlock, 'method="POST"') !== false,
        $tests
    );

    if (!empty($formBlock)) {
        assertTest(
            "Hidden form #{$formId} retains <?= csrf_field() ?>",
            strpos($formBlock, '<?= csrf_field() ?>') !== false,
            $tests
        );
    }

    assertTest(
        "JS handler configures valid endpoint for #{$formId} ({$rules['route']})",
        strpos($content, $rules['route']) !== false,
        $tests
    );
}

// Child Growth Visit direct inline delete form
assertTest(
    "Child growth inline delete form retains <?= csrf_field() ?>",
    strpos($childGrowthBlock, '<?= csrf_field() ?>') !== false,
    $tests
);
assertTest(
    "Child growth inline delete form passes hidden patient_id parameter",
    strpos($childGrowthBlock, '<input type="hidden" name="patient_id" value="<?= $patient[\'id\'] ?>">') !== false,
    $tests
);
assertTest(
    "Child growth inline delete form targets correct route /wellbaby/growth-log/{id}/delete",
    strpos($childGrowthBlock, 'action="<?= url(\'/wellbaby/growth-log/\' . $gl[\'id\'] . \'/delete\') ?>"') !== false,
    $tests
);

// -------------------------------------------------------------------------
// Final Results & Classification
// -------------------------------------------------------------------------
echo "\n====================================================================\n";
$total = count($tests);
$passed = count(array_filter($tests, fn($t) => $t['status'] === 'PASS'));
$failed = $total - $passed;

echo "TOTAL ASSERTIONS RUN: {$total} | PASS: {$passed} | FAIL: {$failed}\n";
echo "====================================================================\n";

if ($failed > 0) {
    echo "\n[FAILURES DETECTED]:\n";
    foreach ($tests as $t) {
        if ($t['status'] === 'FAIL') {
            echo " - [CODE_BUG] {$t['desc']}: {$t['detail']}\n";
        }
    }
    exit(1);
}

echo "\nALL AUDIT AND REGRESSION ASSERTIONS PASSED PERFECTLY.\n";
exit(0);
