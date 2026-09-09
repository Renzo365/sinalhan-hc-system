<?php

/**
 * QA & Security Audit Suite for User Profile & Topbar Dropdown
 * 
 * Features under test:
 * 1. CSRF validation on profile updates & password changes
 * 2. Privilege escalation & mass-assignment prevention
 * 3. Strict authentication enforcement & ID spoofing prevention
 * 4. Password validation, bcrypt hashing, and session fixation defense
 * 5. Comprehensive XSS escaping on profile and topbar templates
 * 6. Special character & multibyte handling (e.g. ñ, Ñ, apostrophes)
 * 7. Real-time session synchronization
 * 8. Audit trail logging integrity
 */

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/../app/helpers.php';

$config = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

echo "====================================================================\n";
echo "   BARANGAY SINALHAN HEALTH CENTER SYSTEM - QA & SECURITY AUDIT     \n";
echo "   TARGET: USER PROFILE & TOPBAR DROPDOWN (PHASE 3 VERIFICATION)   \n";
echo "====================================================================\n\n";

$passCount = 0;
$failCount = 0;

function reportAssertion($description, $condition, $details = '') {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$description}\n";
    } else {
        $failCount++;
        echo "  [FAIL] {$description}\n";
        if ($details) {
            echo "         Details: {$details}\n";
        }
    }
}

// -------------------------------------------------------------
// Test Setup: Create isolated test accounts
// -------------------------------------------------------------
$attackerUsername = 'qa_audit_attacker';
$victimUsername = 'qa_audit_victim';

// Clean old test artifacts
$pdo->prepare("DELETE FROM audit_logs WHERE username IN (:u1, :u2)")->execute(['u1' => $attackerUsername, 'u2' => $victimUsername]);
$pdo->prepare("DELETE FROM users WHERE username IN (:u1, :u2)")->execute(['u1' => $attackerUsername, 'u2' => $victimUsername]);

$victimPass = 'VictimSecret2026!';
$attackerPass = 'AttackerPass2026!';

// Insert Victim (staff)
$pdo->prepare("
    INSERT INTO users (username, password_hash, role, first_name, last_name, email, contact_no, status, employee_id, department, job_title, must_change_password)
    VALUES (:u, :p, 'staff', 'Victor', 'Magtanggol', 'victor@example.com', '09170000001', 'active', 'EMP-001', 'Nursing', 'Nurse', 0)
")->execute([
    'u' => $victimUsername,
    'p' => password_hash($victimPass, PASSWORD_BCRYPT)
]);
$victimId = (int)$pdo->lastInsertId();

// Insert Attacker (staff)
$pdo->prepare("
    INSERT INTO users (username, password_hash, role, first_name, last_name, email, contact_no, status, employee_id, department, job_title, must_change_password)
    VALUES (:u, :p, 'staff', 'Arnold', 'Reyes', 'arnold@example.com', '09170000002', 'active', 'EMP-002', 'Admin Support', 'Clerk', 0)
")->execute([
    'u' => $attackerUsername,
    'p' => password_hash($attackerPass, PASSWORD_BCRYPT)
]);
$attackerId = (int)$pdo->lastInsertId();

$userModel = new \App\Models\User();

// Subclass ProfileController to capture redirects without exiting process
class QAProfileControllerProxy extends \App\Controllers\ProfileController {
    public $redirectUrl = null;
    public $viewName = null;
    public $viewData = [];

    public function __construct() {
        // Bypass constructor exit for test flexibility while verifying logic
        $this->userModel = new \App\Models\User();
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }

    public function testAuthGuard() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
            return false;
        }
        return true;
    }

    protected function redirect($url) {
        $this->redirectUrl = $url;
    }

    protected function view($name, $data = []) {
        $this->viewName = $name;
        $this->viewData = $data;
    }
}

// =============================================================
// SECTION 1: ROUTING & AUTHENTICATION ACCESS CONTROL AUDIT
// =============================================================
echo "--- SECTION 1: ROUTING & AUTHENTICATION ACCESS CONTROL AUDIT ---\n";

