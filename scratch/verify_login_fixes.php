<?php
/**
 * Barangay Sinalhan Health Center - Login Fixes and Throttling Verification Test
 */

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();

// Mock simple environment for the CLI script
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$config = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// We will use a temporary test user "test_lockout_user"
$testUsername = 'test_lockout_user';

// Clean up any old test user
$pdo->prepare("DELETE FROM audit_logs WHERE username = :username OR details LIKE :like_username")->execute([
    'username' => $testUsername,
    'like_username' => "%{$testUsername}%"
]);
$pdo->prepare("DELETE FROM users WHERE username = :username")->execute(['username' => $testUsername]);

// Insert fresh test user
$passwordHash = password_hash('testpassword123', PASSWORD_BCRYPT);
$pdo->prepare("
    INSERT INTO users (username, password_hash, role, first_name, last_name, status, failed_attempts, must_change_password)
    VALUES (:username, :hash, 'staff', 'Test', 'Lockout', 'active', 0, 0)
")->execute(['username' => $testUsername, 'hash' => $passwordHash]);

$userModel = new App\Models\User();

echo "--- STARTING VERIFICATION TESTS ---\n";

// Test 1: First failed attempt increments to 1
echo "Test 1: Incrementing first failed attempt... ";
$count = $userModel->incrementFailedAttempts($testUsername);
if ($count === 1) {
    echo "PASS (Count: 1)\n";
} else {
    echo "FAIL (Count: {$count})\n";
}

// Test 2: Verify database record fields
echo "Test 2: Verifying last_failed_login_at and failed_attempts in DB... ";
$stmt = $pdo->prepare("SELECT failed_attempts, last_failed_login_at, status FROM users WHERE username = :username");
$stmt->execute(['username' => $testUsername]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ((int)$row['failed_attempts'] === 1 && !empty($row['last_failed_login_at'])) {
    echo "PASS (Attempts: {$row['failed_attempts']}, Time: {$row['last_failed_login_at']}, Status: {$row['status']})\n";
} else {
    echo "FAIL\n";
}

// Test 3: Simulate 15-minute window expiration by setting last_failed_login_at to 20 minutes ago
echo "Test 3: Simulating 20 minutes elapsed since last failure... ";
$pdo->prepare("UPDATE users SET last_failed_login_at = NOW() - INTERVAL 20 MINUTE WHERE username = :username")->execute(['username' => $testUsername]);
// Next attempt should reset count to 1 instead of incrementing to 2!
$count = $userModel->incrementFailedAttempts($testUsername);
if ($count === 1) {
    echo "PASS (Successfully reset and set to 1)\n";
} else {
    echo "FAIL (Count: {$count}, expected 1)\n";
}

// Test 4: Accumulate up to lockout (attempts 2, 3, 4, 5)
echo "Test 4: Simulating consecutive failed attempts to trigger suspension... ";
$count = $userModel->incrementFailedAttempts($testUsername); // 2
$count = $userModel->incrementFailedAttempts($testUsername); // 3
$count = $userModel->incrementFailedAttempts($testUsername); // 4
$count = $userModel->incrementFailedAttempts($testUsername); // 5

$stmt = $pdo->prepare("SELECT failed_attempts, status FROM users WHERE username = :username");
$stmt->execute(['username' => $testUsername]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($count === 5 && $row['status'] === 'suspended') {
    echo "PASS (User suspended after 5 attempts! Count: {$row['failed_attempts']}, Status: {$row['status']})\n";
} else {
    echo "FAIL (Count: {$count}, Status: {$row['status']})\n";
}

// Test 5: Verify CLI unlock script
echo "Test 5: Testing scripts/unlock_user.php tool... \n";
$output = [];
$returnVar = 0;
exec("php scripts/unlock_user.php {$testUsername}", $output, $returnVar);

echo "CLI Output:\n" . implode("\n", $output) . "\n";

$stmt = $pdo->prepare("SELECT failed_attempts, last_failed_login_at, status FROM users WHERE username = :username");
$stmt->execute(['username' => $testUsername]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($returnVar === 0 && $row['status'] === 'active' && (int)$row['failed_attempts'] === 0 && is_null($row['last_failed_login_at'])) {
    echo "Test 5 RESULT: PASS (User status is active, failed attempts reset to 0, last_failed_login_at cleared!)\n";
} else {
    echo "Test 5 RESULT: FAIL (Status: {$row['status']}, Count: {$row['failed_attempts']}, Time: {$row['last_failed_login_at']})\n";
}

// Clean up test user
$pdo->prepare("DELETE FROM audit_logs WHERE username = :username OR details LIKE :like_username")->execute([
    'username' => $testUsername,
    'like_username' => "%{$testUsername}%"
]);
$pdo->prepare("DELETE FROM users WHERE username = :username")->execute(['username' => $testUsername]);
echo "--- VERIFICATION COMPLETED AND CLEANED UP ---\n";
