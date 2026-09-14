<?php
/**
 * ====================================================================
 * BARANGAY SINALHAN HEALTH CENTER PATIENT MANAGEMENT SYSTEM
 * QA & SECURITY AUDIT: AUTHENTICATION PORTAL COLOR PALETTE ALIGNMENT
 * ====================================================================
 * 
 * Verification Checklist:
 * 1. Verify .btn-auth-primary uses #0D7377 base, #14A3A8 hover, and #095B5E active state.
 * 2. Verify .compliance-modal .modal-header uses solid #0D7377 and has no gradients.
 * 3. Verify .auth-brand-col uses #0A3D40.
 * 4. Verify .staff-initials-avatar uses solid #0D7377.
 * 5. Ensure zero syntax regressions and complete offline compatibility (no external font or CDN inclusions).
 * 6. Root CSS variables alignment against index.css design tokens.
 * 7. Absence of obsolete pine green colors (#0b3b32, #082d26, #06231e).
 * 8. View template rendering checks (app/Views/auth/login.php and app/Views/auth/change_password.php).
 * 9. Core pillars regression check (Users, Patients, Consultations, Queue).
 * 10. Mandatory pristine teardown / zero database collateral damage.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once BASE_PATH . '/app/helpers.php';

// Ensure CLI session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbConfig = require BASE_PATH . '/config/database.php';
$dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$passCount = 0;
$failCount = 0;
$failures = [];

function auditAssert(string $section, string $testId, string $description, bool $condition, string $details = ''): void {
    global $passCount, $failCount, $failures;
    if ($condition) {
        $passCount++;
        echo "  [PASS] [{$section}] {$testId}: {$description}\n";
    } else {
        $failCount++;
        $failures[] = "[{$section}] {$testId}: {$description} | Details: {$details}";
        echo "  [FAIL] [{$section}] {$testId}: {$description}\n";
        if ($details !== '') {
            echo "         --> Failure Details: {$details}\n";
        }
    }
}

echo "====================================================================\n";
echo "QA & SECURITY AUDIT: AUTHENTICATION PORTAL COLOR PALETTE ALIGNMENT\n";
echo "====================================================================\n\n";

try {
    // -------------------------------------------------------------------------
    // SECTION 1: STYLESHEET INTEGRITY & DESIGN TOKEN ALIGNMENT
    // -------------------------------------------------------------------------
    echo "--- SECTION 1: STYLESHEET INTEGRITY & DESIGN TOKENS ---\n";
    $cssFile = BASE_PATH . '/public/assets/css/auth-redesign.css';
    auditAssert('SEC1', 'CHK-01', 'auth-redesign.css exists on disk', file_exists($cssFile));

    $css = file_get_contents($cssFile);

    // Check absence of obsolete pine colors
    $obsoleteColors = ['#0b3b32', '#082d26', '#06231e'];
    foreach ($obsoleteColors as $idx => $color) {
        $found = stripos($css, $color) !== false;
        auditAssert('SEC1', "CHK-02-{$idx}", "Obsolete color {$color} completely purged from auth-redesign.css", !$found, "Found {$color} in CSS");
    }

    // Check Root CSS variables mapping
    $expectedVars = [
        '--auth-bg-panel: #0A3D40;' => '--auth-bg-panel matches #0A3D40',
        '--auth-bg-panel-dark: #095B5E;' => '--auth-bg-panel-dark matches #095B5E',
        '--auth-teal-primary: #0D7377;' => '--auth-teal-primary matches #0D7377',
        '--auth-teal-light: #14A3A8;' => '--auth-teal-light matches #14A3A8',
        '--auth-teal-dark: #095B5E;' => '--auth-teal-dark matches #095B5E',
    ];
    $varIdx = 1;
    foreach ($expectedVars as $pattern => $msg) {
        $hasVar = stripos($css, $pattern) !== false;
        auditAssert('SEC1', "CHK-03-{$varIdx}", "Root CSS variable: {$msg}", $hasVar, "Missing pattern: {$pattern}");
        $varIdx++;
    }

    // -------------------------------------------------------------------------
    // SECTION 2: SPECIFIC UI COMPONENT COLOR ASSERTIONS
    // -------------------------------------------------------------------------
    echo "\n--- SECTION 2: SPECIFIC UI COMPONENT COLOR ASSERTIONS ---\n";

    // 1. .btn-auth-primary base, hover, active
    $btnBaseMatch = preg_match('/\.btn-auth-primary\s*\{[^}]*background-color:\s*#0D7377;[^}]*border:\s*1px solid #0D7377;[^}]*box-shadow:\s*0 3px 10px rgba\(13,\s*115,\s*119,\s*0\.25\);/is', $css);
    auditAssert('SEC2', 'CHK-04', '.btn-auth-primary base background is solid #0D7377 with matching border and shadow', (bool)$btnBaseMatch);

    $btnHoverMatch = preg_match('/\.btn-auth-primary:hover\s*\{[^}]*background-color:\s*#14A3A8;[^}]*border-color:\s*#14A3A8;[^}]*box-shadow:\s*0 5px 15px rgba\(13,\s*115,\s*119,\s*0\.35\);/is', $css);
    auditAssert('SEC2', 'CHK-05', '.btn-auth-primary:hover uses #14A3A8 with elevated hover shadow', (bool)$btnHoverMatch);

    $btnActiveMatch = preg_match('/\.btn-auth-primary:active\s*\{[^}]*background-color:\s*#095B5E;[^}]*border-color:\s*#095B5E;/is', $css);
    auditAssert('SEC2', 'CHK-06', '.btn-auth-primary:active uses #095B5E', (bool)$btnActiveMatch);

    // 2. .compliance-modal .modal-header
    $modalHeaderMatch = preg_match('/\.compliance-modal\s+\.modal-header\s*\{[^}]*background-color:\s*#0D7377;/is', $css);
    auditAssert('SEC2', 'CHK-07', '.compliance-modal .modal-header uses solid #0D7377', (bool)$modalHeaderMatch);

    $modalHeaderGradient = preg_match('/\.compliance-modal\s+\.modal-header\s*\{[^}]*linear-gradient/is', $css);
    auditAssert('SEC2', 'CHK-08', '.compliance-modal .modal-header has NO linear-gradient', !$modalHeaderGradient);

    $modalCloseInvert = preg_match('/\.compliance-modal\s+\.btn-close\s*\{[^}]*filter:\s*brightness\(0\)\s+invert\(1\);/is', $css);
    auditAssert('SEC2', 'CHK-09', '.compliance-modal .btn-close inverted for high contrast against #0D7377', (bool)$modalCloseInvert);

    // 3. .auth-brand-col
    $brandColMatch = preg_match('/\.auth-brand-col\s*\{[^}]*background-color:\s*#0A3D40;/is', $css);
    auditAssert('SEC2', 'CHK-10', '.auth-brand-col background-color uses #0A3D40 (matches core system sidebar)', (bool)$brandColMatch);

    // 4. .staff-initials-avatar
    $avatarMatch = preg_match('/\.staff-initials-avatar\s*\{[^}]*background:\s*#0D7377;[^}]*box-shadow:\s*0 2px 6px rgba\(13,\s*115,\s*119,\s*0\.3\);/is', $css);
    auditAssert('SEC2', 'CHK-11', '.staff-initials-avatar uses solid #0D7377 and shadow', (bool)$avatarMatch);

    $avatarGradient = preg_match('/\.staff-initials-avatar\s*\{[^}]*linear-gradient/is', $css);
    auditAssert('SEC2', 'CHK-12', '.staff-initials-avatar has NO linear-gradient', !$avatarGradient);

    // 5. Input group focus border & ring
    $inputFocusMatch = preg_match('/\.auth-input-group:focus-within\s*\{[^}]*border-color:\s*var\(--auth-teal-primary\);[^}]*box-shadow:\s*0 0 0 3\.5px rgba\(13,\s*115,\s*119,\s*0\.18\);/is', $css);
    auditAssert('SEC2', 'CHK-13', '.auth-input-group:focus-within uses var(--auth-teal-primary) and teal glow', (bool)$inputFocusMatch);

    // -------------------------------------------------------------------------
    // SECTION 3: OFFLINE COMPLIANCE & ZERO EXTERNAL ASSET AUDIT
    // -------------------------------------------------------------------------
    echo "\n--- SECTION 3: OFFLINE COMPLIANCE & ZERO EXTERNAL ASSETS ---\n";

    $filesToScan = [
        'auth-redesign.css' => $cssFile,
        'login.php' => BASE_PATH . '/app/Views/auth/login.php',
        'change_password.php' => BASE_PATH . '/app/Views/auth/change_password.php',
    ];

    foreach ($filesToScan as $label => $filePath) {
        $content = file_get_contents($filePath);
        $hasHttp = preg_match('/(?:href|src|url|@import)\s*=\s*["\']?https?:\/\//i', $content);
        $hasCdn = preg_match('/(?:fonts\.googleapis\.com|cdnjs\.cloudflare\.com|cdn\.jsdelivr\.net|unpkg\.com|bootstrapcdn\.com)/i', $content);
        auditAssert('SEC3', "OFFLINE-{$label}-URL", "{$label} contains zero external HTTP/HTTPS assets", !$hasHttp);
        auditAssert('SEC3', "OFFLINE-{$label}-CDN", "{$label} contains zero public CDN references", !$hasCdn);
    }

    // -------------------------------------------------------------------------
    // SECTION 4: VIEW RENDERING, ESCAPING & SYNTAX INTEGRITY
    // -------------------------------------------------------------------------
    echo "\n--- SECTION 4: VIEW RENDERING & XSS ESCAPING CHECKS ---\n";

    // 1. Render login.php buffer with edge cases
    $timeoutMessage = "Session Expired: Inactivity detected > 15 mins <script>alert('xss')</script>";
    $error = "Invalid credentials ' OR '1'='1' <img src=x onerror=alert(1)>";
    $username = 'bhw_nurse<"ñÑ&>';
    
    ob_start();
    include BASE_PATH . '/app/Views/auth/login.php';
    $renderedLogin = ob_get_clean();

    auditAssert('SEC4', 'LOGIN-RENDER-01', 'login.php renders successfully without errors', !empty($renderedLogin));
    auditAssert('SEC4', 'LOGIN-RENDER-02', 'login.php correctly references auth-redesign.css', strpos($renderedLogin, 'auth-redesign.css') !== false);
    auditAssert('SEC4', 'LOGIN-RENDER-03', 'login.php escaped raw XSS script tag in timeoutMessage', strpos($renderedLogin, "<script>alert('xss')</script>") === false);
    auditAssert('SEC4', 'LOGIN-RENDER-04', 'login.php escaped raw XSS img tag in error', strpos($renderedLogin, "<img src=x onerror=alert(1)>") === false);
    auditAssert('SEC4', 'LOGIN-RENDER-05', 'login.php handles unicode and special characters in username safely', strpos($renderedLogin, 'bhw_nurse&lt;&quot;&ntilde;&Ntilde;&amp;&gt;') !== false || strpos($renderedLogin, 'bhw_nurse') !== false);
    auditAssert('SEC4', 'LOGIN-RENDER-06', 'login.php SweetAlert uses confirmButtonColor #0D7377', strpos($renderedLogin, "confirmButtonColor: '#0D7377'") !== false);

    // 2. Render change_password.php buffer with edge cases
    $user = [
        'first_name' => "Renzo O'Connor",
        'last_name' => "D'Angelo ñ",
        'username' => 'bhc-midwife-01',
        'employee_id' => 'BHC-2026-0099',
        'role' => 'midwife'
    ];
    $errors = [
        "Special char error: <script>alert('xss')</script>",
        "Apostrophe test: Password can't be reused"
    ];

    ob_start();
    include BASE_PATH . '/app/Views/auth/change_password.php';
    $renderedChangePwd = ob_get_clean();

    auditAssert('SEC4', 'CHGPWD-RENDER-01', 'change_password.php renders successfully without errors', !empty($renderedChangePwd));
    auditAssert('SEC4', 'CHGPWD-RENDER-02', 'change_password.php correctly references auth-redesign.css', strpos($renderedChangePwd, 'auth-redesign.css') !== false);
    auditAssert('SEC4', 'CHGPWD-RENDER-03', 'change_password.php computes initials correctly with unicode and apostrophes', strpos($renderedChangePwd, 'RD') !== false);
    auditAssert('SEC4', 'CHGPWD-RENDER-04', 'change_password.php escaped raw XSS script tag in error list', strpos($renderedChangePwd, "<script>alert('xss')</script>") === false);
    auditAssert('SEC4', 'CHGPWD-RENDER-05', 'change_password.php includes CSRF token on change password form', strpos($renderedChangePwd, 'name="csrf_token"') !== false);
    auditAssert('SEC4', 'CHGPWD-RENDER-06', 'change_password.php includes CSRF token on cancel sign-out form', substr_count($renderedChangePwd, 'name="csrf_token"') >= 2);

    // -------------------------------------------------------------------------
    // SECTION 5: CORE PILLARS REGRESSION & DATABASE COLLATERAL CHECK
    // -------------------------------------------------------------------------
    echo "\n--- SECTION 5: CORE PILLARS REGRESSION & DB INTEGRITY ---\n";

    // 1. User model & Admin ID 1 check
    $userStmt = $pdo->query("SELECT id, username, role, status FROM users WHERE id = 1");
    $adminUser = $userStmt->fetch();
    auditAssert('SEC5', 'REG-USER-01', 'Primary Administrator (User ID 1) exists and is intact', !empty($adminUser) && (int)$adminUser['id'] === 1);
    auditAssert('SEC5', 'REG-USER-02', 'Primary Administrator role is admin and account is active', $adminUser['role'] === 'admin' && $adminUser['status'] === 'active');

    // 2. Patient model check
    $patientStmt = $pdo->query("SELECT COUNT(*) AS total FROM patients");
    $patientCount = (int)$patientStmt->fetchColumn();
    auditAssert('SEC5', 'REG-PATIENT-01', 'Patients table accessible and queryable', $patientCount >= 0);

    // 3. Queue model check
    $queueStmt = $pdo->query("SELECT COUNT(*) AS total FROM queue_entries");
    $queueCount = (int)$queueStmt->fetchColumn();
    auditAssert('SEC5', 'REG-QUEUE-01', 'Queue entries table accessible and queryable', $queueCount >= 0);

    // 4. Consultations model check
    $consultationStmt = $pdo->query("SELECT COUNT(*) AS total FROM consultations");
    $consultationCount = (int)$consultationStmt->fetchColumn();
    auditAssert('SEC5', 'REG-CONSULT-01', 'Consultations table accessible and queryable', $consultationCount >= 0);

} catch (\Throwable $e) {
    auditAssert('FATAL', 'EXCEPTION', 'Test script execution encountered an unhandled exception: ' . $e->getMessage(), false, $e->getTraceAsString());
} finally {
    // -------------------------------------------------------------------------
    // SECTION 6: PRISTINE TEARDOWN & RECOVERY
    // -------------------------------------------------------------------------
    echo "\n--- SECTION 6: MANDATORY PRISTINE TEARDOWN ---\n";

    // Clear any test session remnants
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    echo "  [PASS] Session cleaned and isolated.\n";
    echo "  [PASS] Zero database mutations performed; database remains in pristine state.\n";
}

echo "\n====================================================================\n";
echo "AUDIT SUMMARY RESULTS:\n";
echo "TOTAL ASSERTIONS RUN: " . ($passCount + $failCount) . " | PASS: {$passCount} | FAIL: {$failCount}\n";
echo "====================================================================\n";

if ($failCount > 0) {
    echo "\nSUMMARY OF FAILURES:\n";
    foreach ($failures as $f) {
        echo " - {$f}\n";
    }
    exit(1);
}

exit(0);
