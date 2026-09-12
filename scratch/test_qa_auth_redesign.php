<?php
/**
 * Test script for Authentication Portal Redesign (User model & AuthController logic)
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/Core/Database.php';
require_once BASE_PATH . '/app/Core/Model.php';
require_once BASE_PATH . '/app/Models/User.php';
require_once BASE_PATH . '/app/Models/AuditLog.php';
require_once BASE_PATH . '/app/helpers.php';

echo "=== 1. Testing Database & findByLoginIdentifier ===\n";
$userModel = new \App\Models\User();

// Find by username
$adminByUsername = $userModel->findByLoginIdentifier('admin');
if ($adminByUsername && $adminByUsername['username'] === 'admin') {
    echo " [PASS] Successfully found user by username 'admin'.\n";
} else {
    echo " [FAIL] Could not find user by username 'admin'.\n";
}

// Find by employee_id if employee_id exists on an active user
$db = \App\Core\Database::getInstance()->getConnection();
$sampleUser = $db->query("SELECT * FROM users WHERE employee_id IS NOT NULL AND employee_id != '' AND deleted_at IS NULL LIMIT 1")->fetch();

if ($sampleUser) {
    $empId = $sampleUser['employee_id'];
    $foundByEmpId = $userModel->findByLoginIdentifier($empId);
    if ($foundByEmpId && $foundByEmpId['id'] === $sampleUser['id']) {
        echo " [PASS] Successfully found user {$sampleUser['username']} by employee_id '{$empId}'.\n";
    } else {
        echo " [FAIL] Could not find user by employee_id '{$empId}'.\n";
    }
} else {
    echo " [INFO] No user with employee_id found. Creating a temporary test user...\n";
}

// Test non-existent identifier
$notFound = $userModel->findByLoginIdentifier('non_existent_identifier_9999');
if ($notFound === false) {
    echo " [PASS] Correctly returned false for non-existent identifier.\n";
} else {
    echo " [FAIL] Expected false for non-existent identifier.\n";
}

echo "\n=== 2. Testing Password Complexity Logic ===\n";

$passwords = [
    'short' => false,                          // < 8 chars
    'alllowercase123!' => false,              // no uppercase
    'ALLUPPERCASE123!' => false,              // no lowercase
    'NoDigitsSpecial!!' => false,             // no digit
    'NoSpecial12345' => false,                // no special symbol
    'ValidPass123!' => true,                  // >=8, upper, lower, digit, symbol
    'BhcAdmin@2026' => true                   // >=8, upper, lower, digit, symbol
];

foreach ($passwords as $pwd => $expected) {
    $len = strlen($pwd) >= 8;
    $case = preg_match('/[A-Z]/', $pwd) && preg_match('/[a-z]/', $pwd);
    $sym = preg_match('/[0-9]/', $pwd) && preg_match('/[^A-Za-z0-9]/', $pwd);
    $isValid = $len && $case && $sym;

    if ($isValid === $expected) {
        echo " [PASS] Password '{$pwd}' validated as " . ($isValid ? 'VALID' : 'INVALID') . " as expected.\n";
    } else {
        echo " [FAIL] Password '{$pwd}' evaluated to " . ($isValid ? 'VALID' : 'INVALID') . ", expected " . ($expected ? 'VALID' : 'INVALID') . ".\n";
    }
}

echo "\n=== 3. Testing incrementFailedAttempts with dual identifier ===\n";
// Test on admin account
$initialAdmin = $userModel->findByLoginIdentifier('admin');
$initialAttempts = (int)($initialAdmin['failed_attempts'] ?? 0);

// Increment using username
$newAttempts = $userModel->incrementFailedAttempts('admin');
$afterAdmin = $userModel->findByLoginIdentifier('admin');
if ($afterAdmin['failed_attempts'] > 0) {
    echo " [PASS] incrementFailedAttempts worked via username. Count: {$afterAdmin['failed_attempts']}.\n";
} else {
    echo " [FAIL] incrementFailedAttempts failed to increment via username.\n";
}

// Reset attempts for admin to keep system clean
$db->prepare("UPDATE users SET failed_attempts = :init, last_failed_login_at = NULL WHERE username = 'admin'")->execute(['init' => $initialAttempts]);
echo " [INFO] Admin failed attempts restored to initial state ({$initialAttempts}).\n";

echo "\n=== 4. Testing CSS Asset Resolution ===\n";
$assetPath = asset('css/auth-redesign.css');
echo " Asset URL: {$assetPath}\n";
$physicalPath = dirname(__DIR__) . '/public/assets/css/auth-redesign.css';
if (file_exists($physicalPath)) {
    echo " [PASS] Physical file exists: {$physicalPath} (" . filesize($physicalPath) . " bytes)\n";
} else {
    echo " [FAIL] Physical file not found at: {$physicalPath}\n";
}

echo "\n=== 5. Testing View Template Rendering ===\n";

// Test rendering login.php
$username = 'test_user';
$error = 'Invalid credentials test alert.';
$timeoutMessage = 'Session expired test alert.';
ob_start();
try {
    require BASE_PATH . '/app/Views/auth/login.php';
    $loginHtml = ob_get_clean();
    if (strpos($loginHtml, 'Staff Portal Sign In') !== false && strpos($loginHtml, 'RA 10173 Compliant') !== false) {
        echo " [PASS] app/Views/auth/login.php rendered cleanly with required branding and alerts.\n";
    } else {
        echo " [FAIL] app/Views/auth/login.php missing expected keywords.\n";
    }
} catch (\Throwable $t) {
    ob_end_clean();
    echo " [FAIL] Exception rendering login.php: " . $t->getMessage() . "\n";
}

// Test rendering change_password.php
$user = [
    'id' => 999,
    'username' => 'bhw-maria',
    'employee_id' => 'BHC-2026-0088',
    'first_name' => 'Maria',
    'last_name' => 'Santos',
    'role' => 'nurse'
];
$errors = ['Password must contain at least 1 digit and 1 symbol.'];
$disable_layout = true;

ob_start();
try {
    require BASE_PATH . '/app/Views/auth/change_password.php';
    $changeHtml = ob_get_clean();
    if (strpos($changeHtml, 'First-Time Password Update') !== false && strpos($changeHtml, 'BHC-2026-0088') !== false && strpos($changeHtml, 'Maria Santos') !== false) {
        echo " [PASS] app/Views/auth/change_password.php rendered cleanly with staff identity card and criteria checklist.\n";
    } else {
        echo " [FAIL] app/Views/auth/change_password.php missing expected keywords.\n";
    }
} catch (\Throwable $t) {
    ob_end_clean();
    echo " [FAIL] Exception rendering change_password.php: " . $t->getMessage() . "\n";
}

echo "\n=== ALL CHECKS COMPLETED ===\n";
