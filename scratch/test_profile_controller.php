<?php

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/../app/helpers.php';

$config = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "=== STARTING PROFILE CONTROLLER DIRECT LOGIC TEST ===\n\n";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Create a dummy user
$testUsername = 'test_controller_user';
$pdo->prepare("DELETE FROM audit_logs WHERE username = :u OR details LIKE :lu")->execute(['u' => $testUsername, 'lu' => "%{$testUsername}%"]);
$pdo->prepare("DELETE FROM users WHERE username = :u")->execute(['u' => $testUsername]);

$initPass = 'ControllerTest123!';
$pdo->prepare("
    INSERT INTO users (username, password_hash, role, first_name, last_name, email, contact_no, status, must_change_password)
    VALUES (:u, :p, 'staff', 'Juana', 'Luna', 'juana@example.com', '09191112222', 'active', 0)
")->execute([
    'u' => $testUsername,
    'p' => password_hash($initPass, PASSWORD_BCRYPT)
]);
$userId = (int)$pdo->lastInsertId();

// Setup session
$_SESSION['user_id'] = $userId;
$_SESSION['username'] = $testUsername;
$_SESSION['user_role'] = 'staff';
$_SESSION['user_fullname'] = 'Juana Luna';
$_SESSION['csrf_token'] = 'test_csrf_token_abc123';

echo "[TEST 1] Testing ProfileController instantiation\n";
$controller = new \App\Controllers\ProfileController();
assert($controller instanceof \App\Controllers\ProfileController);
echo "  -> PASS: ProfileController created successfully.\n\n";

// Subclass to override redirect for testing
class TestableProfileController extends \App\Controllers\ProfileController {
    public $redirectedTo = null;
    public $viewRendered = null;
    public $viewData = [];

    protected function redirect($url) {
        $this->redirectedTo = $url;
    }

    protected function view($name, $data = []) {
        $this->viewRendered = $name;
        $this->viewData = $data;
    }
}

$testCtrl = new TestableProfileController();

// 2. Test index()
echo "[TEST 2] Testing index() view rendering\n";
$testCtrl->index();
assert($testCtrl->viewRendered === 'profile/index', "View rendered should be profile/index");
assert(isset($testCtrl->viewData['user']), "View data should contain user");
assert($testCtrl->viewData['user']['username'] === $testUsername, "User should match");
echo "  -> PASS: index() correctly fetched user and bound profile/index view.\n\n";

// 3. Test update() with invalid CSRF
echo "[TEST 3] Testing update() with invalid CSRF\n";
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'csrf_token' => 'wrong_token',
    'first_name' => 'Juana Marie',
    'last_name' => 'Luna'
];
$testCtrl->update();
assert(isset($_SESSION['error_message']), "Expected error_message on CSRF failure");
assert($testCtrl->redirectedTo === '/profile', "Expected redirect to /profile");
unset($_SESSION['error_message']);
echo "  -> PASS: CSRF mismatch caught and rejected.\n\n";

// 4. Test update() with missing first name
echo "[TEST 4] Testing update() validation failure (missing name)\n";
$_POST = [
    'csrf_token' => 'test_csrf_token_abc123',
    'first_name' => '',
    'last_name' => 'Luna'
];
$testCtrl->update();
assert(!empty($_SESSION['profile_errors']), "Expected profile_errors on empty first_name");
unset($_SESSION['profile_errors'], $_SESSION['old_profile']);
echo "  -> PASS: Missing required name rejected.\n\n";

// 5. Test update() valid submission
echo "[TEST 5] Testing update() valid submission\n";
$_POST = [
    'csrf_token' => 'test_csrf_token_abc123',
    'first_name' => 'Juana Marie',
    'middle_name' => 'De Jesus',
    'last_name' => 'Luna-Reyes',
    'email' => 'juana.updated@example.com',
    'contact_no' => '09228889999'
];
$testCtrl->update();
assert(isset($_SESSION['success_message']), "Expected success_message");
assert($_SESSION['user_fullname'] === 'Juana Marie Luna-Reyes', "Active session user_fullname not updated");

$userModel = new \App\Models\User();
$freshUser = $userModel->findById($userId);
assert($freshUser['first_name'] === 'Juana Marie');
assert($freshUser['middle_name'] === 'De Jesus');
assert($freshUser['last_name'] === 'Luna-Reyes');
assert($freshUser['email'] === 'juana.updated@example.com');
assert($freshUser['contact_no'] === '09228889999');
unset($_SESSION['success_message']);
echo "  -> PASS: Profile details updated, session user_fullname refreshed.\n\n";

// 6. Test updatePassword() with wrong current password
echo "[TEST 6] Testing updatePassword() with wrong current password\n";
$_POST = [
    'csrf_token' => 'test_csrf_token_abc123',
    'current_password' => 'WrongPass999!',
    'new_password' => 'NewValidPass123!',
    'confirm_password' => 'NewValidPass123!'
];
$testCtrl->updatePassword();
assert(!empty($_SESSION['password_errors']), "Expected password_errors on wrong current password");
unset($_SESSION['password_errors']);
echo "  -> PASS: Incorrect current password rejected.\n\n";

// 7. Test updatePassword() with mismatched confirmation
echo "[TEST 7] Testing updatePassword() with mismatched confirmation\n";
$_POST = [
    'csrf_token' => 'test_csrf_token_abc123',
    'current_password' => $initPass,
    'new_password' => 'NewValidPass123!',
    'confirm_password' => 'DifferentPass456!'
];
$testCtrl->updatePassword();
assert(!empty($_SESSION['password_errors']), "Expected password_errors on mismatch");
unset($_SESSION['password_errors']);
echo "  -> PASS: Password confirmation mismatch rejected.\n\n";

// 8. Test updatePassword() with too short password
echo "[TEST 8] Testing updatePassword() with short password\n";
$_POST = [
    'csrf_token' => 'test_csrf_token_abc123',
    'current_password' => $initPass,
    'new_password' => 'short',
    'confirm_password' => 'short'
];
$testCtrl->updatePassword();
assert(!empty($_SESSION['password_errors']), "Expected password_errors on short password");
unset($_SESSION['password_errors']);
echo "  -> PASS: Short password (< 8 chars) rejected.\n\n";

// 9. Test updatePassword() valid change
echo "[TEST 9] Testing updatePassword() valid change\n";
$newSecurePass = 'SuperSecret2026!';
$_POST = [
    'csrf_token' => 'test_csrf_token_abc123',
    'current_password' => $initPass,
    'new_password' => $newSecurePass,
    'confirm_password' => $newSecurePass
];
$testCtrl->updatePassword();
assert(isset($_SESSION['success_message']), "Expected success_message after valid password update");
$freshUser = $userModel->findById($userId);
assert(password_verify($newSecurePass, $freshUser['password_hash']), "New password hash mismatch");
echo "  -> PASS: Password voluntarily changed, hashed with bcrypt, and success message flashed.\n\n";

// Cleanup
$pdo->prepare("DELETE FROM audit_logs WHERE user_id = :u")->execute(['u' => $userId]);
$pdo->prepare("DELETE FROM users WHERE id = :u")->execute(['u' => $userId]);
echo "Cleaned up test user record ID {$userId}.\n";

echo "\n=== ALL CONTROLLER DIRECT TESTS COMPLETED SUCCESSFULLY! ===\n";