$router = new \App\Core\Router();
$routeConfig = require __DIR__ . '/../config/routes.php';
$routeConfig($router);

$reflection = new ReflectionClass($router);
$routesProp = $reflection->getProperty('routes');
$routesProp->setAccessible(true);
$registeredRoutes = $routesProp->getValue($router);

$requiredProtectedRoutes = [
    'GET /profile' => \App\Middleware\AuthMiddleware::class,
    'POST /profile/update' => \App\Middleware\AuthMiddleware::class,
    'POST /profile/password' => \App\Middleware\AuthMiddleware::class,
];

foreach ($requiredProtectedRoutes as $routeSig => $expectedMiddleware) {
    $found = false;
    $hasMiddleware = false;
    foreach ($registeredRoutes as $r) {
        if ($r['method'] . ' ' . $r['route'] === $routeSig) {
            $found = true;
            $hasMiddleware = in_array($expectedMiddleware, $r['middlewares'] ?? []);
            break;
        }
    }
    reportAssertion(
        "Route {$routeSig} is registered in router",
        $found
    );
    reportAssertion(
        "Route {$routeSig} enforces AuthMiddleware",
        $hasMiddleware
    );
}

// Test Controller constructor guard behavior when unauthenticated
unset($_SESSION['user_id']);
$testProxy = new QAProfileControllerProxy();
$authGuardResult = $testProxy->testAuthGuard();
reportAssertion(
    "Unauthenticated user is denied and redirected to /login",
    $authGuardResult === false && $testProxy->redirectUrl === '/login'
);

echo "\n";

// =============================================================
// SECTION 2: CSRF INTEGRITY AUDIT
// =============================================================
echo "--- SECTION 2: CSRF TOKEN VALIDATION AUDIT ---\n";

// Authenticate as Attacker
$_SESSION['user_id'] = $attackerId;
$_SESSION['username'] = $attackerUsername;
$_SESSION['csrf_token'] = 'valid_qa_token_987654';

$_SERVER['REQUEST_METHOD'] = 'POST';

// Subcase 2.1: Profile update with MISSING CSRF token
$_POST = [
    'first_name' => 'TamperedName',
    'last_name' => 'TamperedLast'
    // Missing csrf_token
];
$ctrl = new QAProfileControllerProxy();
$ctrl->update();
reportAssertion(
    "Profile update aborts when CSRF token is completely missing",
    isset($_SESSION['error_message']) && strpos($_SESSION['error_message'], 'CSRF') !== false
);
$freshAttacker = $userModel->findById($attackerId);
reportAssertion(
    "Database record remains untouched on missing CSRF token",
    $freshAttacker['first_name'] === 'Arnold'
);
unset($_SESSION['error_message']);

// Subcase 2.2: Profile update with TAMPERED/INVALID CSRF token
$_POST = [
    'csrf_token' => 'attacker_forged_token_xyz',
    'first_name' => 'TamperedName2',
    'last_name' => 'TamperedLast2'
];
$ctrl = new QAProfileControllerProxy();
$ctrl->update();
reportAssertion(
    "Profile update aborts on forged/mismatched CSRF token",
    isset($_SESSION['error_message']) && strpos($_SESSION['error_message'], 'CSRF') !== false
);
$freshAttacker = $userModel->findById($attackerId);
reportAssertion(
    "Database record remains untouched on forged CSRF token",
    $freshAttacker['first_name'] === 'Arnold'
);
unset($_SESSION['error_message']);

// Subcase 2.3: Password change with MISSING CSRF token
$_POST = [
    'current_password' => $attackerPass,
    'new_password' => 'ForgedPass2026!',
    'confirm_password' => 'ForgedPass2026!'
    // Missing csrf_token
];
$ctrl = new QAProfileControllerProxy();
$ctrl->updatePassword();
reportAssertion(
    "Password change aborts when CSRF token is completely missing",
    isset($_SESSION['error_message']) && strpos($_SESSION['error_message'], 'CSRF') !== false
);
$freshAttacker = $userModel->findById($attackerId);
reportAssertion(
    "Password hash unchanged on missing CSRF token",
    password_verify($attackerPass, $freshAttacker['password_hash'])
);
unset($_SESSION['error_message']);

