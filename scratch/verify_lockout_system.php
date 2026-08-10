<?php

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();

echo "--- STARTING 15-MINUTE TEMPORARY LOCKOUT TEST SUITE ---\n\n";

$userModel = new \App\Models\User();
$testUsername = 'records_staff';

// 1. Fetch records_staff user
$user = $userModel->findByUsername($testUsername);
if (!$user) {
    die("ERROR: Test user '{$testUsername}' not found.\n");
}

echo "Initial User State: {$user['username']}, Status: {$user['status']}, Failed Attempts: {$user['failed_attempts']}\n";

// 2. Reset lockout first to ensure clean test start
$userModel->clearLockout($user['id']);
$user = $userModel->findByUsername($testUsername);
echo "Cleaned State: Failed Attempts = {$user['failed_attempts']}\n\n";

// 3. Simulate 5 failed password attempts
echo "Simulating 5 consecutive failed login attempts...\n";
for ($i = 1; $i <= 5; $i++) {
    $count = $userModel->incrementFailedAttempts($testUsername);
    echo "  Attempt {$i}/5 registered. Current Count: {$count}\n";
}

// 4. Verify user state after 5 failed attempts
$user = $userModel->findByUsername($testUsername);
echo "\nPost-5-Failures User State:\n";
echo "  Status: {$user['status']} (Expected: 'active' - account should NOT be deactivated!)\n";
echo "  Failed Attempts: {$user['failed_attempts']}\n";
echo "  Last Failed Login At: {$user['last_failed_login_at']}\n";

$lockoutInfo = $userModel->isLockedOut($user);
if ($lockoutInfo['is_locked'] && $user['status'] === 'active') {
    echo "  PASS: User is correctly in 15-minute temporary lockout. Remaining: {$lockoutInfo['remaining_formatted']}\n";
} else {
    echo "  FAIL: User is NOT properly flagged as locked out or status was mutated incorrectly.\n";
    exit(1);
}

// 5. Test Clear Lockout (Admin Override)
echo "\nTesting Admin Clear Lockout (userModel->clearLockout())...\n";
$userModel->clearLockout($user['id']);

$user = $userModel->findByUsername($testUsername);
$lockoutInfo = $userModel->isLockedOut($user);

if (!$lockoutInfo['is_locked'] && $user['failed_attempts'] == 0) {
    echo "  PASS: Lockout successfully cleared! Failed attempts = 0, Account status = '{$user['status']}'.\n";
} else {
    echo "  FAIL: Lockout clear failed.\n";
    exit(1);
}

echo "\n--- ALL TEMPORARY LOCKOUT TESTS PASSED SUCCESSFULLY! ---\n";
