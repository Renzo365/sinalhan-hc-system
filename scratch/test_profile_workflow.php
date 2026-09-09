<?php

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/../app/helpers.php';

$config = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "=== STARTING PROFILE & TOPBAR DROPDOWN WORKFLOW TEST ===\n\n";

$testUsername = 'test_profile_user';

// 1. Cleanup old test data
$pdo->prepare("DELETE FROM audit_logs WHERE username = :u OR details LIKE :lu")->execute([
    'u' => $testUsername,
    'lu' => "%{$testUsername}%"
]);
$pdo->prepare("DELETE FROM users WHERE username = :u")->execute(['u' => $testUsername]);

// 2. Insert test user with role 'staff'
$initialPass = 'OriginalPass123!';
$initialHash = password_hash($initialPass, PASSWORD_BCRYPT);
$insertStmt = $pdo->prepare("
    INSERT INTO users (username, password_hash, role, first_name, middle_name, last_name, email, contact_no, status, must_change_password)
    VALUES (:username, :password_hash, 'staff', 'Maria', 'Santos', 'Reyes', 'maria.orig@example.com', '09170000001', 'active', 0)
");
$insertStmt->execute([
    'username' => $testUsername,
    'password_hash' => $initialHash
]);
$testUserId = (int)$pdo->lastInsertId();

echo "[TEST 1] Inserted test user ID {$testUserId} (role: staff, name: Maria Santos Reyes)\n";

$userModel = new \App\Models\User();

// 3. Test updateProfile
$updateData = [
    'first_name' => 'Maria Elena',
    'middle_name' => 'Cruz',
    'last_name' => 'Del Rosario',
    'email' => 'maria.new@example.com',
    'contact_no' => '09181112222',
    // Malicious attempts to escalate privileges:
    'role' => 'admin',
    'status' => 'inactive',
    'username' => 'hacked_admin',
    'employee_id' => 'EMP-999'
];

$res = $userModel->updateProfile($testUserId, $updateData);
assert($res === true, "updateProfile failed");

$updatedUser = $userModel->findById($testUserId);

// Verify allowed fields were updated
assert($updatedUser['first_name'] === 'Maria Elena', "first_name not updated");
assert($updatedUser['middle_name'] === 'Cruz', "middle_name not updated");
assert($updatedUser['last_name'] === 'Del Rosario', "last_name not updated");
assert($updatedUser['email'] === 'maria.new@example.com', "email not updated");
assert($updatedUser['contact_no'] === '09181112222', "contact_no not updated");

// Verify privilege escalation was impossible
assert($updatedUser['role'] === 'staff', "SECURITY ERROR: role was modified via updateProfile!");
assert($updatedUser['status'] === 'active', "SECURITY ERROR: status was modified via updateProfile!");
assert($updatedUser['username'] === $testUsername, "SECURITY ERROR: username was modified via updateProfile!");

echo "  -> PASS: updateProfile updated contact/name details successfully.\n";
echo "  -> PASS: Privilege escalation guard confirmed. Unauthorized fields (role, status, username) completely ignored.\n\n";

// 4. Test Email uniqueness
$isUniqueOwn = $userModel->isEmailUnique('maria.new@example.com', $testUserId);
assert($isUniqueOwn === true, "isEmailUnique should allow current user's own email");
$isUniqueTaken = $userModel->isEmailUnique('maria.new@example.com', 999999);
assert($isUniqueTaken === false, "isEmailUnique should reject email if taken by another user");
echo "[TEST 2] Email uniqueness check:\n";
echo "  -> PASS: Own email allowed when excluding user ID.\n";
echo "  -> PASS: Conflict detected when checking for other users.\n\n";

// 5. Test updatePassword
echo "[TEST 3] Password update workflow:\n";
$newPassword = 'BrandNewPassword2026!';
$newHash = password_hash($newPassword, PASSWORD_BCRYPT);
$passRes = $userModel->updatePassword($testUserId, $newHash, 0);
assert($passRes === true, "updatePassword failed");

$reloadedUser = $userModel->findById($testUserId);
assert(password_verify($newPassword, $reloadedUser['password_hash']), "password_verify with new password failed");
assert(!password_verify($initialPass, $reloadedUser['password_hash']), "old password still valid after change");
assert((int)$reloadedUser['must_change_password'] === 0, "must_change_password should be 0");
assert((int)$reloadedUser['failed_attempts'] === 0, "failed_attempts should be 0");
echo "  -> PASS: updatePassword updated hash and reset security counters.\n\n";

// 6. Test AuditLog logging
echo "[TEST 4] AuditLog parameter compatibility:\n";
$_SESSION['user_id'] = $testUserId;
$_SESSION['username'] = $testUsername;
$log1 = \App\Models\AuditLog::log('PROFILE_UPDATED', 'users', $testUserId, 'Updated personal profile contact details');
$log2 = \App\Models\AuditLog::log('USER_PASSWORD_CHANGED', 'users', 'Voluntarily updated account password');

assert($log1 === true, "AuditLog 4-param call failed");
assert($log2 === true, "AuditLog 3-param call failed");

$checkLogs = $pdo->prepare("SELECT action, details FROM audit_logs WHERE user_id = :uid ORDER BY id DESC LIMIT 2");
$checkLogs->execute(['uid' => $testUserId]);
$logs = $checkLogs->fetchAll(PDO::FETCH_ASSOC);

assert(count($logs) === 2, "Expected 2 audit log records");
assert($logs[0]['action'] === 'USER_PASSWORD_CHANGED', "Expected USER_PASSWORD_CHANGED log");
assert($logs[1]['action'] === 'PROFILE_UPDATED', "Expected PROFILE_UPDATED log");
echo "  -> PASS: AuditLog handles both 3-param and 4-param calls cleanly and accurately.\n\n";

// 7. Route resolution verification
echo "[TEST 5] Routes registration verification:\n";
$router = new \App\Core\Router();
$registerRoutes = require __DIR__ . '/../config/routes.php';
$registerRoutes($router);

// Inspect router registered routes via reflection
$reflection = new ReflectionClass($router);
$routesProp = $reflection->getProperty('routes');
$routesProp->setAccessible(true);
$allRoutes = $routesProp->getValue($router);

$profileRoutesFound = [
    'GET /profile' => false,
    'POST /profile/update' => false,
    'POST /profile/password' => false
];

foreach ($allRoutes as $route) {
    $sig = $route['method'] . ' ' . $route['route'];
    if (isset($profileRoutesFound[$sig])) {
        $profileRoutesFound[$sig] = true;
        // Verify middleware contains AuthMiddleware
        $hasAuth = in_array(\App\Middleware\AuthMiddleware::class, $route['middlewares'] ?? []);
        assert($hasAuth, "Route {$sig} is missing AuthMiddleware!");
    }
}

foreach ($profileRoutesFound as $sig => $found) {
    assert($found, "Route {$sig} was not found in registered routes!");
    echo "  -> PASS: Route {$sig} registered with AuthMiddleware.\n";
}

// 8. Cleanup test user
$pdo->prepare("DELETE FROM audit_logs WHERE user_id = :u")->execute(['u' => $testUserId]);
$pdo->prepare("DELETE FROM users WHERE id = :u")->execute(['u' => $testUserId]);
echo "\nCleaned up test user record ID {$testUserId}.\n";

echo "\n=== ALL VERIFICATION TESTS PASSED SUCCESSFULLY! ===\n";