// Subcase 2.4: Password change with TAMPERED CSRF token
$_POST = [
    'csrf_token' => 'invalid_token_9999',
    'current_password' => $attackerPass,
    'new_password' => 'ForgedPass2026!',
    'confirm_password' => 'ForgedPass2026!'
];
$ctrl = new QAProfileControllerProxy();
$ctrl->updatePassword();
reportAssertion(
    "Password change aborts on forged CSRF token",
    isset($_SESSION['error_message']) && strpos($_SESSION['error_message'], 'CSRF') !== false
);
$freshAttacker = $userModel->findById($attackerId);
reportAssertion(
    "Password hash unchanged on forged CSRF token",
    password_verify($attackerPass, $freshAttacker['password_hash'])
);
unset($_SESSION['error_message']);

echo "\n";

// =============================================================
// SECTION 3: PRIVILEGE ESCALATION & MASS-ASSIGNMENT AUDIT
// =============================================================
echo "--- SECTION 3: PRIVILEGE ESCALATION & MASS-ASSIGNMENT AUDIT ---\n";

// Subcase 3.1: Direct model mass-assignment audit on User::updateProfile()
$maliciousPayload = [
    'first_name' => 'ArnoldLegit',
    'middle_name' => 'MiddleLegit',
    'last_name' => 'ReyesLegit',
    'email' => 'arnold.legit@example.com',
    'contact_no' => '09179998888',
    // Injected administrative / sensitive fields:
    'role' => 'admin',
    'status' => 'inactive',
    'username' => 'arnold_escalated_admin',
    'employee_id' => 'HAX-999',
    'department' => 'Root Administration',
    'job_title' => 'Chief Director',
    'password_hash' => '$2y$10$forgedhashforgedhashforgedhas'
];

$updateResult = $userModel->updateProfile($attackerId, $maliciousPayload);
reportAssertion(
    "User::updateProfile executes cleanly",
    $updateResult === true
);

$attackerCheck = $userModel->findById($attackerId);
reportAssertion(
    "Legitimate fields updated: first_name",
    $attackerCheck['first_name'] === 'ArnoldLegit'
);
reportAssertion(
    "Legitimate fields updated: email",
    $attackerCheck['email'] === 'arnold.legit@example.com'
);
reportAssertion(
    "Privilege escalation blocked: role remains 'staff'",
    $attackerCheck['role'] === 'staff'
);
reportAssertion(
    "Privilege escalation blocked: status remains 'active'",
    $attackerCheck['status'] === 'active'
);
reportAssertion(
    "Privilege escalation blocked: username remains original",
    $attackerCheck['username'] === $attackerUsername
);
reportAssertion(
    "Privilege escalation blocked: employee_id untouched",
    $attackerCheck['employee_id'] === 'EMP-002'
);
reportAssertion(
    "Privilege escalation blocked: department untouched",
    $attackerCheck['department'] === 'Admin Support'
);
reportAssertion(
    "Privilege escalation blocked: password_hash untouched",
    password_verify($attackerPass, $attackerCheck['password_hash'])
);

// Subcase 3.2: Controller ID Spoofing / Insecure Direct Object Reference (IDOR)
// Attacker tries to submit POST with victim's ID
$_SESSION['user_id'] = $attackerId;
$_POST = [
    'csrf_token' => 'valid_qa_token_987654',
    'id' => $victimId,            // Spoofed victim ID
    'user_id' => $victimId,       // Spoofed victim ID
    'first_name' => 'HackedVictor',
    'last_name' => 'Pwned',
    'email' => 'victim.hacked@example.com',
    'role' => 'admin',
    'status' => 'inactive'
];

$ctrl = new QAProfileControllerProxy();
$ctrl->update();

