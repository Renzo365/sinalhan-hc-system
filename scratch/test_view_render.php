<?php

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/../app/helpers.php';

echo "=== TESTING VIEW RENDERING ===\n";

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['user_role'] = 'admin';
$_SESSION['user_fullname'] = 'System Administrator';
$_SESSION['csrf_token'] = 'token12345';

// Mock user data
$user = [
    'id' => 1,
    'username' => 'admin',
    'first_name' => 'System',
    'middle_name' => '',
    'last_name' => 'Administrator',
    'email' => 'admin@sinalhan.gov.ph',
    'contact_no' => '0917-000-0000',
    'role' => 'admin',
    'employee_id' => 'EMP-001',
    'department' => 'Executive Office',
    'job_title' => 'Chief Administrator',
    'status' => 'active',
    'created_at' => '2026-01-01 08:00:00',
    'last_login_at' => '2026-09-09 08:30:00'
];

ob_start();
try {
    // Render topbar
    require __DIR__ . '/../app/Views/layout/topbar.php';
    $topbarOutput = ob_get_clean();
    echo "[TEST 1] Topbar view rendered successfully (" . strlen($topbarOutput) . " bytes)\n";
    assert(strpos($topbarOutput, 'System Administrator') !== false, "Topbar should contain user fullname");
    assert(strpos($topbarOutput, 'Admin') !== false, "Topbar should contain role Admin");
    assert(strpos($topbarOutput, 'user-avatar-circle') !== false, "Topbar should contain avatar circle");
    assert(strpos($topbarOutput, 'My Profile') !== false, "Topbar dropdown should contain My Profile link");
    assert(strpos($topbarOutput, 'Password Settings') !== false, "Topbar dropdown should contain Password Settings link");
    assert(strpos($topbarOutput, 'Logout') !== false, "Topbar dropdown should contain Logout");
    assert(strpos($topbarOutput, 'data-confirm') !== false, "Logout should have data-confirm trigger");
    echo "  -> PASS: All topbar dropdown components verified.\n\n";
} catch (\Throwable $e) {
    ob_end_clean();
    echo "FAIL in topbar: " . $e->getMessage() . "\n";
    exit(1);
}

// Test profile view rendering
ob_start();
try {
    $disable_layout = true; // prevent nested header/sidebar for isolated component check
    $title = 'My Profile';
    require __DIR__ . '/../app/Views/profile/index.php';
    $profileOutput = ob_get_clean();
    echo "[TEST 2] Profile view rendered successfully (" . strlen($profileOutput) . " bytes)\n";
    assert(strpos($profileOutput, 'Personal &amp; Contact Information') !== false || strpos($profileOutput, 'Personal & Contact Information') !== false, "Profile view should contain personal info section");
    assert(strpos($profileOutput, 'Security &amp; Password Settings') !== false || strpos($profileOutput, 'Security & Password Settings') !== false, "Profile view should contain security info section");
    assert(strpos($profileOutput, 'password-settings') !== false, "Profile view should contain id=password-settings anchor");
    assert(strpos($profileOutput, 'btn-toggle-password') !== false, "Profile view should contain password visibility toggle");
    assert(strpos($profileOutput, 'admin@sinalhan.gov.ph') !== false, "Profile view should render user email");
    echo "  -> PASS: All profile view elements rendered properly.\n\n";
} catch (\Throwable $e) {
    ob_end_clean();
    echo "FAIL in profile view: " . $e->getMessage() . "\n";
    exit(1);
}

echo "=== ALL VIEW RENDERING TESTS PASSED! ===\n";
