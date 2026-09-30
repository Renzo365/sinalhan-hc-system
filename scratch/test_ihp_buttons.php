<?php
/**
 * Test IHP action button cleanups
 */
$ihpPath = __DIR__ . '/../app/Views/patients/partials/tab_ihp.php';
$content = file_get_contents($ihpPath);

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

// 1. Check PHP syntax of tab_ihp.php
exec('php -l ' . escapeshellarg($ihpPath), $out, $ret);
assertCheck("tab_ihp.php has valid PHP syntax", $ret === 0);

// 2. Check that Edit IHP Record is wrapped with if ($hasAnyIhpRecord)
assertCheck("Edit IHP Record button only renders when hasAnyIhpRecord is true", 
    strpos($content, 'if ($hasAnyIhpRecord):') !== false &&
    preg_match('/if\s*\(\$hasAnyIhpRecord\):\s*\?>\s*<button[^>]*onclick="enterIhpEditMode\(\)"[^>]*>.*?Edit IHP Record.*?<\/button>\s*<\?php\s+endif;\s*\?>/s', $content) === 1
);

// 3. Check that in edit mode, the top header has no duplicate Cancel / Save buttons
$editModeStart = strpos($content, '<div id="ihp-edit-mode"');
$navStart = strpos($content, 'id="ihpSectionNav"');
$editHeaderChunk = substr($content, $editModeStart, $navStart - $editModeStart);

assertCheck("Edit mode top header does NOT contain duplicate Cancel button", strpos($editHeaderChunk, 'onclick="cancelIhpEditMode()"') === false);
assertCheck("Edit mode top header does NOT contain duplicate Save IHP Record button", strpos($editHeaderChunk, 'Save IHP Record') === false);

// 4. Check that sticky bottom bar still contains Cancel and Save IHP Record
$bottomBarChunk = substr($content, strpos($content, 'sticky-bottom'));
assertCheck("Edit mode sticky bottom bar contains Cancel button", strpos($bottomBarChunk, 'onclick="cancelIhpEditMode()"') !== false);
assertCheck("Edit mode sticky bottom bar contains Save IHP Record submit button", strpos($bottomBarChunk, 'Save IHP Record') !== false);

echo "\nSummary: $passed passed, $failed failed.\n";
if ($failed > 0) exit(1);