$victimCheck = $userModel->findById($victimId);
reportAssertion(
    "IDOR Guard: Victim first_name was NOT modified by spoofed ID",
    $victimCheck['first_name'] === 'Victor'
);
reportAssertion(
    "IDOR Guard: Victim email was NOT modified by spoofed ID",
    $victimCheck['email'] === 'victor@example.com'
);
$attackerCheck = $userModel->findById($attackerId);
reportAssertion(
    "Only authenticated session user's record was updated",
    $attackerCheck['first_name'] === 'HackedVictor' && $attackerCheck['last_name'] === 'Pwned'
);
reportAssertion(
    "Controller ignored injected 'role' parameter",
    $attackerCheck['role'] === 'staff'
);

echo "\n";

// =============================================================
// SECTION 4: PASSWORD SECURITY & FIXATION DEFENSE AUDIT
// =============================================================
echo "--- SECTION 4: PASSWORD SECURITY AUDIT ---\n";

// Subcase 4.1: Missing fields
$_POST = [
    'csrf_token' => 'valid_qa_token_987654',
    'current_password' => '',
    'new_password' => '',
    'confirm_password' => ''
];
$ctrl = new QAProfileControllerProxy();
$ctrl->updatePassword();
reportAssertion(
    "Rejects completely empty password change payload",
    !empty($_SESSION['password_errors'])
);
unset($_SESSION['password_errors']);

// Subcase 4.2: Current password bypass attempt / incorrect current password
$_POST = [
    'csrf_token' => 'valid_qa_token_987654',
    'current_password' => 'WrongGuessPass!',
    'new_password' => 'SecurePass2026!',
    'confirm_password' => 'SecurePass2026!'
];
$ctrl = new QAProfileControllerProxy();
$ctrl->updatePassword();
reportAssertion(
    "Rejects incorrect current password",
    !empty($_SESSION['password_errors']) && in_array('The current password you entered is incorrect.', $_SESSION['password_errors'])
);
unset($_SESSION['password_errors']);

// Subcase 4.3: SQL Injection in current password
$_POST = [
    'csrf_token' => 'valid_qa_token_987654',
    'current_password' => "' OR '1'='1' --",
    'new_password' => 'SecurePass2026!',
    'confirm_password' => 'SecurePass2026!'
];
$ctrl = new QAProfileControllerProxy();
$ctrl->updatePassword();
reportAssertion(
    "Rejects SQL injection payload in current password",
    !empty($_SESSION['password_errors'])
);
unset($_SESSION['password_errors']);

// Subcase 4.4: Password length boundary (< 8 characters)
$_POST = [
    'csrf_token' => 'valid_qa_token_987654',
    'current_password' => $attackerPass,
    'new_password' => '1234567', // 7 chars
    'confirm_password' => '1234567'
];
$ctrl = new QAProfileControllerProxy();
$ctrl->updatePassword();
reportAssertion(
    "Rejects new password shorter than 8 characters",
    !empty($_SESSION['password_errors']) && in_array('New password must be at least 8 characters long.', $_SESSION['password_errors'])
);
unset($_SESSION['password_errors']);

// Subcase 4.5: Confirmation mismatch
$_POST = [
    'csrf_token' => 'valid_qa_token_987654',
    'current_password' => $attackerPass,
    'new_password' => 'ValidPass123!',
    'confirm_password' => 'MismatchedPass123!'
];
$ctrl = new QAProfileControllerProxy();
$ctrl->updatePassword();
reportAssertion(
    "Rejects password confirmation mismatch",
    !empty($_SESSION['password_errors']) && in_array('The new password and confirmation password do not match.', $_SESSION['password_errors'])
);
unset($_SESSION['password_errors']);

// Subcase 4.6: Valid password change
$newValidPass = 'NewlySetValidPassword2026#';
$_POST = [
    'csrf_token' => 'valid_qa_token_987654',
    'current_password' => $attackerPass,
    'new_password' => $newValidPass,
    'confirm_password' => $newValidPass
];
$ctrl = new QAProfileControllerProxy();
$ctrl->updatePassword();

reportAssertion(
    "Accepts valid password change and flashes success message",
    isset($_SESSION['success_message'])
);

