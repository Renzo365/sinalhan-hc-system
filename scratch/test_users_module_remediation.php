<?php
/**
 * Comprehensive Automated Regression Test Suite for Users & Profile System Remediation.
 * 
 * Tests:
 * 1. User Creation & Password Confirmation Validation
 * 2. Contact Number Normalization & Sanitization
 * 3. Self-Service Job Title Updates in Profile
 * 4. Administrator Password Reset Validation & Safety
 * 5. Domain Scope Cleanliness (No orphaned employee_id or department dependencies)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Validators/BaseValidator.php';
require_once __DIR__ . '/../app/Validators/UserValidator.php';

use App\Models\User;
use App\Validators\UserValidator;

$db = \App\Core\Database::getInstance()->getConnection();
$userModel = new User();
$validator = new UserValidator($userModel);

$passed = 0;
$failed = 0;

function assertTest($condition, $message) {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] " . $message . "\n";
        $passed++;
    } else {
        echo "  [FAIL] " . $message . "\n";
        $failed++;
    }
}

echo "======================================================\n";
echo " Running Users & Profile End-to-End Test Suite\n";
echo "======================================================\n\n";

// --- TEST SUITE 1: Create User & Password Confirmation ---
echo "1. Testing Create User & Password Confirmation:\n";
$invalidMismatch = [
    'username' => 'n_' . substr((string)time(), -8) . '1',
    'password' => 'SecurePass123',
    'confirm_password' => 'DifferentPass456',
    'first_name' => 'Maria',
    'last_name' => 'Santos'
];
$errsMismatch = $validator->validateCreate($invalidMismatch);
assertTest(in_array('Initial password and confirmation password do not match.', $errsMismatch), 'Rejects mismatched confirm_password');

$invalidEmptyConfirm = [
    'username' => 'n_' . substr((string)time(), -8) . '2',
    'password' => 'SecurePass123',
    'confirm_password' => '',
    'first_name' => 'Maria',
    'last_name' => 'Santos'
];
$errsEmptyConfirm = $validator->validateCreate($invalidEmptyConfirm);
assertTest(in_array('Please confirm the initial password.', $errsEmptyConfirm), 'Rejects empty confirm_password');

$validData = [
    'username' => 'n_' . substr((string)time(), -8) . '3',
    'password' => 'SecurePass123',
    'confirm_password' => 'SecurePass123',
    'first_name' => 'Maria',
    'last_name' => 'Santos'
];
$errsValid = $validator->validateCreate($validData);
assertTest(empty($errsValid), 'Valid create data with matching password passes');

// --- TEST SUITE 2: Phone Number Normalization ---
echo "\n2. Testing Contact Number Sanitization:\n";
assertTest(UserValidator::sanitizePhone('0917-123-4567') === '09171234567', 'Dashed phone 0917-123-4567 -> 09171234567');
assertTest(UserValidator::sanitizePhone('0917 123 4567') === '09171234567', 'Spaced phone 0917 123 4567 -> 09171234567');
assertTest(UserValidator::sanitizePhone('+639171234567') === '09171234567', 'Intl phone +639171234567 -> 09171234567');
assertTest(UserValidator::sanitizePhone('(0917) 123-4567') === '09171234567', 'Parenthesized phone (0917) 123-4567 -> 09171234567');

// --- TEST SUITE 3: Self-Service Job Title in Profile ---
echo "\n3. Testing Self-Service Job Title in User Profile:\n";
$targetUser = $userModel->findByUsername('midwife_user') ?: $userModel->findById(1);
if ($targetUser) {
    $userId = $targetUser['id'];
    $origJob = $targetUser['job_title'];
    $origContact = $targetUser['contact_no'];

    $updated = $userModel->updateProfile($userId, [
        'first_name' => $targetUser['first_name'],
        'middle_name' => $targetUser['middle_name'],
        'last_name' => $targetUser['last_name'],
        'email' => $targetUser['email'],
        'contact_no' => '09178889999',
        'job_title' => 'Chief Health Officer / Midwife Lead'
    ]);
    assertTest($updated === true, 'Profile update executed successfully');

    $refetched = $userModel->findById($userId);
    assertTest($refetched['job_title'] === 'Chief Health Officer / Midwife Lead', 'Persisted clinical job_title successfully');
    assertTest($refetched['contact_no'] === '09178889999', 'Persisted contact_no successfully');

    // Restore original values
    $userModel->updateProfile($userId, [
        'first_name' => $targetUser['first_name'],
        'middle_name' => $targetUser['middle_name'],
        'last_name' => $targetUser['last_name'],
        'email' => $targetUser['email'],
        'contact_no' => $origContact,
        'job_title' => $origJob
    ]);
    $restored = $userModel->findById($userId);
    assertTest($restored['job_title'] === $origJob, 'Restored original clinical job_title');
}

// --- TEST SUITE 4: Admin Password Reset Security ---
echo "\n4. Testing Administrator Password Reset Security:\n";
$adminUser = $userModel->findByUsername('admin');
if ($adminUser && $targetUser) {
    $shortPasswordData = [
        'admin_password' => 'admin1234',
        'new_password' => 'short',
        'confirm_password' => 'short'
    ];
    $errsShort = $validator->validatePasswordReset($targetUser, $shortPasswordData, $adminUser['id']);
    assertTest(in_array('New temporary password must be at least 8 characters long.', $errsShort), 'Rejects new password shorter than 8 characters');

    $mismatchPasswordData = [
        'admin_password' => 'admin1234',
        'new_password' => 'ValidPassword123!',
        'confirm_password' => 'DifferentPassword123!'
    ];
    $errsMismatch = $validator->validatePasswordReset($targetUser, $mismatchPasswordData, $adminUser['id']);
    assertTest(in_array('New password and confirmation password do not match.', $errsMismatch), 'Rejects mismatched reset confirmation');

    $wrongAdminData = [
        'admin_password' => 'definitely_wrong_password',
        'new_password' => 'ValidPassword123!',
        'confirm_password' => 'ValidPassword123!'
    ];
    $errsAdmin = $validator->validatePasswordReset($targetUser, $wrongAdminData, $adminUser['id']);
    assertTest(in_array('Incorrect administrator authorization password.', $errsAdmin), 'Rejects reset when administrator password is incorrect');

    // Create a known password for target user to test reuse check
    $knownHashUser = $targetUser;
    $knownHashUser['password_hash'] = password_hash('KnownExistingPass1!', PASSWORD_BCRYPT);
    $reusePasswordData = [
        'admin_password' => 'admin1234',
        'new_password' => 'KnownExistingPass1!',
        'confirm_password' => 'KnownExistingPass1!'
    ];
    $errsReuse = $validator->validatePasswordReset($knownHashUser, $reusePasswordData, $adminUser['id']);
    assertTest(in_array('New password cannot be the same as the user\'s current password. Please choose a different password.', $errsReuse), 'Rejects resetting to the user\'s existing password');
}

// --- TEST SUITE 5: Verification of Domain Scope (No required department / employee_id) ---
echo "\n5. Testing Domain Scope Cleanliness:\n";
$userCols = $db->query("DESCRIBE users")->fetchAll(\PDO::FETCH_COLUMN);
assertTest(in_array('job_title', $userCols), "Database contains clinical 'job_title' column");
assertTest(in_array('username', $userCols), "Database contains 'username' column");

$omittedDepData = [
    'username' => 't_sc_' . substr((string)time(), -8),
    'password' => 'Pass12345!',
    'confirm_password' => 'Pass12345!',
    'first_name' => 'Maria',
    'last_name' => 'Santos',
    'job_title' => 'Barangay Health Worker (BHW)'
];
$errsScope = $validator->validateCreate($omittedDepData);
assertTest(empty($errsScope), "Application validation accepts users without 'department' or 'employee_id'");

echo "\n======================================================\n";
echo " Test Results: {$passed} Passed, {$failed} Failed\n";
echo "======================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
