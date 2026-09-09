<?php

/**
 * ====================================================================
 * BARANGAY SINALHAN HEALTH CENTER PATIENT MANAGEMENT SYSTEM
 * QA & SECURITY AUDIT SUITE: USER ACCOUNT MODULE HARDENING (PHASE 3)
 * ====================================================================
 * 
 * Audit Checklist Coverage:
 * 1. CSRF Enforcement Audit on all 5 POST endpoints
 *    - POST /users (store)
 *    - POST /users/{id} (update)
 *    - POST /users/{id}/reset-password (resetPassword)
 *    - POST /users/{id}/toggle-status (toggleStatus)
 *    - POST /users/{id}/reset-lockout (resetLockout)
 * 2. Backend Validation Rules (Username, Email, Contact No)
 * 3. Session Inactivity & AdminMiddleware (Timeout, Unauthenticated, RBAC)
 * 4. Session Synchronization (Real-time $_SESSION['user_fullname'] sync)
 * 5. MVC Integrity & UX Input Retention (0 User models in view, old_input)
 * 6. Regression Verification (User listing, login lockout, profile)
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/../app/helpers.php';

// Ensure session exists
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbConfig = require __DIR__ . '/../config/database.php';
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
echo "   MODULE UNDER AUDIT: USER ACCOUNTS HARDENING & REFACTORING        \n";
echo "====================================================================\n\n";

// Snapshot User 1 to guarantee 100% database state restoration
$stmtUser1 = $pdo->prepare("SELECT * FROM users WHERE id = 1 LIMIT 1");
$stmtUser1->execute();
$originalUser1 = $stmtUser1->fetch();

// Test Controller subclass to capture redirects and views without process termination
class AuditedUserController extends \App\Controllers\UserController {
    public ?string $redirectedTo = null;
    public ?string $renderedView = null;
    public array $viewParams = [];

    protected function redirect($url) {
        $this->redirectedTo = $url;
    }

    protected function view($name, $data = []) {
        $this->renderedView = $name;
        $this->viewParams = $data;
    }
}

class AuditedAuthController extends \App\Controllers\AuthController {
    public ?string $redirectedTo = null;

    protected function redirect($url) {
        $this->redirectedTo = $url;
    }
}

class AuditedProfileController extends \App\Controllers\ProfileController {
    public ?string $redirectedTo = null;

    protected function redirect($url) {
        $this->redirectedTo = $url;
    }
}

// Clean up any stale test accounts from previous runs
$pdo->prepare("DELETE FROM users WHERE username LIKE 'qa_audit_%'")->execute();
$pdo->prepare("DELETE FROM audit_logs WHERE username LIKE 'qa_audit_%'")->execute();

try {
    // -------------------------------------------------------------
    // Test Setup: Create dedicated test admin and test staff accounts
    // -------------------------------------------------------------
    $adminPassRaw = 'AdminSecret2026!';
    $adminPassHash = password_hash($adminPassRaw, PASSWORD_BCRYPT);
    $staffPassRaw = 'StaffSecret2026!';
    $staffPassHash = password_hash($staffPassRaw, PASSWORD_BCRYPT);

    // Ensure User 1 has known password for resetPassword admin verification
    $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = 1")->execute(['hash' => $adminPassHash]);

    // Insert test target user (staff)
    $stmt = $pdo->prepare("
        INSERT INTO users (username, password_hash, role, first_name, last_name, email, contact_no, job_title, employee_id, department, status, failed_attempts)
        VALUES ('qa_audit_target', :hash, 'staff', 'TargetFirstName', 'TargetLastName', 'target@sinalhan.test', '09171112233', 'Nurse', 'EMP-TEST-01', 'General Consultation', 'active', 0)
    ");
    $stmt->execute(['hash' => $staffPassHash]);
    $targetUserId = (int)$pdo->lastInsertId();

    $userModel = new \App\Models\User();

    // =============================================================
    // 1. CSRF ENFORCEMENT AUDIT ON ALL 5 POST ENDPOINTS
    // =============================================================
    echo "--- 1. CSRF ENFORCEMENT AUDIT ON ALL 5 POST ENDPOINTS ---\n";

    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'admin';
    $_SESSION['username'] = 'admin';
    $_SESSION['csrf_token'] = 'valid_qa_csrf_token_8848';
    $_SERVER['REQUEST_METHOD'] = 'POST';

    $ctrl = new AuditedUserController();

    // Helper to query latest SECURITY_VIOLATION audit log
    $getLatestViolation = function() use ($pdo) {
        $stmt = $pdo->query("SELECT * FROM audit_logs WHERE action = 'SECURITY_VIOLATION' ORDER BY id DESC LIMIT 1");
        return $stmt->fetch();
    };

    // -------------------------------------------------------------
    // Endpoint 1: POST /users (store)
    // -------------------------------------------------------------
    echo "\n  [Endpoint 1/5: POST /users (store)]\n";

    // 1.A: Missing token
    $_POST = [
        'username' => 'qa_audit_new1',
        'password' => 'Password123!',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'role' => 'staff'
    ]; // csrf_token missing
    $ctrl->store();
    $uCheck1 = $userModel->findByUsername('qa_audit_new1');
    $log1 = $getLatestViolation();
    auditAssert("CSRF", "CSRF-STORE-1", "Missing CSRF token rejects creation and preserves DB state", $uCheck1 === false, "User was created when CSRF token was missing");
    auditAssert("CSRF", "CSRF-STORE-2", "Missing CSRF token logs SECURITY_VIOLATION in AuditLog", $log1 && strpos($log1['details'], 'user account creation') !== false, "Audit log not found or details mismatch");

    // 1.B: Forged token
    $_POST['csrf_token'] = 'forged_fake_token_attacker';
    $ctrl->store();
    $uCheck2 = $userModel->findByUsername('qa_audit_new1');
    $log2 = $getLatestViolation();
    auditAssert("CSRF", "CSRF-STORE-3", "Forged CSRF token rejects creation and preserves DB state", $uCheck2 === false, "User was created with forged CSRF token");
    auditAssert("CSRF", "CSRF-STORE-4", "Forged CSRF token logs SECURITY_VIOLATION in AuditLog", $log2 && strpos($log2['details'], 'user account creation') !== false, "Audit log not found or details mismatch");

    // 1.C: Valid token
    $_POST['csrf_token'] = $_SESSION['csrf_token'];
    $ctrl->store();
    $uCheck3 = $userModel->findByUsername('qa_audit_new1');
    auditAssert("CSRF", "CSRF-STORE-5", "Valid CSRF token successfully creates user record in DB", $uCheck3 !== false && $uCheck3['first_name'] === 'John', "User record not created with valid CSRF");

    // -------------------------------------------------------------
    // Endpoint 2: POST /users/{id} (update)
    // -------------------------------------------------------------
    echo "\n  [Endpoint 2/5: POST /users/{id} (update)]\n";

    // 2.A: Missing token
    $_POST = [
        'first_name' => 'MaliciousEdit1',
        'last_name' => 'Hacker1',
        'role' => 'staff'
    ];
    $ctrl->update($targetUserId);
    $targetDb1 = $userModel->findById($targetUserId);
    $log3 = $getLatestViolation();
    auditAssert("CSRF", "CSRF-UPDATE-1", "Missing CSRF token rejects update and preserves DB record", $targetDb1['first_name'] === 'TargetFirstName', "Record was modified despite missing CSRF token");
    auditAssert("CSRF", "CSRF-UPDATE-2", "Missing CSRF token logs SECURITY_VIOLATION for update", $log3 && strpos($log3['details'], "user ID {$targetUserId}") !== false, "Missing audit log for update CSRF violation");

    // 2.B: Forged token
    $_POST['csrf_token'] = 'bad_token_999';
    $ctrl->update($targetUserId);
    $targetDb2 = $userModel->findById($targetUserId);
    $log4 = $getLatestViolation();
    auditAssert("CSRF", "CSRF-UPDATE-3", "Forged CSRF token rejects update and preserves DB record", $targetDb2['first_name'] === 'TargetFirstName', "Record was modified despite forged CSRF token");
    auditAssert("CSRF", "CSRF-UPDATE-4", "Forged CSRF token logs SECURITY_VIOLATION for update", $log4 && strpos($log4['details'], "user ID {$targetUserId}") !== false, "Missing audit log for update forged CSRF violation");

    // 2.C: Valid token
    $_POST['csrf_token'] = $_SESSION['csrf_token'];
    $_POST['first_name'] = 'LegitUpdatedFirstName';
    $_POST['last_name'] = 'LegitUpdatedLastName';
    $ctrl->update($targetUserId);
    $targetDb3 = $userModel->findById($targetUserId);
    auditAssert("CSRF", "CSRF-UPDATE-5", "Valid CSRF token allows updating record in DB", $targetDb3['first_name'] === 'LegitUpdatedFirstName', "Update failed with valid CSRF token");

    // -------------------------------------------------------------
    // Endpoint 3: POST /users/{id}/reset-password (resetPassword)
    // -------------------------------------------------------------
    echo "\n  [Endpoint 3/5: POST /users/{id}/reset-password (resetPassword)]\n";

    $initialHash = $targetDb3['password_hash'];

    // 3.A: Missing token
    $_POST = [
        'admin_password' => $adminPassRaw,
        'new_password' => 'NewTempPass2026!',
        'confirm_password' => 'NewTempPass2026!'
    ];
    $ctrl->resetPassword($targetUserId);
    $targetDb4 = $userModel->findById($targetUserId);
    $log5 = $getLatestViolation();
    auditAssert("CSRF", "CSRF-RESETPASS-1", "Missing CSRF token rejects password reset and preserves hash", $targetDb4['password_hash'] === $initialHash, "Password hash was modified despite missing CSRF token");
    auditAssert("CSRF", "CSRF-RESETPASS-2", "Missing CSRF token logs SECURITY_VIOLATION for resetPassword", $log5 && strpos($log5['details'], "password reset for user ID {$targetUserId}") !== false, "Missing audit log for resetPassword CSRF violation");

    // 3.B: Forged token
    $_POST['csrf_token'] = 'invalid_attacker_token';
    $ctrl->resetPassword($targetUserId);
    $targetDb5 = $userModel->findById($targetUserId);
    $log6 = $getLatestViolation();
    auditAssert("CSRF", "CSRF-RESETPASS-3", "Forged CSRF token rejects password reset and preserves hash", $targetDb5['password_hash'] === $initialHash, "Password hash was modified despite forged CSRF token");
    auditAssert("CSRF", "CSRF-RESETPASS-4", "Forged CSRF token logs SECURITY_VIOLATION for resetPassword", $log6 && strpos($log6['details'], "password reset for user ID {$targetUserId}") !== false, "Missing audit log for resetPassword forged CSRF violation");

    // 3.C: Valid token
    $_POST['csrf_token'] = $_SESSION['csrf_token'];
    $ctrl->resetPassword($targetUserId);
    $targetDb6 = $userModel->findById($targetUserId);
    auditAssert("CSRF", "CSRF-RESETPASS-5", "Valid CSRF token successfully resets user password", $targetDb6['password_hash'] !== $initialHash && password_verify('NewTempPass2026!', $targetDb6['password_hash']), "Password hash not updated on valid CSRF");
    auditAssert("CSRF", "CSRF-RESETPASS-6", "Valid password reset sets must_change_password flag to 1", (int)$targetDb6['must_change_password'] === 1, "must_change_password flag not set to 1");

    // -------------------------------------------------------------
    // Endpoint 4: POST /users/{id}/toggle-status (toggleStatus)
    // -------------------------------------------------------------
    echo "\n  [Endpoint 4/5: POST /users/{id}/toggle-status (toggleStatus)]\n";

    auditAssert("CSRF", "CSRF-STATUS-PRE", "Target status is currently active", $targetDb6['status'] === 'active');

    // 4.A: Missing token
    $_POST = [];
    $ctrl->toggleStatus($targetUserId);
    $targetDb7 = $userModel->findById($targetUserId);
    $log7 = $getLatestViolation();
    auditAssert("CSRF", "CSRF-STATUS-1", "Missing CSRF token rejects status toggle and preserves status", $targetDb7['status'] === 'active', "Status was toggled despite missing CSRF token");
    auditAssert("CSRF", "CSRF-STATUS-2", "Missing CSRF token logs SECURITY_VIOLATION for toggleStatus", $log7 && strpos($log7['details'], "status toggle for user ID {$targetUserId}") !== false, "Missing audit log for status toggle CSRF violation");

    // 4.B: Forged token
    $_POST['csrf_token'] = 'forged_token_evil';
    $ctrl->toggleStatus($targetUserId);
    $targetDb8 = $userModel->findById($targetUserId);
    $log8 = $getLatestViolation();
    auditAssert("CSRF", "CSRF-STATUS-3", "Forged CSRF token rejects status toggle and preserves status", $targetDb8['status'] === 'active', "Status was toggled despite forged CSRF token");
    auditAssert("CSRF", "CSRF-STATUS-4", "Forged CSRF token logs SECURITY_VIOLATION for toggleStatus", $log8 && strpos($log8['details'], "status toggle for user ID {$targetUserId}") !== false, "Missing audit log for status toggle forged CSRF violation");

    // 4.C: Valid token
    $_POST['csrf_token'] = $_SESSION['csrf_token'];
    $ctrl->toggleStatus($targetUserId);
    $targetDb9 = $userModel->findById($targetUserId);
    auditAssert("CSRF", "CSRF-STATUS-5", "Valid CSRF token successfully toggles status to inactive", $targetDb9['status'] === 'inactive', "Status not toggled to inactive on valid CSRF");

    // Toggle back to active with valid token
    $ctrl->toggleStatus($targetUserId);
    $targetDb10 = $userModel->findById($targetUserId);
    auditAssert("CSRF", "CSRF-STATUS-6", "Valid CSRF token toggles status back to active", $targetDb10['status'] === 'active', "Status not toggled back to active on valid CSRF");

    // -------------------------------------------------------------
    // Endpoint 5: POST /users/{id}/reset-lockout (resetLockout)
    // -------------------------------------------------------------
    echo "\n  [Endpoint 5/5: POST /users/{id}/reset-lockout (resetLockout)]\n";

    // Set target account into locked out state
    $pdo->prepare("UPDATE users SET failed_attempts = 5, last_failed_login_at = NOW() WHERE id = :id")->execute(['id' => $targetUserId]);
    $targetLocked = $userModel->findById($targetUserId);
    $lockoutInfo = $userModel->isLockedOut($targetLocked);
    auditAssert("CSRF", "CSRF-LOCK-PRE", "Target account confirmed in 15-min lockout state", $lockoutInfo['is_locked'] === true);

    // 5.A: Missing token
    $_POST = [];
    $ctrl->resetLockout($targetUserId);
    $targetDb11 = $userModel->findById($targetUserId);
    $log9 = $getLatestViolation();
    auditAssert("CSRF", "CSRF-LOCK-1", "Missing CSRF token rejects lockout reset and preserves failed_attempts", (int)$targetDb11['failed_attempts'] === 5, "Lockout was reset despite missing CSRF token");
    auditAssert("CSRF", "CSRF-LOCK-2", "Missing CSRF token logs SECURITY_VIOLATION for resetLockout", $log9 && strpos($log9['details'], "lockout reset for user ID {$targetUserId}") !== false, "Missing audit log for lockout reset CSRF violation");

    // 5.B: Forged token
    $_POST['csrf_token'] = 'fake_lockout_token_attacker';
    $ctrl->resetLockout($targetUserId);
    $targetDb12 = $userModel->findById($targetUserId);
    $log10 = $getLatestViolation();
    auditAssert("CSRF", "CSRF-LOCK-3", "Forged CSRF token rejects lockout reset and preserves failed_attempts", (int)$targetDb12['failed_attempts'] === 5, "Lockout was reset despite forged CSRF token");
    auditAssert("CSRF", "CSRF-LOCK-4", "Forged CSRF token logs SECURITY_VIOLATION for resetLockout", $log10 && strpos($log10['details'], "lockout reset for user ID {$targetUserId}") !== false, "Missing audit log for lockout reset forged CSRF violation");

    // 5.C: Valid token
    $_POST['csrf_token'] = $_SESSION['csrf_token'];
    $ctrl->resetLockout($targetUserId);
    $targetDb13 = $userModel->findById($targetUserId);
    $lockoutClearedInfo = $userModel->isLockedOut($targetDb13);
    auditAssert("CSRF", "CSRF-LOCK-5", "Valid CSRF token successfully clears lockout in DB (failed_attempts = 0)", (int)$targetDb13['failed_attempts'] === 0 && $targetDb13['last_failed_login_at'] === null, "failed_attempts not reset to 0");
    auditAssert("CSRF", "CSRF-LOCK-6", "isLockedOut evaluates to false after resetLockout execution", $lockoutClearedInfo['is_locked'] === false, "Account still reports locked out");

    // =============================================================
    // 2. BACKEND VALIDATION RULES
    // =============================================================
    echo "\n--- 2. BACKEND VALIDATION RULES AUDIT ---\n";

    // Setup base post payload for store()
    $baseStoreData = [
        'csrf_token' => $_SESSION['csrf_token'],
        'password' => 'ValidPassword123!',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'role' => 'staff',
        'email' => 'valid_base@sinalhan.test',
        'contact_no' => '09171234567'
    ];

    // 2.1: Username regex verification
    echo "\n  [2.1: Username Regex Validation: /^[a-zA-Z0-9_]{3,20}$/]\n";

    $invalidUsernames = [
        'ab' => 'Length < 3 characters (2 chars)',
        'a' => 'Length 1 character',
        '' => 'Empty username',
        'user_name_longer_than_twenty_characters' => 'Length > 20 characters (39 chars)',
        'a123456789012345678901' => 'Length > 20 characters (21 chars boundary)',
        'admin!@#' => 'Special symbols !@#',
        'user$name' => 'Special symbol $',
        'test*user' => 'Special symbol *',
        'spaces here' => 'Embedded spaces (spaces here)',
        'user name' => 'Embedded space (user name)',
        'userñame' => 'Unicode ñ character',
        'nurse_😊' => 'Unicode emoji character',
        'médico' => 'Unicode accented character'
    ];

    foreach ($invalidUsernames as $badUser => $reason) {
        $_POST = $baseStoreData;
        $_POST['username'] = $badUser;
        $_SESSION['form_errors'] = [];
        $ctrl->store();
        $hasError = !empty($_SESSION['form_errors']);
        $regexErrorFound = false;
        foreach ($_SESSION['form_errors'] ?? [] as $err) {
            if (strpos($err, 'Username must be 3 to 20 alphanumeric characters') !== false || strpos($err, 'Username, Password, First Name, and Last Name are required') !== false) {
                $regexErrorFound = true;
                break;
            }
        }
        auditAssert("VALIDATION", "USER-REJECT-" . md5($badUser), "Rejected invalid username '{$badUser}' ({$reason})", $regexErrorFound, "Username '{$badUser}' was not rejected by validation. Errors: " . json_encode($_SESSION['form_errors'] ?? []));
    }

    $validUsernames = [
        'nurse_maria' => 'Alphanumeric with underscore (11 chars)',
        'admin2026' => 'Alphanumeric without underscore (9 chars)',
        'abc' => 'Minimum boundary (3 chars)',
        'a1234567890123456789' => 'Maximum boundary (20 chars)'
    ];

    foreach ($validUsernames as $goodUser => $desc) {
        $_POST = $baseStoreData;
        $_POST['username'] = $goodUser;
        $_POST['email'] = $goodUser . '@sinalhan.test';
        $_SESSION['form_errors'] = [];
        $ctrl->store();
        $createdUser = $userModel->findByUsername($goodUser);
        auditAssert("VALIDATION", "USER-ACCEPT-" . md5($goodUser), "Accepted valid username '{$goodUser}' ({$desc})", $createdUser !== false, "Valid username '{$goodUser}' was rejected. Errors: " . json_encode($_SESSION['form_errors'] ?? []));
    }

    // 2.2: Email format verification
    echo "\n  [2.2: Email Format Validation (filter_var)]\n";

    $invalidEmails = [
        'invalid.email@' => 'Missing domain after @',
        'justtext' => 'Plain text string without @',
        '@missinguser.com' => 'Missing local part before @',
        'user@.com' => 'Dot immediately following @',
        'user@domain..com' => 'Double dot in domain'
    ];

    foreach ($invalidEmails as $badEmail => $reason) {
        $_POST = $baseStoreData;
        $_POST['username'] = 'val_mail_' . substr(md5($badEmail), 0, 8);
        $_POST['email'] = $badEmail;
        $_SESSION['form_errors'] = [];
        $ctrl->store();
        $emailErrorFound = in_array('Invalid email address format.', $_SESSION['form_errors'] ?? []);
        auditAssert("VALIDATION", "EMAIL-REJECT-" . md5($badEmail), "Rejected invalid email format '{$badEmail}' ({$reason})", $emailErrorFound, "Invalid email was not rejected. Errors: " . json_encode($_SESSION['form_errors'] ?? []));
    }

    $validEmails = [
        'nurse_test@sinalhan.gov.ph',
        'valid.user@example.com',
        'doctor+tag@health.org.ph'
    ];

    foreach ($validEmails as $goodEmail) {
        $_POST = $baseStoreData;
        $uniqueUser = 'val_em_' . substr(md5($goodEmail), 0, 8);
        $_POST['username'] = $uniqueUser;
        $_POST['email'] = $goodEmail;
        $_SESSION['form_errors'] = [];
        $ctrl->store();
        $created = $userModel->findByUsername($uniqueUser);
        auditAssert("VALIDATION", "EMAIL-ACCEPT-" . md5($goodEmail), "Accepted valid email format '{$goodEmail}'", $created !== false, "Valid email was rejected. Errors: " . json_encode($_SESSION['form_errors'] ?? []));
    }

    // 2.3: Contact number Philippine mobile format verification
    echo "\n  [2.3: Contact Number Validation: /^09\d{9}$/]\n";

    $invalidContacts = [
        '08123456789' => 'Starts with 08 instead of 09',
        '091234' => 'Too short (6 digits)',
        '0912345678901' => 'Too long (13 digits)',
        '+639171234567' => 'Country code prefix (+63) instead of local 09 format',
        '0917123456a' => 'Contains non-digit character',
        '09 17123456' => 'Contains spaces'
    ];

    foreach ($invalidContacts as $badContact => $reason) {
        $_POST = $baseStoreData;
        $_POST['username'] = 'val_cnt_' . substr(md5($badContact), 0, 8);
        $_POST['contact_no'] = $badContact;
        $_SESSION['form_errors'] = [];
        $ctrl->store();
        $contactErrorFound = in_array('Contact number must be an 11-digit Philippine mobile number starting with 09 (e.g., 09171234567).', $_SESSION['form_errors'] ?? []);
        auditAssert("VALIDATION", "CONTACT-REJECT-" . md5($badContact), "Rejected invalid contact number '{$badContact}' ({$reason})", $contactErrorFound, "Invalid contact number was not rejected. Errors: " . json_encode($_SESSION['form_errors'] ?? []));
    }

    $validContacts = [
        '09171234567' => 'Standard Globe/TM format (0917)',
        '09987654321' => 'Standard Smart/TNT format (0998)',
        '09051122334' => 'Standard DITO/other mobile format (0905)'
    ];

    foreach ($validContacts as $goodContact => $desc) {
        $_POST = $baseStoreData;
        $uniqueUser = 'val_cn_' . substr(md5($goodContact), 0, 8);
        $_POST['username'] = $uniqueUser;
        $_POST['contact_no'] = $goodContact;
        $_POST['email'] = $uniqueUser . '@sinalhan.test';
        $_SESSION['form_errors'] = [];
        $ctrl->store();
        $created = $userModel->findByUsername($uniqueUser);
        auditAssert("VALIDATION", "CONTACT-ACCEPT-" . md5($goodContact), "Accepted valid contact number '{$goodContact}' ({$desc})", $created !== false, "Valid contact number was rejected. Errors: " . json_encode($_SESSION['form_errors'] ?? []));
    }

    // =============================================================
    // 3. SESSION INACTIVITY & ADMINMIDDLEWARE
    // =============================================================
    echo "\n--- 3. SESSION INACTIVITY & ADMINMIDDLEWARE AUDIT ---\n";

    // Subprocess execution runner to test AdminMiddleware handling of headers, redirects, 401/403, and exit codes
    function runMiddlewareSubprocess(array $sessionData, array $serverData): array {
        $phpBinary = PHP_BINARY;
        $sessionCode = var_export($sessionData, true);
        $serverCode = var_export($serverData, true);

        $script = <<<PHP
<?php
namespace App\Middleware {
    // Intercept header calls to inspect redirect targets in CLI
    function header(\$header) {
        \$GLOBALS['captured_headers'][] = \$header;
    }
}

namespace {
    \$GLOBALS['captured_headers'] = [];
    require_once __DIR__ . '/app/Core/Autoloader.php';
    \\App\\Core\\Autoloader::register();
    require_once __DIR__ . '/app/helpers.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    \$_SESSION = {$sessionCode};
    \$_SERVER = array_merge([
        'SCRIPT_NAME' => '/sinalhan-hc-system/public/index.php',
        'REQUEST_METHOD' => 'GET',
        'REMOTE_ADDR' => '127.0.0.1'
    ], {$serverCode});

    register_shutdown_function(function() {
        \$output = [
            'exit_code' => http_response_code(),
            'headers' => \$GLOBALS['captured_headers'],
            'session_active' => !empty(\$_SESSION),
            'last_activity' => \$_SESSION['last_activity'] ?? null,
            'session_id_exists' => isset(\$_SESSION['user_id'])
        ];
        echo '---RESULT_JSON---' . json_encode(\$output);
    });

    \$mw = new \\App\\Middleware\\AdminMiddleware();
    \$res = \$mw->handle();
    echo 'HANDLE_RETURNED:' . (\$res === true ? 'TRUE' : 'FALSE');
}
PHP;

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $process = proc_open($phpBinary, $descriptors, $pipes, dirname(__DIR__));
        fwrite($pipes[0], $script);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $parts = explode('---RESULT_JSON---', $stdout);
        $body = $parts[0] ?? '';
        $meta = isset($parts[1]) ? json_decode($parts[1], true) : [];

        return [
            'body' => $body,
            'meta' => $meta,
            'stderr' => $stderr
        ];
    }

    // 3.1: 15-minute inactivity timeout (> 900 seconds)
    echo "\n  [3.1: Inactivity Timeout Enforcement (15 mins = 900s)]\n";

    // Web request: idle for 901 seconds
    $resTimeoutWeb = runMiddlewareSubprocess(
        ['user_id' => 1, 'username' => 'admin', 'user_role' => 'admin', 'last_activity' => time() - 901],
        []
    );
    $headersWeb = $resTimeoutWeb['meta']['headers'] ?? [];
    $hasTimeoutRedirect = false;
    foreach ($headersWeb as $h) {
        if (strpos($h, '/login?timeout=1') !== false) {
            $hasTimeoutRedirect = true;
            break;
        }
    }
    auditAssert("MIDDLEWARE", "TIMEOUT-WEB-REDIRECT", "Inactivity timeout (>900s) redirects web user to /login?timeout=1", $hasTimeoutRedirect, "Redirect header mismatch: " . json_encode($headersWeb));
    auditAssert("MIDDLEWARE", "TIMEOUT-WEB-CLEANUP", "Inactivity timeout clears active session data", ($resTimeoutWeb['meta']['session_id_exists'] ?? true) === false, "Session was not destroyed on timeout");

    // Verify SESSION_TIMEOUT was logged in audit_logs
    $stmtTimeoutLog = $pdo->query("SELECT * FROM audit_logs WHERE action = 'SESSION_TIMEOUT' ORDER BY id DESC LIMIT 1");
    $lastTimeoutLog = $stmtTimeoutLog->fetch();
    auditAssert("MIDDLEWARE", "TIMEOUT-AUDITLOG", "Inactivity timeout creates SESSION_TIMEOUT entry in AuditLog", $lastTimeoutLog !== false && strpos($lastTimeoutLog['details'], 'Session expired due to inactivity') !== false, "Audit log missing for session timeout");

    // AJAX request: idle for 950 seconds
    $resTimeoutAjax = runMiddlewareSubprocess(
        ['user_id' => 1, 'username' => 'admin', 'user_role' => 'admin', 'last_activity' => time() - 950],
        ['HTTP_X_REQUESTED_WITH' => 'xmlhttprequest']
    );
    $isAjax401 = ($resTimeoutAjax['meta']['exit_code'] ?? 0) === 401;
    $hasTimeoutJson = strpos($resTimeoutAjax['body'], 'Session Timeout') !== false;
    auditAssert("MIDDLEWARE", "TIMEOUT-AJAX-401", "Inactivity timeout on AJAX request returns HTTP 401 code", $isAjax401, "Expected HTTP 401, got: " . ($resTimeoutAjax['meta']['exit_code'] ?? 0));
    auditAssert("MIDDLEWARE", "TIMEOUT-AJAX-JSON", "Inactivity timeout on AJAX request returns JSON timeout message", $hasTimeoutJson, "JSON output mismatch: " . $resTimeoutAjax['body']);

    // Active session (idle for 850 seconds, <= 900s)
    $resActive = runMiddlewareSubprocess(
        ['user_id' => 1, 'username' => 'admin', 'user_role' => 'admin', 'last_activity' => time() - 850],
        []
    );
    auditAssert("MIDDLEWARE", "ACTIVE-ALLOW", "Active session within 900s window is not timed out and passes middleware", strpos($resActive['body'], 'HANDLE_RETURNED:TRUE') !== false, "Active session was incorrectly blocked");
    auditAssert("MIDDLEWARE", "ACTIVE-REFRESH", "Active session refreshes last_activity timestamp", abs(($resActive['meta']['last_activity'] ?? 0) - time()) <= 2, "last_activity was not updated to current time");

    // 3.2: Unauthenticated access
    echo "\n  [3.2: Unauthenticated Access Enforcement]\n";

    // Web unauthenticated
    $resUnauthWeb = runMiddlewareSubprocess([], []);
    $headersUnauth = $resUnauthWeb['meta']['headers'] ?? [];
    $hasLoginRedirect = false;
    foreach ($headersUnauth as $h) {
        if (strpos($h, '/login') !== false && strpos($h, 'timeout=1') === false) {
            $hasLoginRedirect = true;
            break;
        }
    }
    auditAssert("MIDDLEWARE", "UNAUTH-WEB-REDIRECT", "Unauthenticated web access redirects to /login", $hasLoginRedirect, "Headers mismatch: " . json_encode($headersUnauth));

    // AJAX unauthenticated
    $resUnauthAjax = runMiddlewareSubprocess([], ['HTTP_X_REQUESTED_WITH' => 'xmlhttprequest']);
    $isUnauth401 = ($resUnauthAjax['meta']['exit_code'] ?? 0) === 401;
    $hasUnauthJson = strpos($resUnauthAjax['body'], 'Unauthenticated') !== false;
    auditAssert("MIDDLEWARE", "UNAUTH-AJAX-401", "Unauthenticated AJAX access returns HTTP 401", $isUnauth401, "Expected HTTP 401, got: " . ($resUnauthAjax['meta']['exit_code'] ?? 0));
    auditAssert("MIDDLEWARE", "UNAUTH-AJAX-JSON", "Unauthenticated AJAX access returns JSON Unauthenticated message", $hasUnauthJson, "JSON output mismatch: " . $resUnauthAjax['body']);

    // 3.3: Role-based authorization (Staff vs Admin)
    echo "\n  [3.3: RBAC Enforcement (Staff vs Admin on /users)]\n";

    // Staff web access
    $resStaffWeb = runMiddlewareSubprocess(
        ['user_id' => $targetUserId, 'username' => 'qa_audit_target', 'user_role' => 'staff', 'last_activity' => time()],
        []
    );
    $headersStaff = $resStaffWeb['meta']['headers'] ?? [];
    $hasDashboardRedirect = false;
    foreach ($headersStaff as $h) {
        if (strpos($h, '/dashboard') !== false) {
            $hasDashboardRedirect = true;
            break;
        }
    }
    auditAssert("MIDDLEWARE", "RBAC-STAFF-WEB", "Non-admin authenticated staff is redirected to /dashboard", $hasDashboardRedirect, "Headers mismatch: " . json_encode($headersStaff));

    // Staff AJAX access
    $resStaffAjax = runMiddlewareSubprocess(
        ['user_id' => $targetUserId, 'username' => 'qa_audit_target', 'user_role' => 'staff', 'last_activity' => time()],
        ['HTTP_X_REQUESTED_WITH' => 'xmlhttprequest']
    );
    $isStaff403 = ($resStaffAjax['meta']['exit_code'] ?? 0) === 403;
    $hasForbiddenJson = strpos($resStaffAjax['body'], 'Forbidden') !== false;
    auditAssert("MIDDLEWARE", "RBAC-STAFF-AJAX-403", "Non-admin authenticated staff AJAX request returns HTTP 403", $isStaff403, "Expected HTTP 403, got: " . ($resStaffAjax['meta']['exit_code'] ?? 0));
    auditAssert("MIDDLEWARE", "RBAC-STAFF-AJAX-JSON", "Non-admin authenticated staff AJAX request returns Forbidden JSON", $hasForbiddenJson, "JSON output mismatch: " . $resStaffAjax['body']);

    // Admin authenticated access
    $resAdmin = runMiddlewareSubprocess(
        ['user_id' => 1, 'username' => 'admin', 'user_role' => 'admin', 'last_activity' => time()],
        []
    );
    auditAssert("MIDDLEWARE", "RBAC-ADMIN-ALLOW", "Authenticated admin user successfully passes AdminMiddleware", strpos($resAdmin['body'], 'HANDLE_RETURNED:TRUE') !== false, "Admin user blocked by middleware");

    // =============================================================
    // 4. SESSION SYNCHRONIZATION
    // =============================================================
    echo "\n--- 4. SESSION SYNCHRONIZATION AUDIT ---\n";

    // 4.1: Administrator updating their own account in UserController::update()
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'admin';
    $_SESSION['user_fullname'] = 'Original Admin Name';
    $_SESSION['csrf_token'] = 'session_sync_token_7761';
    $_POST = [
        'csrf_token' => 'session_sync_token_7761',
        'first_name' => 'Renzo Synchronized',
        'last_name' => 'Administrator',
        'role' => 'admin',
        'email' => 'admin.sync@sinalhan.test',
        'contact_no' => '09179998877'
    ];

    $ctrl->update(1);
    auditAssert("SESSION_SYNC", "SYNC-SELF", "Updating logged-in administrator immediately syncs \$_SESSION['user_fullname']", $_SESSION['user_fullname'] === 'Renzo Synchronized Administrator', "Session fullname was not updated. Current: " . ($_SESSION['user_fullname'] ?? 'null'));

    // 4.2: Administrator updating ANOTHER user's account must NOT overwrite $_SESSION['user_fullname']
    $_POST = [
        'csrf_token' => 'session_sync_token_7761',
        'first_name' => 'OtherUserFirst',
        'last_name' => 'OtherUserLast',
        'role' => 'staff',
        'email' => 'other.user@sinalhan.test',
        'contact_no' => '09171113344'
    ];
    $ctrl->update($targetUserId);
    auditAssert("SESSION_SYNC", "SYNC-OTHER-PRESERVE", "Updating a different user does not mutate current administrator's \$_SESSION['user_fullname']", $_SESSION['user_fullname'] === 'Renzo Synchronized Administrator', "Administrator session fullname was erroneously overwritten");

    // =============================================================
    // 5. MVC INTEGRITY & UX INPUT RETENTION
    // =============================================================
    echo "\n--- 5. MVC INTEGRITY & UX INPUT RETENTION AUDIT ---\n";

    // 5.1: MVC Integrity: index.php contains 0 instances of `new \App\Models\User()`
    $indexViewPath = dirname(__DIR__) . '/app/Views/users/index.php';
    $indexViewContent = file_get_contents($indexViewPath);
    $modelInstantiations = substr_count($indexViewContent, 'new \App\Models\User()') + substr_count($indexViewContent, 'new User()');
    auditAssert("MVC", "MVC-NO-DIRECT-MODEL", "app/Views/users/index.php contains 0 instances of User model instantiation", $modelInstantiations === 0, "Found direct User model instantiation in index.php");
    auditAssert("MVC", "MVC-PRECOMPUTED-LOCKOUT", "app/Views/users/index.php accesses precomputed lockout_info from controller", strpos($indexViewContent, "lockout_info") !== false, "View does not reference controller precomputed lockout_info");

    // 5.2: UX Input Retention: update() validation failure retains input and renders in edit.php
    $_SESSION['old_input'] = [];
    $_SESSION['form_errors'] = [];
    $_POST = [
        'csrf_token' => $_SESSION['csrf_token'],
        'first_name' => '', // missing required first_name
        'middle_name' => 'RetainedMiddle',
        'last_name' => 'RetainedLastName',
        'email' => 'bad-email-format', // invalid email
        'contact_no' => '09170001122',
        'job_title' => 'Senior Nurse',
        'employee_id' => 'EMP-RET-999',
        'department' => 'Maternal & Child Health'
    ];

    $ctrl->update($targetUserId);
    auditAssert("UX_RETENTION", "OLD-INPUT-SAVED", "Validation failure in update() preserves submitted data in \$_SESSION['old_input']", isset($_SESSION['old_input']['employee_id']) && $_SESSION['old_input']['employee_id'] === 'EMP-RET-999', "old_input was not populated in session");
    auditAssert("UX_RETENTION", "FORM-ERRORS-SAVED", "Validation failure populates \$_SESSION['form_errors']", !empty($_SESSION['form_errors']), "form_errors was empty after validation failure");

    // Render edit.php with the flashed session data
    $editViewPath = dirname(__DIR__) . '/app/Views/users/edit.php';
    $user = $userModel->findById($targetUserId);
    $title = 'Edit User Account';
    $disable_layout = true; // render edit form content cleanly

    ob_start();
    require $editViewPath;
    $renderedHtml = ob_get_clean();

    auditAssert("UX_RETENTION", "RENDER-RETAINED-VAL", "app/Views/users/edit.php renders retained value from old_input (EMP-RET-999)", strpos($renderedHtml, 'value="EMP-RET-999"') !== false, "Retained employee_id not rendered in edit form");
    auditAssert("UX_RETENTION", "RENDER-RETAINED-EMAIL", "app/Views/users/edit.php renders retained invalid email value", strpos($renderedHtml, 'value="bad-email-format"') !== false, "Retained email not rendered in edit form");
    auditAssert("UX_RETENTION", "RENDER-ERROR-BANNER", "app/Views/users/edit.php displays error alert banner", strpos($renderedHtml, 'Please correct the following issues:') !== false, "Error banner missing from rendered view");
    auditAssert("UX_RETENTION", "FLASH-CLEANUP", "app/Views/users/edit.php unsets \$_SESSION['old_input'] and form_errors after rendering", !isset($_SESSION['old_input']) && !isset($_SESSION['form_errors']), "Session flash variables were not unset after rendering");

    // =============================================================
    // 6. REGRESSION VERIFICATION
    // =============================================================
    echo "\n--- 6. REGRESSION VERIFICATION (LISTING, LOCKOUT, PROFILE) ---\n";

    // 6.1: User Listing with Filters
    echo "\n  [6.1: User Listing Functionality & Filters]\n";
    $_GET['search'] = 'qa_audit';
    $_GET['role'] = '';
    $_GET['status'] = '';
    $ctrl->index();
    auditAssert("REGRESSION", "LISTING-INDEX-VIEW", "UserController::index renders users/index view", $ctrl->renderedView === 'users/index');
    $listedUsers = $ctrl->viewParams['users'] ?? [];
    auditAssert("REGRESSION", "LISTING-FILTER-SEARCH", "Filtering by search keyword returns matching users", count($listedUsers) >= 1);
    $hasLockoutInfoOnAll = true;
    foreach ($listedUsers as $lu) {
        if (!isset($lu['lockout_info']) || !is_array($lu['lockout_info']) || !isset($lu['lockout_info']['is_locked'])) {
            $hasLockoutInfoOnAll = false;
            break;
        }
    }
    auditAssert("REGRESSION", "LISTING-LOCKOUT-ATTACHED", "Every listed user has lockout_info attached by controller", $hasLockoutInfoOnAll, "lockout_info missing on listed user");

    // 6.2: Login Lockout Cycle via AuthController & User::isLockedOut
    echo "\n  [6.2: Login Lockout Cycle & scripts/unlock_user.php CLI Utility]\n";
    $authCtrl = new AuditedAuthController();
    $lockTestUser = 'qa_audit_lock_test';
    $pdo->prepare("
        INSERT INTO users (username, password_hash, role, first_name, last_name, status, failed_attempts)
        VALUES (:u, :p, 'staff', 'Lock', 'Tester', 'active', 0)
    ")->execute(['u' => $lockTestUser, 'p' => $staffPassHash]);

    // Perform 4 failed logins
    for ($i = 1; $i <= 4; $i++) {
        $_POST = ['username' => $lockTestUser, 'password' => 'WrongPassword!'];
        $authCtrl->login();
    }
    $uLock4 = $userModel->findByUsername($lockTestUser);
    auditAssert("REGRESSION", "LOCKOUT-ATTEMPTS-4", "4 failed attempts recorded in database", (int)$uLock4['failed_attempts'] === 4);
    $lockInfo4 = $userModel->isLockedOut($uLock4);
    auditAssert("REGRESSION", "LOCKOUT-NOT-LOCKED-YET", "Account is not locked out at 4 attempts", $lockInfo4['is_locked'] === false);

    // 5th failed login triggers 15-minute temporary lockout
    $_POST = ['username' => $lockTestUser, 'password' => 'WrongPassword!'];
    $authCtrl->login();
    $uLock5 = $userModel->findByUsername($lockTestUser);
    auditAssert("REGRESSION", "LOCKOUT-ATTEMPTS-5", "5th failed attempt increments failed_attempts to 5", (int)$uLock5['failed_attempts'] === 5);
    $lockInfo5 = $userModel->isLockedOut($uLock5);
    auditAssert("REGRESSION", "LOCKOUT-ACTIVATED", "Account is now locked out for 15 minutes (isLockedOut = true)", $lockInfo5['is_locked'] === true && $lockInfo5['remaining_seconds'] > 850);

    // Blocked login attempt during lockout
    $_POST = ['username' => $lockTestUser, 'password' => $staffPassRaw]; // correct password during lockout
    $authCtrl->login();
    $stmtBlockLog = $pdo->query("SELECT * FROM audit_logs WHERE action = 'LOGIN_BLOCKED_LOCKOUT' ORDER BY id DESC LIMIT 1");
    $blockLog = $stmtBlockLog->fetch();
    auditAssert("REGRESSION", "LOCKOUT-LOGIN-BLOCKED", "Login blocked during lockout even with correct credentials", $blockLog !== false && strpos($blockLog['details'], $lockTestUser) !== false);

    // Test unlock via CLI script: scripts/unlock_user.php
    exec(escapeshellcmd(PHP_BINARY) . ' scripts/unlock_user.php ' . escapeshellarg($lockTestUser), $unlockOut, $unlockCode);
    $uUnlocked = $userModel->findByUsername($lockTestUser);
    auditAssert("REGRESSION", "LOCKOUT-CLI-UNLOCK", "scripts/unlock_user.php unlocks user and resets failed_attempts to 0", $unlockCode === 0 && (int)$uUnlocked['failed_attempts'] === 0 && $uUnlocked['last_failed_login_at'] === null);

    // Successful login following unlock
    $_POST = ['username' => $lockTestUser, 'password' => $staffPassRaw];
    $authCtrl->login();
    auditAssert("REGRESSION", "LOCKOUT-POST-UNLOCK-LOGIN", "User can authenticate successfully after unlock override", ($_SESSION['user_id'] ?? null) == $uUnlocked['id']);

    // 6.3: Profile Module (Self-Service Profile & Password Update)
    echo "\n  [6.3: Profile Module (ProfileController)]\n";
    $_SESSION['user_id'] = $targetUserId;
    $_SESSION['user_role'] = 'staff';
    $_SESSION['username'] = 'qa_audit_target';
    $_SESSION['user_fullname'] = 'Original Profile Name';
    $_SESSION['csrf_token'] = 'profile_test_token_5521';

    $profCtrl = new AuditedProfileController();

    // Profile details update
    $_POST = [
        'csrf_token' => 'profile_test_token_5521',
        'first_name' => 'UpdatedProfFirst',
        'middle_name' => 'M',
        'last_name' => 'UpdatedProfLast',
        'email' => 'updated.profile@sinalhan.test',
        'contact_no' => '09178889900'
    ];
    $profCtrl->update();
    $targetDbUpdated = $userModel->findById($targetUserId);
    auditAssert("REGRESSION", "PROFILE-UPDATE-DB", "ProfileController updates self-service personal information in DB", $targetDbUpdated['first_name'] === 'UpdatedProfFirst', "Profile first_name not updated in DB");
    auditAssert("REGRESSION", "PROFILE-SYNC-SESSION", "ProfileController updates \$_SESSION['user_fullname'] in real time", $_SESSION['user_fullname'] === 'UpdatedProfFirst UpdatedProfLast', "Profile fullname not synced in session");

    // Profile voluntary password change
    $_POST = [
        'csrf_token' => 'profile_test_token_5521',
        'current_password' => 'NewTempPass2026!', // set during resetPassword test
        'new_password' => 'MyNewVoluntaryPassword2026!',
        'confirm_password' => 'MyNewVoluntaryPassword2026!'
    ];
    $profCtrl->updatePassword();
    $targetDbPass = $userModel->findById($targetUserId);
    auditAssert("REGRESSION", "PROFILE-PASS-CHANGED", "ProfileController updates password hash and sets must_change_password to 0", password_verify('MyNewVoluntaryPassword2026!', $targetDbPass['password_hash']) && (int)$targetDbPass['must_change_password'] === 0, "Password hash not updated via ProfileController");

} finally {
    // =============================================================
    // TEARDOWN & DATABASE RESTORATION
    // =============================================================
    echo "\n====================================================================\n";
    echo "   CLEANUP & TEARDOWN: RESTORING DATABASE INTEGRITY                 \n";
    echo "====================================================================\n";

    // Clean all test users created during audit
    $pdo->prepare("DELETE FROM users WHERE username LIKE 'qa_audit_%' OR username LIKE 'val_%'")->execute();
    $pdo->prepare("DELETE FROM audit_logs WHERE username LIKE 'qa_audit_%' OR username LIKE 'val_%'")->execute();

    // Clean any session timeout log created during test
    $pdo->prepare("DELETE FROM audit_logs WHERE action = 'SESSION_TIMEOUT' AND details LIKE '%admin%'")->execute();

    // Restore User 1 original attributes
    if ($originalUser1) {
        $pdo->prepare("
            UPDATE users 
            SET first_name = :fn, 
                last_name = :ln, 
                email = :em, 
                contact_no = :cn, 
                password_hash = :ph,
                role = :r,
                status = :st
            WHERE id = 1
        ")->execute([
            'fn' => $originalUser1['first_name'],
            'ln' => $originalUser1['last_name'],
            'em' => $originalUser1['email'],
            'cn' => $originalUser1['contact_no'],
            'ph' => $originalUser1['password_hash'],
            'r' => $originalUser1['role'],
            'st' => $originalUser1['status']
        ]);
        echo "  [INFO] Primary Administrator (User ID 1) restored to pristine snapshot state.\n";
    }

    echo "  [INFO] Temporary QA test users and audit logs purged.\n";
}

echo "\n====================================================================\n";
echo "AUDIT SUMMARY: {$passCount} PASSED, {$failCount} FAILED.\n";
echo "====================================================================\n";

if ($failCount > 0) {
    echo "\nFAILURE BREAKDOWN:\n";
    foreach ($failures as $f) {
        echo "  - {$f}\n";
    }
    exit(1);
}

exit(0);