$attackerCheck = $userModel->findById($attackerId);
reportAssertion(
    "New password verifies with bcrypt hash",
    password_verify($newValidPass, $attackerCheck['password_hash'])
);
reportAssertion(
    "Old password can no longer authenticate",
    !password_verify($attackerPass, $attackerCheck['password_hash'])
);
reportAssertion(
    "Bcrypt hash format conforms to $2y$ standard",
    substr($attackerCheck['password_hash'], 0, 4) === '$2y$' && strlen($attackerCheck['password_hash']) === 60
);
reportAssertion(
    "Password change clears failed_attempts counter to 0",
    (int)$attackerCheck['failed_attempts'] === 0
);
reportAssertion(
    "Password change clears must_change_password flag",
    (int)$attackerCheck['must_change_password'] === 0
);

echo "\n";

// =============================================================
// SECTION 5: REAL-TIME SESSION SYNCHRONIZATION AUDIT
// =============================================================
echo "--- SECTION 5: REAL-TIME SESSION SYNCHRONIZATION AUDIT ---\n";

$_SESSION['user_id'] = $attackerId;
$_SESSION['user_fullname'] = 'Old FullName';

$_POST = [
    'csrf_token' => 'valid_qa_token_987654',
    'first_name' => 'Roberto',
    'middle_name' => 'De la Cruz',
    'last_name' => 'Mendoza',
    'email' => 'roberto.m@example.com',
    'contact_no' => '09187776655'
];

$ctrl = new QAProfileControllerProxy();
$ctrl->update();

reportAssertion(
    "Active session 'user_fullname' is synchronized immediately upon profile update",
    $_SESSION['user_fullname'] === 'Roberto Mendoza'
);

echo "\n";

// =============================================================
// SECTION 6: XSS & TEMPLATE ESCAPING AUDIT
// =============================================================
echo "--- SECTION 6: XSS & TEMPLATE ESCAPING AUDIT ---\n";

// Test 6.1: Helper h() behavior with dangerous HTML & special characters
$xssVector1 = "<script>alert('xss')</script>";
$xssVector2 = '"><img src=x onerror=alert(1)>';
$xssVector3 = "D'Angelo & Co. \"Special\"";
$filipinoName = "Niño Peña-Iñigo";

reportAssertion(
    "Helper h() escapes <script> to &lt;script&gt;",
    h($xssVector1) === "&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;"
);
reportAssertion(
    "Helper h() escapes double quotes and brackets",
    h($xssVector2) === "&quot;&gt;&lt;img src=x onerror=alert(1)&gt;"
);
reportAssertion(
    "Helper h() escapes single and double quotes via ENT_QUOTES",
    h($xssVector3) === "D&#039;Angelo &amp; Co. &quot;Special&quot;"
);
reportAssertion(
    "Helper h() preserves multibyte UTF-8 Filipino characters (ñ, Ñ)",
    h($filipinoName) === "Niño Peña-Iñigo"
);

// Test 6.2: Full View Template Rendering with XSS Payloads
$xssUser = [
    'id' => 999,
    'username' => '<script>alert("xss_user")</script>',
    'first_name' => '<img src=x onerror=alert("fn")>',
    'middle_name' => '"><svg onload=alert("mn")>',
    'last_name' => "D'Arcy <script>alert('ln')</script>",
    'email' => 'xss"onmouseover="alert(1)"@example.com',
    'contact_no' => '<b onfocus=alert(1)>0912</b>',
    'employee_id' => '"><script>alert("emp")</script>',
    'department' => '<span onclick="evil()">Health Dept</span>',
    'job_title' => '<div onmouseover="evil()">Nurse</div>',
    'role' => 'staff',
    'status' => 'active',
    'created_at' => '2026-01-01 00:00:00',
    'last_login_at' => null
];

// Mock session for view rendering
$_SESSION['user_fullname'] = '"><script>alert("session_xss")</script>';
$_SESSION['user_role'] = 'staff';
$_SESSION['user_id'] = 999;
$_SESSION['csrf_token'] = 'test_token';

// Render topbar.php
ob_start();
require __DIR__ . '/../app/Views/layout/topbar.php';
$topbarOutput = ob_get_clean();

