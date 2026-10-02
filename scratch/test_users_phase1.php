<?php
/**
 * Test Suite: User Management & Profile Phase 1 Remediation
 * Verifies:
 * 1. Password confirmation validation on create (match, mismatch, empty)
 * 2. Contact number sanitization helper (dashes, spaces, international format)
 * 3. Profile update with self-service job_title persistence
 * 4. Password reset validation rules
 */

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Validators/BaseValidator.php';
require_once __DIR__ . '/../app/Validators/UserValidator.php';

use App\Models\User;
use App\Validators\UserValidator;

$userModel = new User();
$validator = new UserValidator($userModel);

$passed = 0;
$failed = 0;

function assertTest($condition, $name) {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$name}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$name}\n";
        $failed++;
    }
}

echo "======================================================\n";
echo " Running Users & Profile Phase 1 Test Suite\n";
echo "======================================================\n\n";

// TEST 1: Password Confirmation Enforcement
echo "1. Testing Password Confirmation on Create User:\n";
$inputMismatch = [
    'username' => 'n_' . substr((string)time(), -8) . '1',
    'password' => 'SecurePass123',
    'confirm_password' => 'DifferentPass456',
    'first_name' => 'Maria',
    'last_name' => 'Santos'
];
$errors = $validator->validateCreate($inputMismatch);
assertTest(!empty($errors) && in_array('Initial password and confirmation password do not match.', $errors), 'Rejects mismatched passwords');

$inputEmptyConfirm = [
    'username' => 'n_' . substr((string)time(), -8) . '2',
    'password' => 'SecurePass123',
    'confirm_password' => '',
    'first_name' => 'Maria',
    'last_name' => 'Santos'
];
$errors = $validator->validateCreate($inputEmptyConfirm);
assertTest(!empty($errors) && in_array('Please confirm the initial password.', $errors), 'Rejects empty confirmation password');

$inputMatching = [
    'username' => 'n_' . substr((string)time(), -8) . '3',
    'password' => 'SecurePass123',
    'confirm_password' => 'SecurePass123',
    'first_name' => 'Maria',
    'last_name' => 'Santos'
];
$errors = $validator->validateCreate($inputMatching);
assertTest(empty($errors), 'Passes when initial password and confirmation match');

// TEST 2: Phone Number Normalization & Sanitization
echo "\n2. Testing Contact Number Sanitization:\n";
assertTest(UserValidator::sanitizePhone('0917-123-4567') === '09171234567', 'Strips dashes: 0917-123-4567 -> 09171234567');
assertTest(UserValidator::sanitizePhone('0917 123 4567') === '09171234567', 'Strips spaces: 0917 123 4567 -> 09171234567');
assertTest(UserValidator::sanitizePhone('+639171234567') === '09171234567', 'Converts +639 format to 09 format');
assertTest(UserValidator::sanitizePhone('(0917) 123-4567') === '09171234567', 'Strips parentheses and spaces');

$inputDashedPhone = [
    'username' => 'test_bhw_' . time(),
    'password' => 'SecurePass123',
    'confirm_password' => 'SecurePass123',
    'first_name' => 'Juana',
    'last_name' => 'Dela Cruz',
    'contact_no' => '0917-987-6543'
];
$errors = $validator->validateCreate($inputDashedPhone);
assertTest(empty($errors), 'Validator accepts dashed contact number without error');

// TEST 3: User Profile Self-Service Job Title Update
echo "\n3. Testing Self-Service Job Title in User Profile:\n";
$testUser = $userModel->findByUsername('midwife_user');
if ($testUser) {
    $userId = $testUser['id'];
    $originalJobTitle = $testUser['job_title'];
    
    // Update job title to Senior Midwife / BHW Lead
    $newJobTitle = 'Senior Midwife / Maternal Care Lead';
    $updateData = [
        'first_name' => $testUser['first_name'],
        'middle_name' => $testUser['middle_name'],
        'last_name' => $testUser['last_name'],
        'email' => $testUser['email'],
        'contact_no' => '09171112233',
        'job_title' => $newJobTitle
    ];
    
    $result = $userModel->updateProfile($userId, $updateData);
    assertTest($result === true, 'User::updateProfile executes successfully');
    
    $reloaded = $userModel->findById($userId);
    assertTest($reloaded['job_title'] === $newJobTitle, "Persisted new job title: '{$reloaded['job_title']}'");
    assertTest($reloaded['contact_no'] === '09171112233', "Persisted clean contact number: '{$reloaded['contact_no']}'");
    
    // Restore original job title
    $updateData['job_title'] = $originalJobTitle;
    $userModel->updateProfile($userId, $updateData);
    $restored = $userModel->findById($userId);
    assertTest($restored['job_title'] === $originalJobTitle, "Restored original job title: '{$originalJobTitle}'");
} else {
    echo "  [SKIP] midwife_user account not found for profile test\n";
}

// TEST 4: Password Reset Validation
echo "\n4. Testing Administrative Password Reset Validation:\n";
$adminUser = $userModel->findByUsername('admin');
if ($adminUser && $testUser) {
    $resetDataMismatch = [
        'admin_password' => 'admin1234',
        'new_password' => 'TemporaryPass1',
        'confirm_password' => 'DifferentPass2'
    ];
    $errors = $validator->validatePasswordReset($testUser, $resetDataMismatch, $adminUser['id']);
    assertTest(!empty($errors) && in_array('New password and confirmation password do not match.', $errors), 'Rejects mismatched new passwords');
    
    $resetDataShort = [
        'admin_password' => 'admin1234',
        'new_password' => 'short',
        'confirm_password' => 'short'
    ];
    $errors = $validator->validatePasswordReset($testUser, $resetDataShort, $adminUser['id']);
    assertTest(!empty($errors) && in_array('New temporary password must be at least 8 characters long.', $errors), 'Rejects short new password (< 8 chars)');
    
    $resetDataWrongAdmin = [
        'admin_password' => 'wrong_admin_pass',
        'new_password' => 'TemporaryPass123',
        'confirm_password' => 'TemporaryPass123'
    ];
    $errors = $validator->validatePasswordReset($testUser, $resetDataWrongAdmin, $adminUser['id']);
    assertTest(!empty($errors) && in_array('Incorrect administrator authorization password.', $errors), 'Rejects incorrect administrator password');
} else {
    echo "  [SKIP] Test users not available for reset validation\n";
}

echo "\n======================================================\n";
echo " Test Results: {$passed} Passed, {$failed} Failed\n";
echo "======================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
