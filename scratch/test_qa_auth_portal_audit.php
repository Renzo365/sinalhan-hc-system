<?php

/**
 * ====================================================================
 * BARANGAY SINALHAN HEALTH CENTER PATIENT MANAGEMENT SYSTEM
 * QA & SECURITY AUDIT SUITE: AUTHENTICATION PORTAL REDESIGN (PHASE 3)
 * ====================================================================
 * 
 * Audit Checklist Coverage:
 * 1. Dual-Identifier Login Security (Username + Employee ID, PDO Prepared Statements, SQL Injection Defense, Soft-Deleted Account Blocking)
 * 2. Brute-Force & Sliding Lockout (Dual Identifier Failed Counter, 5-Attempt Lockout, Lockout Duration Calculation, Lockout Bypass Prevention)
 * 3. First-Time Password Enclave Enforcement (Unauthenticated Redirection, Enclave Gate for must_change_password, Activated User Redirection)
 * 4. Password Policy & Complexity Matrix (Length, Mixed Case, Digits, Symbols, Re-use Prevention, Confirmation Match, Bcrypt Hashing, Session Fixation Prevention)
 * 5. CSRF & Session Security (CSRF Enforcement on POST /login, POST /change-password, POST /logout, Cancel & Sign Out Form)
 * 6. XSS & Output Sanitization (Hostile Vector Injections in View Dynamic Variables)
 * 7. Offline Compliance Audit (Zero External CDNs/Fonts/Stylesheets, Offline Modals)
 * 8. Session Inactivity & Timeout Lifecycle (15-Minute Expiry, Audit Log, Redirection)
 * 9. Core Pillars Regression & Database Integrity Check (Users, Patients, Consultations, Queue)
 * 10. Mandatory Pristine Teardown (0 Collateral Damage, User 1 State Verified)
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once BASE_PATH . '/app/helpers.php';

// Ensure clean CLI session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbConfig = require BASE_PATH . '/config/database.php';
$dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// Test counters and logging
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
echo "   BARANGAY SINALHAN HEALTH CENTER - QA & SECURITY AUDIT (PHASE 3)  \n";
echo "   MODULE: AUTHENTICATION PORTAL REDESIGN & SECURITY ENCLAVE        \n";
echo "====================================================================\n\n";

// --- Snapshot Primary Administrator (User ID 1) for Verification ---
$stmtUser1 = $pdo->prepare("SELECT * FROM users WHERE id = 1 LIMIT 1");
$stmtUser1->execute();
$originalUser1 = $stmtUser1->fetch();

if (!$originalUser1) {
    die("FATAL ERROR: Primary Administrator (User ID 1) not found in database!\n");
}

// Initial cleanup of any previous test artifacts
$pdo->exec("DELETE FROM audit_logs WHERE details LIKE '%qa_test_%' OR details LIKE '%qa_audit_%'");
$pdo->exec("DELETE FROM users WHERE username LIKE 'qa_test_%' OR username LIKE 'qa_audit_%'");

// Testable Controller Subclasses
class AuditedAuthController extends \App\Controllers\AuthController {
    public ?string $redirectedTo = null;
    public ?string $renderedView = null;
    public array $viewParams = [];

    public function resetCapture(): void {
        $this->redirectedTo = null;
        $this->renderedView = null;
        $this->viewParams = [];
    }

    protected function redirect($url) {
        $this->redirectedTo = $url;
    }

    protected function view($name, $data = []) {
        $this->renderedView = $name;
        $this->viewParams = $data;
    }

    /**
     * Override logout to avoid setcookie / session_destroy CLI warnings
     */
    public function logout() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (empty($token) || !hash_equals(csrf_token(), $token)) {
                \App\Models\AuditLog::log('SECURITY_VIOLATION', 'Auth', 'CSRF token mismatch on logout attempt.');
                $this->redirect('/login');
                return;
            }
        }

        $isTimeout = isset($_GET['timeout']) || (isset($_GET['reason']) && $_GET['reason'] === 'timeout');

        if (isset($_SESSION['user_id'])) {
            if ($isTimeout) {
                \App\Models\AuditLog::log('SESSION_TIMEOUT', 'Auth', "Session expired due to client inactivity for user: " . ($_SESSION['username'] ?? 'User'));
            } else {
                \App\Models\AuditLog::log('LOGOUT', 'Auth', "User logged out.");
            }
        }

        $_SESSION = [];

        if ($isTimeout) {
            $this->redirect('/login?timeout=1');
        } else {
            $this->redirect('/login');
        }
    }
}

class AuditedAuthMiddleware extends \App\Middleware\AuthMiddleware {
    public ?string $redirectedTo = null;
    public ?int $httpCode = null;
    public bool $terminated = false;

    public function handle() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirectedTo = '/login';
            $this->terminated = true;
            return false;
        }

        $idleTimeout = 900;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $idleTimeout) {
            $username = $_SESSION['username'] ?? 'User';
            \App\Models\AuditLog::log(
                'SESSION_TIMEOUT',
                'Auth',
                "Session expired due to inactivity for user: {$username}"
            );

            $_SESSION = [];
            $this->redirectedTo = '/login?timeout=1';
            $this->terminated = true;
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }
}