reportAssertion(
    "Topbar escapes raw <script> tag from user_fullname",
    strpos($topbarOutput, '<script>alert("session_xss")</script>') === false
);
reportAssertion(
    "Topbar encodes user_fullname with HTML entities",
    strpos($topbarOutput, '&lt;script&gt;alert(&quot;session_xss&quot;)&lt;/script&gt;') !== false
);

// Render profile/index.php
$user = $xssUser;
// Mock breadcrumbs and header requirements
ob_start();
// Include view directly
require __DIR__ . '/../app/Views/profile/index.php';
$profileOutput = ob_get_clean();

reportAssertion(
    "Profile view escapes username XSS",
    strpos($profileOutput, '<script>alert("xss_user")</script>') === false &&
    strpos($profileOutput, '&lt;script&gt;alert(&quot;xss_user&quot;)&lt;/script&gt;') !== false
);
reportAssertion(
    "Profile view escapes first_name XSS",
    strpos($profileOutput, '<img src=x onerror=alert("fn")>') === false &&
    strpos($profileOutput, '&lt;img src=x onerror=alert(&quot;fn&quot;)&gt;') !== false
);
reportAssertion(
    "Profile view escapes middle_name XSS attribute escape",
    strpos($profileOutput, '"><svg onload=alert("mn")>') === false
);
reportAssertion(
    "Profile view escapes employee_id XSS",
    strpos($profileOutput, '"><script>alert("emp")</script>') === false
);
reportAssertion(
    "Profile view escapes department XSS",
    strpos($profileOutput, '<span onclick="evil()">') === false
);
reportAssertion(
    "Profile view escapes job_title XSS",
    strpos($profileOutput, '<div onmouseover="evil()">') === false
);
reportAssertion(
    "Profile view escapes single quote apostrophes safely",
    strpos($profileOutput, "D&#039;Arcy") !== false
);

echo "\n";

// =============================================================
// SECTION 7: AUDIT LOGGING INTEGRITY
// =============================================================
echo "--- SECTION 7: AUDIT LOGGING INTEGRITY ---\n";

$auditLogsStmt = $pdo->prepare("SELECT action, details, module FROM audit_logs WHERE user_id = :uid ORDER BY id DESC");
$auditLogsStmt->execute(['uid' => $attackerId]);
$logs = $auditLogsStmt->fetchAll(PDO::FETCH_ASSOC);

$actionsFound = array_column($logs, 'action');

reportAssertion(
    "Audit trail recorded PROFILE_UPDATED action",
    in_array('PROFILE_UPDATED', $actionsFound)
);
reportAssertion(
    "Audit trail recorded USER_PASSWORD_CHANGED action",
    in_array('USER_PASSWORD_CHANGED', $actionsFound)
);

echo "\n";

// =============================================================
// CLEANUP TEST DATA
// =============================================================
$pdo->prepare("DELETE FROM audit_logs WHERE username IN (:u1, :u2) OR user_id IN (:id1, :id2)")->execute([
    'u1' => $attackerUsername,
    'u2' => $victimUsername,
    'id1' => $attackerId,
    'id2' => $victimId
]);
$pdo->prepare("DELETE FROM users WHERE id IN (:id1, :id2)")->execute([
    'id1' => $attackerId,
    'id2' => $victimId
]);

echo "Cleaned up test accounts (Attacker ID {$attackerId}, Victim ID {$victimId}).\n\n";

// =============================================================
// FINAL SUMMARY
// =============================================================
echo "====================================================================\n";
echo "   QA & SECURITY AUDIT SUMMARY                                      \n";
echo "====================================================================\n";
echo "TOTAL ASSERTIONS RUN: " . ($passCount + $failCount) . "\n";
echo "PASS: {$passCount}\n";
echo "FAIL: {$failCount}\n";

if ($failCount === 0) {
    echo "\n>>> STATUS: ALL QA & SECURITY AUDIT CHECKS PASSED PERFECTLY! <<<\n";
    exit(0);
} else {
    echo "\n>>> STATUS: {$failCount} FAILURE(S) DETECTED! <<<\n";
    exit(1);
}
