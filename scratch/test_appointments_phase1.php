<?php
// Test Suite for Appointment System Remediation - Phase 1
echo "=== Running Appointment System Remediation Phase 1 Verification ===\n\n";

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
    'app/Views/appointments/index.php',
    'app/Views/appointments/create.php',
    'app/Views/appointments/edit.php',
    'app/Models/Appointment.php'
];

foreach ($files as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    $output = [];
    $returnVar = 0;
    exec("php -l \"$fullPath\"", $output, $returnVar);
    assertTest($returnVar === 0, "Syntax validation: $file is valid PHP");
}

// 2. DataTables Chronological Sorting in index.php
$indexContent = file_get_contents(__DIR__ . '/../app/Views/appointments/index.php');
assertTest(
    strpos($indexContent, 'data-order="<?= h($a[\'appointment_date\']) ?>"') !== false,
    'index.php has data-order ISO date attribute for chronological sorting'
);
assertTest(
    strpos($indexContent, "data-order=\"<?= date('H:i:s', strtotime(\$a['appointment_time'])) ?>\"") !== false,
    'index.php has data-order 24-hr time attribute for chronological sorting'
);

// 3. Category Filter Aliases in Appointment.php
$modelContent = file_get_contents(__DIR__ . '/../app/Models/Appointment.php');
assertTest(
    strpos($modelContent, "in_array(\$prog, ['Maternal Care', 'Prenatal Care'], true)") !== false,
    'Appointment.php contains program type alias mapping for Maternal Care / Prenatal Care'
);
assertTest(
    strpos($modelContent, "in_array(\$prog, ['Well-Baby Care', 'Well Baby Immunization'], true)") !== false,
    'Appointment.php contains program type alias mapping for Well-Baby Care / Well Baby Immunization'
);
assertTest(
    strpos($modelContent, "in_array(\$prog, ['General OPD', 'Consultation'], true)") !== false,
    'Appointment.php contains program type alias mapping for General OPD / Consultation'
);
assertTest(
    strpos($modelContent, "a.program_type IN ('Maternal Care', 'Prenatal Care')") !== false,
    'Appointment.php uses SQL IN clause for category aliases'
);

// 4. Accessibility and ARIA in create.php
$createContent = file_get_contents(__DIR__ . '/../app/Views/appointments/create.php');
assertTest(
    strpos($createContent, 'role="radiogroup"') !== false,
    'create.php has role="radiogroup" on slot containers'
);
assertTest(
    strpos($createContent, 'role="radio"') !== false && strpos($createContent, 'tabindex="0"') !== false,
    'create.php slot cards have role="radio" and tabindex="0"'
);
assertTest(
    strpos($createContent, "e.key === ' ' || e.key === 'Enter'") !== false,
    'create.php slot cards support keyboard activation via Space and Enter'
);
assertTest(
    strpos($createContent, 'aria-checked') !== false,
    'create.php dynamic updates manage aria-checked attribute'
);
assertTest(
    strpos($createContent, "alert(") === false,
    'create.php does NOT contain native browser alert()'
);
assertTest(
    strpos($createContent, 'timeSlotErrorAlert') !== false && strpos($createContent, 'showTimeSlotError') !== false,
    'create.php uses accessible inline error banner #timeSlotErrorAlert'
);

// 5. Accessibility and ARIA in edit.php
$editContent = file_get_contents(__DIR__ . '/../app/Views/appointments/edit.php');
assertTest(
    strpos($editContent, 'role="radiogroup"') !== false,
    'edit.php has role="radiogroup" on slot containers'
);
assertTest(
    strpos($editContent, 'role="radio"') !== false && strpos($editContent, 'tabindex="0"') !== false,
    'edit.php slot cards have role="radio" and tabindex="0"'
);
assertTest(
    strpos($editContent, "e.key === ' ' || e.key === 'Enter'") !== false,
    'edit.php slot cards support keyboard activation via Space and Enter'
);
assertTest(
    strpos($editContent, 'aria-checked') !== false,
    'edit.php dynamic updates manage aria-checked attribute'
);
assertTest(
    strpos($editContent, "alert(") === false,
    'edit.php does NOT contain native browser alert()'
);
assertTest(
    strpos($editContent, 'timeSlotErrorAlert') !== false && strpos($editContent, 'showTimeSlotError') !== false,
    'edit.php uses accessible inline error banner #timeSlotErrorAlert'
);

// 6. Focus visible CSS rule in index.css
$cssContent = file_get_contents(__DIR__ . '/../public/assets/css/index.css');
assertTest(
    strpos($cssContent, '.time-slot-card:focus-visible') !== false,
    'public/assets/css/index.css has .time-slot-card:focus-visible rule for keyboard accessibility'
);

// 7. Database execution test with Appointment model
require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/../app/helpers.php';

try {
    $apptModel = new \App\Models\Appointment();
    $res1 = $apptModel->findAll(['program_type' => 'Maternal Care']);
    assertTest(is_array($res1), 'Appointment::findAll with Maternal Care runs without error (' . count($res1) . ' records found)');

    $res2 = $apptModel->findAll(['program_type' => 'Well-Baby Care']);
    assertTest(is_array($res2), 'Appointment::findAll with Well-Baby Care runs without error (' . count($res2) . ' records found)');

    $res3 = $apptModel->findAll(['program_type' => 'General OPD']);
    assertTest(is_array($res3), 'Appointment::findAll with General OPD runs without error (' . count($res3) . ' records found)');
} catch (\Throwable $e) {
    assertTest(false, "Appointment query execution failed: " . $e->getMessage());
}

echo "\nSummary: $pass Passed, $fail Failed.\n";
if ($fail > 0) {
    exit(1);
} else {
    echo "All Phase 1 tests passed successfully!\n";
    exit(0);
}