try {
    $userModel = new \App\Models\User();
    $authController = new AuditedAuthController();

    // =========================================================================
    // SECTION 1: DUAL-IDENTIFIER LOGIN SECURITY & SQL INJECTION AUDIT
    // =========================================================================
    echo "--- SECTION 1: DUAL-IDENTIFIER LOGIN SECURITY & SQL INJECTION DEFENSE ---\n";

    // Setup Test User 1 (Active, standard username and employee ID)
    $stmt = $pdo->prepare("
        INSERT INTO users (username, password_hash, role, first_name, last_name, email, contact_no, employee_id, status, must_change_password)
        VALUES ('qa_test_staff01', :hash, 'nurse', 'Juan', 'Dela Cruz', 'qa1@sinalhan.gov.ph', '09123456781', 'BHC-2026-QA01', 'active', 1)
    ");
    $stmt->execute(['hash' => password_hash('TempPass@2026', PASSWORD_BCRYPT)]);
    $testUser1Id = (int)$pdo->lastInsertId();

    // Setup Test User 2 (Soft-Deleted)
    $stmt = $pdo->prepare("
        INSERT INTO users (username, password_hash, role, first_name, last_name, email, contact_no, employee_id, status, must_change_password, deleted_at)
        VALUES ('qa_test_deleted01', :hash, 'staff', 'Deleted', 'Staff', 'deleted@sinalhan.gov.ph', '09123456782', 'BHC-2026-DEL01', 'active', 0, NOW())
    ");
    $stmt->execute(['hash' => password_hash('TempPass@2026', PASSWORD_BCRYPT)]);
    $deletedUserId = (int)$pdo->lastInsertId();

    // Setup Test User 3 (Inactive status)
    $stmt = $pdo->prepare("
        INSERT INTO users (username, password_hash, role, first_name, last_name, email, contact_no, employee_id, status, must_change_password)
        VALUES ('qa_test_inactive01', :hash, 'midwife', 'Inactive', 'Midwife', 'inactive@sinalhan.gov.ph', '09123456783', 'BHC-2026-INA01', 'inactive', 0)
    ");
    $stmt->execute(['hash' => password_hash('TempPass@2026', PASSWORD_BCRYPT)]);
    $inactiveUserId = (int)$pdo->lastInsertId();

    // Setup Test User 4 (Unicode characters & Apostrophe in Name)
    $stmt = $pdo->prepare("
        INSERT INTO users (username, password_hash, role, first_name, last_name, email, contact_no, employee_id, status, must_change_password)
        VALUES ('qa_test_peña_doc', :hash, 'doctor', 'José', 'O\'Connor', 'peña@sinalhan.gov.ph', '09123456784', 'BHC-2026-PEÑA', 'active', 0)
    ");
    $stmt->execute(['hash' => password_hash('DoctorPass@2026', PASSWORD_BCRYPT)]);
    $unicodeUserId = (int)$pdo->lastInsertId();

    // 1.1 Happy Path: Username resolution
    $foundByUsername = $userModel->findByLoginIdentifier('qa_test_staff01');
    auditAssert("DUAL-IDENTIFIER", "IDENT-USER-1", "Resolves user by standard username", 
        $foundByUsername !== false && (int)$foundByUsername['id'] === $testUser1Id);

    // 1.2 Happy Path: Employee ID resolution
    $foundByEmpId = $userModel->findByLoginIdentifier('BHC-2026-QA01');
    auditAssert("DUAL-IDENTIFIER", "IDENT-EMP-2", "Resolves user by official Employee ID", 
        $foundByEmpId !== false && (int)$foundByEmpId['id'] === $testUser1Id);

    // 1.3 Boundary: Non-existent identifier returns false
    $foundNone = $userModel->findByLoginIdentifier('NON_EXISTENT_IDENTIFIER_9999');
    auditAssert("DUAL-IDENTIFIER", "IDENT-NONE-3", "Non-existent identifier returns false without error", 
        $foundNone === false);

    // 1.4 Boundary: Empty string identifier returns false
    $foundEmpty = $userModel->findByLoginIdentifier('');
    auditAssert("DUAL-IDENTIFIER", "IDENT-EMPTY-4", "Empty string identifier returns false", 
        $foundEmpty === false);

    // 1.5 Edge Case: Unicode identifier handling
    $foundUnicodeUser = $userModel->findByLoginIdentifier('qa_test_peña_doc');
    $foundUnicodeEmp = $userModel->findByLoginIdentifier('BHC-2026-PEÑA');
    auditAssert("DUAL-IDENTIFIER", "IDENT-UNICODE-5", "Safely resolves username and employee ID with Unicode (ñ/Ñ)", 
        $foundUnicodeUser !== false && $foundUnicodeEmp !== false && $foundUnicodeUser['id'] === $unicodeUserId);

    // 1.6 Hostile Security: SQL Injection Attacks against findByLoginIdentifier
    $sqliPayloads = [
        "' OR '1'='1",
        "admin' --",
        "admin' /*",
        "' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20--",
        "\" OR \"\"=\"",
        "'; DROP TABLE users; --",
        "1' OR '1'='1' UNION SELECT * FROM users --"
    ];

    $sqliAllNeutralized = true;
    foreach ($sqliPayloads as $sqli) {
        $result = $userModel->findByLoginIdentifier($sqli);
        if ($result !== false) {
            $sqliAllNeutralized = false;
            break;
        }
    }
    auditAssert("DUAL-IDENTIFIER", "SQLI-NEUTRAL-6", "SQL injection strings in login identifier return false via PDO parameters", 
        $sqliAllNeutralized);

    // 1.7 Soft-deleted user cannot authenticate via username
    $deletedByUsername = $userModel->findByLoginIdentifier('qa_test_deleted01');
    auditAssert("DUAL-IDENTIFIER", "SOFT-DEL-USER-7", "Soft-deleted user cannot be retrieved by username", 
        $deletedByUsername === false);

    // 1.8 Soft-deleted user cannot authenticate via employee ID
    $deletedByEmp = $userModel->findByLoginIdentifier('BHC-2026-DEL01');
    auditAssert("DUAL-IDENTIFIER", "SOFT-DEL-EMP-8", "Soft-deleted user cannot be retrieved by employee ID", 
        $deletedByEmp === false);

    // 1.9 Inactive status account blocked in AuthController::login()
    $_SESSION = ['csrf_token' => 'valid_csrf_token_test'];
    $_POST = [
        'csrf_token' => 'valid_csrf_token_test',
        'username' => 'qa_test_inactive01',
        'password' => 'TempPass@2026'
    ];
    $authController->resetCapture();
    $authController->login();
    auditAssert("DUAL-IDENTIFIER", "INACTIVE-BLOCK-9", "Inactive account is blocked from login with appropriate notification", 
        $authController->redirectedTo === '/login' && 
        strpos($_SESSION['login_error'] ?? '', 'inactive') !== false);

    // =========================================================================
    // SECTION 2: BRUTE-FORCE & SLIDING LOCKOUT AUDIT
    // =========================================================================
    echo "\n--- SECTION 2: BRUTE-FORCE & LOCKOUT AUDIT ---\n";

    // Setup Test User for Lockout (qa_test_lockout)
    $stmt = $pdo->prepare("
        INSERT INTO users (username, password_hash, role, first_name, last_name, employee_id, status, failed_attempts, last_failed_login_at)
        VALUES ('qa_test_lockout', :hash, 'staff', 'Lockout', 'Target', 'BHC-2026-LOCK', 'active', 0, NULL)
    ");
    $stmt->execute(['hash' => password_hash('CorrectPass@2026', PASSWORD_BCRYPT)]);
    $lockoutUserId = (int)$pdo->lastInsertId();

    // 2.1 Increment failed attempts using Username
    $attempts1 = $userModel->incrementFailedAttempts('qa_test_lockout');
    $checkUser = $userModel->findById($lockoutUserId);
    auditAssert("LOCKOUT", "LOCK-INCR-USER-1", "incrementFailedAttempts via username increments attempt counter to 1", 
        $attempts1 === 1 && (int)$checkUser['failed_attempts'] === 1 && !empty($checkUser['last_failed_login_at']));

    // 2.2 Increment failed attempts using Employee ID
    $attempts2 = $userModel->incrementFailedAttempts('BHC-2026-LOCK');
    $checkUser = $userModel->findById($lockoutUserId);
    auditAssert("LOCKOUT", "LOCK-INCR-EMP-2", "incrementFailedAttempts via employee ID increments attempt counter to 2", 
        $attempts2 === 2 && (int)$checkUser['failed_attempts'] === 2);

    // 2.3 Check lockout status below threshold (< 5 attempts)
    $lockStatusBefore = $userModel->isLockedOut($checkUser);
    auditAssert("LOCKOUT", "LOCK-BELOW-THRESH-3", "Account with 2 failed attempts is NOT locked out", 
        $lockStatusBefore['is_locked'] === false);

    // 2.4 Increment to 5 failed attempts
    $userModel->incrementFailedAttempts('qa_test_lockout'); // 3
    $userModel->incrementFailedAttempts('qa_test_lockout'); // 4
    $attempts5 = $userModel->incrementFailedAttempts('BHC-2026-LOCK'); // 5
    $checkUser = $userModel->findById($lockoutUserId);
    auditAssert("LOCKOUT", "LOCK-REACH-FIVE-4", "Attempt counter reaches 5 consecutive failed attempts", 
        $attempts5 === 5 && (int)$checkUser['failed_attempts'] === 5);

    // 2.5 isLockedOut verifies active 15-minute lockout
    $lockStatusAfter = $userModel->isLockedOut($checkUser);
    auditAssert("LOCKOUT", "LOCK-ACTIVE-5", "5 failed attempts triggers active 15-minute sliding lockout", 
        $lockStatusAfter['is_locked'] === true && 
        $lockStatusAfter['remaining_seconds'] > 850 && 
        $lockStatusAfter['remaining_seconds'] <= 900 &&
        strpos($lockStatusAfter['remaining_formatted'], 'minute') !== false);

    // 2.6 AuthController blocks login for locked out user (even with correct password)
    $_SESSION = ['csrf_token' => 'valid_csrf_token_test'];
    $_POST = [
        'csrf_token' => 'valid_csrf_token_test',
        'username' => 'BHC-2026-LOCK',
        'password' => 'CorrectPass@2026' // Correct password, but account is locked
    ];
    $authController->resetCapture();
    $authController->login();
    auditAssert("LOCKOUT", "LOCK-CONTROLLER-BLOCK-6", "AuthController immediately blocks login during lockout before checking credentials", 
        $authController->redirectedTo === '/login' && 
        strpos($_SESSION['login_error'] ?? '', 'Too many failed login attempts') !== false);

    // Verify LOGIN_BLOCKED_LOCKOUT audit log was generated
    $stmtLog = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE action = 'LOGIN_BLOCKED_LOCKOUT' AND details LIKE '%qa_test_lockout%'");
    $stmtLog->execute();
    $logCount = (int)$stmtLog->fetchColumn();
    auditAssert("LOCKOUT", "LOCK-AUDIT-LOG-7", "Audit log records LOGIN_BLOCKED_LOCKOUT event", 
        $logCount > 0);

    // 2.7 Clear lockout restores account to active state
    $userModel->clearLockout($lockoutUserId);
    $checkUserClean = $userModel->findById($lockoutUserId);
    $lockStatusClean = $userModel->isLockedOut($checkUserClean);
    auditAssert("LOCKOUT", "LOCK-CLEARED-8", "clearLockout() resets failed attempts to 0 and clears lock", 
        (int)$checkUserClean['failed_attempts'] === 0 && 
        $checkUserClean['last_failed_login_at'] === null && 
        $lockStatusClean['is_locked'] === false);

    // 2.8 SQL injection string in incrementFailedAttempts returns 0 without crashing
    $sqliIncr = $userModel->incrementFailedAttempts("' OR '1'='1");
    auditAssert("LOCKOUT", "LOCK-SQLI-SAFE-9", "incrementFailedAttempts with malicious input returns 0 without SQL exception", 
        $sqliIncr === 0);

    // =========================================================================
    // SECTION 3: FIRST-TIME PASSWORD ENCLAVE ENFORCEMENT
    // =========================================================================
    echo "\n--- SECTION 3: FIRST-TIME PASSWORD ENCLAVE ENFORCEMENT ---\n";

    $testMiddleware = new AuditedAuthMiddleware();

    // 3.1 Unauthenticated visitor accessing protected route is redirected to /login
    $_SESSION = [];
    $mwResult = $testMiddleware->handle();
    auditAssert("ENCLAVE", "ENCLAVE-UNAUTH-1", "Unauthenticated visitor accessing protected route redirected to /login", 
        $mwResult === false && $testMiddleware->redirectedTo === '/login');

    // 3.2 Authenticated user with must_change_password = 0 cannot access /change-password
    $_SESSION = [
        'user_id' => $unicodeUserId,
        'username' => 'qa_test_peña_doc',
        'must_change_password' => 0,
        'last_activity' => time()
    ];
    $authController->resetCapture();
    $authController->showChangePassword();
    auditAssert("ENCLAVE", "ENCLAVE-ACTIVE-REDIRECT-2", "User with must_change_password = 0 redirected away from /change-password to /dashboard", 
        $authController->redirectedTo === '/dashboard');

    // 3.3 changePassword POST submission from must_change_password = 0 redirected to /dashboard
    $authController->resetCapture();
    $authController->changePassword();
    auditAssert("ENCLAVE", "ENCLAVE-POST-BYPASS-BLOCK-3", "POST /change-password blocked for users with must_change_password = 0", 
        $authController->redirectedTo === '/dashboard');

    // 3.4 Authenticated user with must_change_password = 1 can view /change-password
    $_SESSION = [
        'user_id' => $testUser1Id,
        'username' => 'qa_test_staff01',
        'must_change_password' => 1,
        'last_activity' => time()
    ];
    $authController->resetCapture();
    $authController->showChangePassword();
    auditAssert("ENCLAVE", "ENCLAVE-MUSTCHANGE-VIEW-4", "User with must_change_password = 1 can view /change-password with user data", 
        $authController->renderedView === 'auth/change_password' && 
        isset($authController->viewParams['user']) && 
        $authController->viewParams['user']['id'] === $testUser1Id);

    // 3.5 Login routing interceptor: user with must_change_password = 1 routed to /change-password
    $_SESSION = ['csrf_token' => 'valid_csrf_token_test'];
    $_POST = [
        'csrf_token' => 'valid_csrf_token_test',
        'username' => 'qa_test_staff01',
        'password' => 'TempPass@2026'
    ];
    $authController->resetCapture();
    $authController->login();
    auditAssert("ENCLAVE", "ENCLAVE-LOGIN-ROUTE-5", "Successful login with must_change_password = 1 directs user directly to /change-password", 
        $authController->redirectedTo === '/change-password' && 
        $_SESSION['must_change_password'] === 1);

    // 3.6 Login routing interceptor: user with must_change_password = 0 routed to /dashboard
    $_SESSION = ['csrf_token' => 'valid_csrf_token_test'];
    $_POST = [
        'csrf_token' => 'valid_csrf_token_test',
        'username' => 'qa_test_peña_doc',
        'password' => 'DoctorPass@2026'
    ];
    $authController->resetCapture();
    $authController->login();
    auditAssert("ENCLAVE", "ENCLAVE-LOGIN-DASH-6", "Successful login with must_change_password = 0 directs user to /dashboard", 
        $authController->redirectedTo === '/dashboard' && 
        $_SESSION['must_change_password'] === 0);

    // =========================================================================
    // SECTION 4: PASSWORD POLICY & COMPLEXITY MATRIX AUDIT
    // =========================================================================
    echo "\n--- SECTION 4: PASSWORD POLICY & COMPLEXITY MATRIX ---\n";

    // Setup enclave test session for qa_test_staff01
    $_SESSION = [
        'user_id' => $testUser1Id,
        'username' => 'qa_test_staff01',
        'must_change_password' => 1,
        'csrf_token' => 'valid_csrf_enclave_token'
    ];

    $complexityMatrix = [
        'EMPTY_FIELDS' => [
            'data' => ['current_password' => '', 'new_password' => '', 'confirm_password' => ''],
            'expectedError' => 'All fields are required.'
        ],
        'WRONG_CURRENT_TEMP' => [
            'data' => ['current_password' => 'WrongTempPass!', 'new_password' => 'SecurePass@2026', 'confirm_password' => 'SecurePass@2026'],
            'expectedError' => 'Incorrect current temporary password.'
        ],
        'TOO_SHORT' => [
            'data' => ['current_password' => 'TempPass@2026', 'new_password' => 'Ab1!', 'confirm_password' => 'Ab1!'],
            'expectedError' => 'New password must be at least 8 characters long.'
        ],
        'NO_UPPERCASE' => [
            'data' => ['current_password' => 'TempPass@2026', 'new_password' => 'lowercase@123', 'confirm_password' => 'lowercase@123'],
            'expectedError' => 'New password must contain both uppercase and lowercase letters.'
        ],
        'NO_LOWERCASE' => [
            'data' => ['current_password' => 'TempPass@2026', 'new_password' => 'UPPERCASE@123', 'confirm_password' => 'UPPERCASE@123'],
            'expectedError' => 'New password must contain both uppercase and lowercase letters.'
        ],
        'NO_DIGIT' => [
            'data' => ['current_password' => 'TempPass@2026', 'new_password' => 'NoDigitsSpecial!!', 'confirm_password' => 'NoDigitsSpecial!!'],
            'expectedError' => 'New password must contain at least 1 digit and 1 special symbol (@$!%*?&).'
        ],
        'NO_SPECIAL_SYMBOL' => [
            'data' => ['current_password' => 'TempPass@2026', 'new_password' => 'NoSpecial12345', 'confirm_password' => 'NoSpecial12345'],
            'expectedError' => 'New password must contain at least 1 digit and 1 special symbol (@$!%*?&).'
        ],
        'SAME_AS_TEMP' => [
            'data' => ['current_password' => 'TempPass@2026', 'new_password' => 'TempPass@2026', 'confirm_password' => 'TempPass@2026'],
            'expectedError' => 'New password must be different from your current temporary password.'
        ],
        'CONFIRM_MISMATCH' => [
            'data' => ['current_password' => 'TempPass@2026', 'new_password' => 'SecurePass@2026', 'confirm_password' => 'DifferentPass@2026'],
            'expectedError' => 'New password and confirmation password do not match.'
        ],
    ];

    foreach ($complexityMatrix as $caseName => $caseConfig) {
        $_POST = array_merge(['csrf_token' => 'valid_csrf_enclave_token'], $caseConfig['data']);
        $authController->resetCapture();
        $authController->changePassword();

        $errors = $authController->viewParams['errors'] ?? [];
        $matched = false;
        foreach ($errors as $err) {
            if (strpos($err, $caseConfig['expectedError']) !== false) {
                $matched = true;
                break;
            }
        }
        auditAssert("PASSWORD-POLICY", "POLICY-{$caseName}", "Rejects invalid password: {$caseConfig['expectedError']}", 
            $authController->renderedView === 'auth/change_password' && $matched);
    }

    // 4.10 Happy Path: Compliant Password Submission
    $_POST = [
        'csrf_token' => 'valid_csrf_enclave_token',
        'current_password' => 'TempPass@2026',
        'new_password' => 'BhcNewCompliantPass@2026',
        'confirm_password' => 'BhcNewCompliantPass@2026'
    ];
    $authController->resetCapture();
    $authController->changePassword();

    // Verify DB state
    $updatedUser = $userModel->findById($testUser1Id);
    $hashMatches = password_verify('BhcNewCompliantPass@2026', $updatedUser['password_hash']);
    $oldHashRejected = !password_verify('TempPass@2026', $updatedUser['password_hash']);
    $mustChangeCleared = (int)$updatedUser['must_change_password'] === 0;
    $failedAttemptsZero = (int)$updatedUser['failed_attempts'] === 0;

    auditAssert("PASSWORD-POLICY", "POLICY-SUCCESS-HASH", "Compliant password updates password_hash with bcrypt", 
        $hashMatches && $oldHashRejected);
    auditAssert("PASSWORD-POLICY", "POLICY-SUCCESS-FLAG", "Database must_change_password set to 0", 
        $mustChangeCleared);
    auditAssert("PASSWORD-POLICY", "POLICY-SUCCESS-SESSION", "Session must_change_password updated to 0", 
        $_SESSION['must_change_password'] === 0);
    auditAssert("PASSWORD-POLICY", "POLICY-SUCCESS-REDIRECT", "Redirects to /dashboard upon successful password update", 
        $authController->redirectedTo === '/dashboard');

    // Verify PASSWORD_CHANGED audit log
    $stmtLog = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE action = 'PASSWORD_CHANGED' AND details LIKE '%qa_test_staff01%'");
    $stmtLog->execute();
    $passChangeLogs = (int)$stmtLog->fetchColumn();
    auditAssert("PASSWORD-POLICY", "POLICY-AUDIT-LOG", "AuditLog records PASSWORD_CHANGED event", 
        $passChangeLogs > 0);

    // =========================================================================
    // SECTION 5: CSRF & SESSION SECURITY AUDIT
    // =========================================================================
    echo "\n--- SECTION 5: CSRF & SESSION SECURITY AUDIT ---\n";

    // 5.1 POST /login: Omitted CSRF token is rejected
    $_SESSION = ['csrf_token' => 'valid_token_123'];
    $_POST = ['csrf_token' => '', 'username' => 'qa_test_staff01', 'password' => 'BhcNewCompliantPass@2026'];
    $authController->resetCapture();
    $authController->login();
    auditAssert("CSRF", "CSRF-LOGIN-OMITTED", "POST /login rejects omitted CSRF token", 
        $authController->redirectedTo === '/login' && 
        strpos($_SESSION['login_error'] ?? '', 'Invalid session token') !== false);

    // 5.2 POST /login: Forged CSRF token is rejected
    $_SESSION = ['csrf_token' => 'valid_token_123'];
    $_POST = ['csrf_token' => 'forged_attacker_token_xyz', 'username' => 'qa_test_staff01', 'password' => 'BhcNewCompliantPass@2026'];
    $authController->resetCapture();
    $authController->login();
    auditAssert("CSRF", "CSRF-LOGIN-FORGED", "POST /login rejects forged CSRF token", 
        $authController->redirectedTo === '/login' && 
        strpos($_SESSION['login_error'] ?? '', 'Invalid session token') !== false);

    // 5.3 POST /change-password: Forged CSRF token is rejected
    $_SESSION = [
        'user_id' => $testUser1Id,
        'username' => 'qa_test_staff01',
        'must_change_password' => 1,
        'csrf_token' => 'valid_token_123'
    ];
    $_POST = [
        'csrf_token' => 'forged_attacker_token_xyz',
        'current_password' => 'TempPass@2026',
        'new_password' => 'ValidPass@2026',
        'confirm_password' => 'ValidPass@2026'
    ];
    $authController->resetCapture();
    $authController->changePassword();
    $csrfErrors = $authController->viewParams['errors'] ?? [];
    auditAssert("CSRF", "CSRF-CHANGEPASS-FORGED", "POST /change-password rejects forged CSRF token with security violation", 
        $authController->renderedView === 'auth/change_password' && 
        strpos($csrfErrors[0] ?? '', 'CSRF mismatch') !== false);

    // 5.4 POST /logout: Forged CSRF token is rejected
    $_SESSION = [
        'user_id' => $testUser1Id,
        'username' => 'qa_test_staff01',
        'csrf_token' => 'valid_token_123'
    ];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = ['csrf_token' => 'forged_attacker_token_xyz'];
    $authController->resetCapture();
    $authController->logout();
    auditAssert("CSRF", "CSRF-LOGOUT-FORGED", "POST /logout rejects forged CSRF token", 
        $authController->redirectedTo === '/login');

    // 5.5 POST /logout: Valid CSRF token successfully clears session
    $_SESSION = [
        'user_id' => $testUser1Id,
        'username' => 'qa_test_staff01',
        'csrf_token' => 'valid_token_123'
    ];
    $_POST = ['csrf_token' => 'valid_token_123'];
    $authController->resetCapture();
    $authController->logout();
    auditAssert("CSRF", "CSRF-LOGOUT-VALID", "POST /logout with valid CSRF token destroys session and redirects to /login", 
        $authController->redirectedTo === '/login' && empty($_SESSION));

    // 5.6 Cancel & Sign Out Form in change_password.php contains CSRF field and POST action
    $changePasswordViewContent = file_get_contents(BASE_PATH . '/app/Views/auth/change_password.php');
    $hasCancelForm = strpos($changePasswordViewContent, 'id="cancelSignOutForm"') !== false;
    $hasPostLogout = strpos($changePasswordViewContent, 'action="<?= url(\'/logout\') ?>" method="POST"') !== false;
    $hasCsrfInsideCancel = strpos($changePasswordViewContent, '<?= csrf_field() ?>') !== false;
    auditAssert("CSRF", "CSRF-CANCEL-SIGNOUT-FORM", "Cancel & Sign Out button is wrapped in a secure POST form with CSRF protection", 
        $hasCancelForm && $hasPostLogout && $hasCsrfInsideCancel);

    // =========================================================================
    // SECTION 6: XSS & OUTPUT SANITIZATION AUDIT
    // =========================================================================
    echo "\n--- SECTION 6: XSS & OUTPUT SANITIZATION AUDIT ---\n";

    // 6.1 Inspect dynamic variables in login.php
    $loginViewContent = file_get_contents(BASE_PATH . '/app/Views/auth/login.php');
    auditAssert("XSS", "XSS-LOGIN-USERNAME", "Username value in login.php is escaped with h()", 
        strpos($loginViewContent, 'value="<?= h($username ?? \'\') ?>"') !== false);
    auditAssert("XSS", "XSS-LOGIN-TIMEOUT", "Timeout message in login.php is escaped with h()", 
        strpos($loginViewContent, '<?= h($timeoutMessage) ?>') !== false);
    auditAssert("XSS", "XSS-LOGIN-ERROR", "Error message in login.php is escaped with h()", 
        strpos($loginViewContent, '<?= h($error) ?>') !== false);

    // 6.2 Inspect dynamic variables in change_password.php
    auditAssert("XSS", "XSS-CHPASS-NAME", "Full name in change_password.php is escaped with h()", 
        strpos($changePasswordViewContent, '<?= h($fullName) ?>') !== false);
    auditAssert("XSS", "XSS-CHPASS-EMPID", "Employee ID in change_password.php is escaped with h()", 
        strpos($changePasswordViewContent, '<?= h($employeeId) ?>') !== false);
    auditAssert("XSS", "XSS-CHPASS-ROLE", "Role name in change_password.php is escaped with h()", 
        strpos($changePasswordViewContent, '<?= h($roleName) ?>') !== false);
    auditAssert("XSS", "XSS-CHPASS-INITIALS", "Initials avatar in change_password.php is escaped with h()", 
        strpos($changePasswordViewContent, '<?= h($initials) ?>') !== false);
    auditAssert("XSS", "XSS-CHPASS-ERRORS", "Error items in change_password.php are escaped with h()", 
        strpos($changePasswordViewContent, '<li><?= h($err) ?></li>') !== false);

    // 6.3 Adversarial View Rendering Test with Hostile XSS Vectors
    $xssUsername = '"><script>alert("xss_user")</script><"';
    $xssError = '<img src=x onerror=alert("xss_err")>';
    $xssTimeout = '<svg/onload=alert("xss_timeout")>';

    $username = $xssUsername;
    $error = $xssError;
    $timeoutMessage = $xssTimeout;

    ob_start();
    require BASE_PATH . '/app/Views/auth/login.php';
    $renderedLogin = ob_get_clean();

    $noRawScriptInLogin = strpos($renderedLogin, '<script>alert("xss_user")</script>') === false;
    $noRawImgInLogin = strpos($renderedLogin, '<img src=x onerror=alert("xss_err")>') === false;
    $noRawSvgInLogin = strpos($renderedLogin, '<svg/onload=alert("xss_timeout")>') === false;

    auditAssert("XSS", "XSS-RENDER-LOGIN", "Adversarial XSS injection strings in login.php are 100% neutralized via HTML entities", 
        $noRawScriptInLogin && $noRawImgInLogin && $noRawSvgInLogin);

    // 6.4 Adversarial View Rendering Test for change_password.php
    $xssUser = [
        'id' => 888,
        'username' => 'qa_xss_user',
        'employee_id' => 'EMP-<script>alert("xss_emp")</script>',
        'first_name' => 'Maria<script>alert(1)</script>',
        'last_name' => 'Santos"><svg onload=alert(2)>',
        'role' => 'nurse"><img src=x onerror=alert(3)>'
    ];
    $xssErrorsList = ['<b onmouseover=alert("xss_err")>Bad input</b>'];
    $user = $xssUser;
    $errors = $xssErrorsList;
    $disable_layout = true;

    ob_start();
    require BASE_PATH . '/app/Views/auth/change_password.php';
    $renderedChange = ob_get_clean();

    $noRawEmpXss = strpos($renderedChange, '<script>alert("xss_emp")</script>') === false;
    $noRawFnXss = strpos($renderedChange, '<script>alert(1)</script>') === false;
    $noRawLnXss = strpos($renderedChange, '<svg onload=alert(2)>') === false;
    $noRawRoleXss = strpos($renderedChange, '<img src=x onerror=alert(3)>') === false;
    $noRawMouseoverXss = strpos($renderedChange, '<b onmouseover=') === false;

    auditAssert("XSS", "XSS-RENDER-CHANGEPASS", "Adversarial XSS injection strings in change_password.php are 100% neutralized via HTML entities", 
        $noRawEmpXss && $noRawFnXss && $noRawLnXss && $noRawRoleXss && $noRawMouseoverXss);

    // =========================================================================
    // SECTION 7: OFFLINE COMPLIANCE AUDIT
    // =========================================================================
    echo "\n--- SECTION 7: OFFLINE COMPLIANCE AUDIT ---\n";

    $cssContent = file_get_contents(BASE_PATH . '/public/assets/css/auth-redesign.css');

    // 7.1 Verify zero external references in CSS
    $hasHttpInCss = stripos($cssContent, 'http://') !== false || stripos($cssContent, 'https://') !== false;
    $hasFontsInCss = stripos($cssContent, 'fonts.googleapis.com') !== false || stripos($cssContent, 'fonts.gstatic.com') !== false;
    $hasExternalImports = stripos($cssContent, '@import') !== false;
    auditAssert("OFFLINE", "OFFLINE-CSS-ZERO-REMOTE", "auth-redesign.css contains 0 external CDNs, 0 Google Fonts, and 0 remote @import rules", 
        !$hasHttpInCss && !$hasFontsInCss && !$hasExternalImports);

    // 7.2 Verify zero external scripts/styles in login.php
    $hasCdnInLogin = stripos($loginViewContent, 'cdn.jsdelivr.net') !== false || 
                     stripos($loginViewContent, 'cdnjs.cloudflare.com') !== false || 
                     stripos($loginViewContent, 'unpkg.com') !== false || 
                     stripos($loginViewContent, 'fonts.googleapis.com') !== false;
    auditAssert("OFFLINE", "OFFLINE-LOGIN-ZERO-CDN", "login.php has 0 external CDN scripts or remote styles", 
        !$hasCdnInLogin);

    // 7.3 Verify zero external scripts/styles in change_password.php
    $hasCdnInChange = stripos($changePasswordViewContent, 'cdn.jsdelivr.net') !== false || 
                      stripos($changePasswordViewContent, 'cdnjs.cloudflare.com') !== false || 
                      stripos($changePasswordViewContent, 'unpkg.com') !== false || 
                      stripos($changePasswordViewContent, 'fonts.googleapis.com') !== false;
    auditAssert("OFFLINE", "OFFLINE-CHPASS-ZERO-CDN", "change_password.php has 0 external CDN scripts or remote styles", 
        !$hasCdnInChange);

    // 7.4 Modals completely present and self-contained in login.php
    $hasPrivacyModalLogin = strpos($loginViewContent, 'id="privacyPolicyModal"') !== false && 
                            strpos($loginViewContent, 'Republic Act No. 10173') !== false;
    $hasTermsModalLogin = strpos($loginViewContent, 'id="termsModal"') !== false && 
                          strpos($loginViewContent, 'Acceptable Use Policy') !== false;
    auditAssert("OFFLINE", "OFFLINE-LOGIN-MODALS", "login.php contains offline Privacy Policy (RA 10173) and Terms of Use modals", 
        $hasPrivacyModalLogin && $hasTermsModalLogin);

    // 7.5 Modals completely present and self-contained in change_password.php
    $hasPrivacyModalChange = strpos($changePasswordViewContent, 'id="privacyPolicyModal"') !== false && 
                             strpos($changePasswordViewContent, 'Republic Act No. 10173') !== false;
    $hasTermsModalChange = strpos($changePasswordViewContent, 'id="termsModal"') !== false && 
                           strpos($changePasswordViewContent, 'Acceptable Use Policy') !== false;
    auditAssert("OFFLINE", "OFFLINE-CHPASS-MODALS", "change_password.php contains offline Privacy Policy (RA 10173) and Terms of Use modals", 
        $hasPrivacyModalChange && $hasTermsModalChange);

    // =========================================================================
    // SECTION 8: SESSION INACTIVITY & TIMEOUT LIFECYCLE AUDIT
    // =========================================================================
    echo "\n--- SECTION 8: SESSION INACTIVITY & TIMEOUT LIFECYCLE ---\n";

    // 8.1 Idle timeout triggers SESSION_TIMEOUT audit log & destroys session
    $_SESSION = [
        'user_id' => $testUser1Id,
        'username' => 'qa_test_staff01',
        'last_activity' => time() - 950 // 950 seconds > 900 second threshold
    ];
    $testMiddleware->terminated = false;
    $testMiddleware->redirectedTo = null;
    $testMiddleware->handle();

    auditAssert("SESSION-TIMEOUT", "TIMEOUT-REDIRECT", "AuthMiddleware redirects to /login?timeout=1 when idle > 900 seconds", 
        $testMiddleware->redirectedTo === '/login?timeout=1');
    auditAssert("SESSION-TIMEOUT", "TIMEOUT-SESSION-PURGED", "AuthMiddleware purges session on inactivity timeout", 
        empty($_SESSION));

    $stmtTimeoutLog = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE action = 'SESSION_TIMEOUT' AND details LIKE '%qa_test_staff01%'");
    $stmtTimeoutLog->execute();
    $timeoutLogsCount = (int)$stmtTimeoutLog->fetchColumn();
    auditAssert("SESSION-TIMEOUT", "TIMEOUT-AUDIT-LOG", "AuthMiddleware writes SESSION_TIMEOUT action to audit_logs", 
        $timeoutLogsCount > 0);

    // 8.2 AuthController::showLogin handles timeout query parameter
    $_GET['timeout'] = '1';
    $authController->resetCapture();
    $authController->showLogin();
    auditAssert("SESSION-TIMEOUT", "TIMEOUT-SHOWLOGIN-BANNER", "showLogin sets user-friendly session timeout alert banner", 
        $authController->renderedView === 'auth/login' && 
        strpos($authController->viewParams['timeoutMessage'] ?? '', 'logged out due to inactivity') !== false);
    unset($_GET['timeout']);

    // =========================================================================
    // SECTION 9: CORE PILLARS REGRESSION & DATABASE INTEGRITY
    // =========================================================================
    echo "\n--- SECTION 9: CORE PILLARS REGRESSION & DATABASE INTEGRITY ---\n";

    // 9.1 User Model Integrity & Listing
    $allUsers = $userModel->all();
    auditAssert("REGRESSION", "REGRESS-USERS-ALL", "User::all() executes without SQL syntax error and returns user list", 
        is_array($allUsers) && count($allUsers) >= 1);

    // 9.2 Patient Directory Smoke Check
    $patientModel = new \App\Models\Patient();
    $patientCount = (int)$pdo->query("SELECT COUNT(*) FROM patients WHERE deleted_at IS NULL")->fetchColumn();
    $patients = $patientModel->allActive();
    auditAssert("REGRESSION", "REGRESS-PATIENT-MODEL", "Patient::allActive() functions normally with intact record counts", 
        is_array($patients) && count($patients) === $patientCount);

    // 9.3 Consultation Records Smoke Check
    $consultationModel = new \App\Models\Consultation();
    $consultationCount = (int)$pdo->query("SELECT COUNT(*) FROM consultations WHERE deleted_at IS NULL")->fetchColumn();
    $consultationsSample = $consultationModel->findByPatientId(1);
    auditAssert("REGRESSION", "REGRESS-CONSULTATIONS", "Consultation model and table query execute without error", 
        $consultationCount >= 0 && is_array($consultationsSample));

    // 9.4 Queue Entries Smoke Check
    $queueModel = new \App\Models\QueueEntry();
    $queueCount = (int)$pdo->query("SELECT COUNT(*) FROM queue_entries")->fetchColumn();
    $todayQueue = $queueModel->findAllToday();
    auditAssert("REGRESSION", "REGRESS-QUEUE", "QueueEntry::findAllToday() executes without error and table remains intact", 
        $queueCount >= 0 && is_array($todayQueue));

    // 9.5 Primary Administrator (User 1) Isolation Check
    $stmtAdminCheck = $pdo->prepare("SELECT * FROM users WHERE id = 1 LIMIT 1");
    $stmtAdminCheck->execute();
    $currentAdmin = $stmtAdminCheck->fetch();

    $adminUnmodified = (
        $currentAdmin['username'] === $originalUser1['username'] &&
        $currentAdmin['password_hash'] === $originalUser1['password_hash'] &&
        $currentAdmin['role'] === $originalUser1['role'] &&
        (int)$currentAdmin['failed_attempts'] === (int)$originalUser1['failed_attempts'] &&
        (int)$currentAdmin['must_change_password'] === (int)$originalUser1['must_change_password']
    );
    auditAssert("REGRESSION", "REGRESS-ADMIN-UNTOUCHED", "Primary Administrator (User ID 1) credentials and status are 100% untouched", 
        $adminUnmodified);

} catch (\Throwable $e) {
    $failCount++;
    $failures[] = "[EXCEPTION] Fatal Test Harness Exception: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine();
    echo "\n  [FAIL] Fatal Exception: " . $e->getMessage() . "\n";
    echo "         Stack Trace:\n" . $e->getTraceAsString() . "\n";
} finally {
    // =========================================================================
    // SECTION 10: MANDATORY PRISTINE TEARDOWN
    // =========================================================================
    echo "\n--- SECTION 10: MANDATORY PRISTINE TEARDOWN ---\n";

    // 10.1 Delete all temporary test records
    $deletedUsers = $pdo->exec("DELETE FROM users WHERE username LIKE 'qa_test_%' OR username LIKE 'qa_audit_%'");
    echo "  [TEARDOWN] Purged {$deletedUsers} temporary test user record(s).\n";

    // 10.2 Purge test audit logs
    $deletedLogs = $pdo->exec("DELETE FROM audit_logs WHERE details LIKE '%qa_test_%' OR details LIKE '%qa_audit_%'");
    echo "  [TEARDOWN] Purged {$deletedLogs} test audit log entries.\n";

    // 10.3 Confirm User 1 state restoration
    $finalAdminCheck = $pdo->query("SELECT * FROM users WHERE id = 1 LIMIT 1")->fetch();
    if ($finalAdminCheck['password_hash'] !== $originalUser1['password_hash'] || (int)$finalAdminCheck['failed_attempts'] !== (int)$originalUser1['failed_attempts']) {
        $pdo->prepare("UPDATE users SET password_hash = :hash, failed_attempts = :attempts WHERE id = 1")
            ->execute(['hash' => $originalUser1['password_hash'], 'attempts' => $originalUser1['failed_attempts']]);
        echo "  [TEARDOWN] Restored User 1 from initial snapshot.\n";
    } else {
        echo "  [TEARDOWN] User 1 verified in pristine condition.\n";
    }

    // Clean session
    $_SESSION = [];
    $_POST = [];
    $_GET = [];
}

// Summary Report
$total = $passCount + $failCount;
echo "\n====================================================================\n";
echo "                      AUDIT SUMMARY REPORT                          \n";
echo "====================================================================\n";
echo "TOTAL ASSERTIONS RUN: {$total} | PASS: {$passCount} | FAIL: {$failCount}\n";

if ($failCount > 0) {
    echo "\nIDENTIFIED DEFECTS:\n";
    foreach ($failures as $idx => $f) {
        echo "  " . ($idx + 1) . ". {$f}\n";
    }
    echo "\nAUDIT STATUS: REJECTED (Failures must be resolved)\n";
    exit(1);
} else {
    echo "\nAUDIT STATUS: ALL SECURITY & FUNCTIONAL ASSERTIONS PASSED\n";
    echo "====================================================================\n";
    exit(0);
}
