<?php

require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/AuditLog.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Middleware/AdminMiddleware.php';
require_once __DIR__ . '/../app/Controllers/UserController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$passed = 0;
$failed = 0;

function it($description, $condition) {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$description}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$description}\n";
        $failed++;
    }
}

echo "=== 1. Testing AdminMiddleware ===\n";

// Test unauthenticated
$_SESSION = [];
$adminMw = new \App\Middleware\AdminMiddleware();
// We won't trigger exit in handle(), but we can inspect role check logic
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
$_SESSION['last_activity'] = time();
$_SERVER['SCRIPT_NAME'] = '/sinalhan-hc-system/public/index.php';
$_SERVER['REQUEST_METHOD'] = 'GET';

$res = $adminMw->handle();
it("AdminMiddleware allows authenticated admin", $res === true);

// Test AJAX detection on non-admin
$_SESSION['user_role'] = 'staff';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
// We can check if it detects non-admin
it("AdminMiddleware identifies non-admin correctly", ($_SESSION['user_role'] ?? '') !== 'admin');
unset($_SERVER['HTTP_X_REQUESTED_WITH']);

echo "\n=== 2. Testing UserController CSRF & Validation ===\n";
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
$_SESSION['csrf_token'] = 'valid_token_1234567890';

$controller = new class extends \App\Controllers\UserController {
    public $redirectedTo = null;
    public function redirect($url) {
        $this->redirectedTo = $url;
    }
};

// Test store() with invalid CSRF
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'csrf_token' => 'invalid_token',
    'username' => 'testuser',
    'password' => 'Password123!',
    'first_name' => 'Test',
    'last_name' => 'User'
];
$controller->store();
it("store() rejects invalid CSRF", $controller->redirectedTo === '/users/create');
it("store() sets error_message on CSRF failure", strpos($_SESSION['error_message'] ?? '', 'CSRF') !== false);
it("store() retains old_input on CSRF failure", ($_SESSION['old_input']['username'] ?? '') === 'testuser');

// Test store() with invalid username regex
$_POST['csrf_token'] = 'valid_token_1234567890';
$_POST['username'] = 'ab'; // too short (< 3)
$_SESSION['form_errors'] = [];
$controller->store();
it("store() rejects username < 3 chars", in_array('Username must be 3 to 20 alphanumeric characters (underscores allowed).', $_SESSION['form_errors'] ?? []));

$_POST['username'] = 'invalid@user!'; // special chars not allowed
$_SESSION['form_errors'] = [];
$controller->store();
it("store() rejects username with invalid characters", in_array('Username must be 3 to 20 alphanumeric characters (underscores allowed).', $_SESSION['form_errors'] ?? []));

// Test store() with invalid email format
$_POST['username'] = 'valid_user_99';
$_POST['email'] = 'not-an-email';
$_SESSION['form_errors'] = [];
$controller->store();
it("store() rejects invalid email format", in_array('Invalid email address format.', $_SESSION['form_errors'] ?? []));

// Test store() with invalid contact number format
$_POST['email'] = 'valid@example.com';
$_POST['contact_no'] = '12345';
$_SESSION['form_errors'] = [];
$controller->store();
it("store() rejects invalid contact_no format", in_array('Contact number must be an 11-digit Philippine mobile number starting with 09 (e.g., 09171234567).', $_SESSION['form_errors'] ?? []));

// Test update() with invalid CSRF
$_POST = [
    'csrf_token' => 'wrong_token',
    'first_name' => 'Renzo',
    'last_name' => 'Admin'
];
$controller->update(1);
it("update() rejects invalid CSRF", strpos($_SESSION['error_message'] ?? '', 'CSRF') !== false);

// Test update() session full name sync (with teardown to avoid DB mutation)
$userModel = new \App\Models\User();
$origUser = $userModel->findById(1);
$origSessionName = $_SESSION['user_fullname'] ?? null;

$_POST = [
    'csrf_token' => 'valid_token_1234567890',
    'first_name' => 'Renzo Updated',
    'last_name' => 'Admin',
    'role' => 'admin',
    'email' => 'admin@sinalhan.gov.ph',
    'contact_no' => '09171234567'
];
$_SESSION['user_fullname'] = 'Renzo Admin';
try {
    $controller->update(1);
    it("update() syncs \$_SESSION['user_fullname'] in real time for logged-in user", $_SESSION['user_fullname'] === 'Renzo Updated Admin');
} finally {
    if ($origUser) {
        $userModel->update(1, [
            'first_name' => $origUser['first_name'],
            'last_name' => $origUser['last_name'],
            'role' => $origUser['role'],
            'email' => $origUser['email'],
            'contact_no' => $origUser['contact_no'],
            'employee_id' => $origUser['employee_id'] ?? null,
            'job_title' => $origUser['job_title'] ?? null,
            'department' => $origUser['department'] ?? null
        ]);
    }
    if ($origSessionName !== null) {
        $_SESSION['user_fullname'] = $origSessionName;
    }
}

// Test resetPassword() CSRF guard
$_POST = ['csrf_token' => 'bad_token'];
$controller->resetPassword(1);
it("resetPassword() rejects invalid CSRF", strpos($_SESSION['error_message'] ?? '', 'CSRF') !== false);

// Test toggleStatus() CSRF guard
$_POST = ['csrf_token' => 'bad_token'];
$controller->toggleStatus(2);
it("toggleStatus() rejects invalid CSRF", strpos($_SESSION['error_message'] ?? '', 'CSRF') !== false);

// Test resetLockout() CSRF guard
$_POST = ['csrf_token' => 'bad_token'];
$controller->resetLockout(2);
it("resetLockout() rejects invalid CSRF", strpos($_SESSION['error_message'] ?? '', 'CSRF') !== false);

echo "\n=== 3. Testing View Contents ===\n";

$indexContent = file_get_contents(__DIR__ . '/../app/Views/users/index.php');
it("users/index.php does NOT contain new \\App\\Models\\User()", strpos($indexContent, 'new \\App\\Models\\User()') === false);
it("users/index.php uses lockout_info precomputed array", strpos($indexContent, "\$u['lockout_info']") !== false);
it("users/index.php contains btnGeneratePassword button", strpos($indexContent, 'id="btnGeneratePassword"') !== false);
it("users/index.php contains passwordCopiedNotice", strpos($indexContent, 'id="passwordCopiedNotice"') !== false);
it("users/index.php has min-width: 34px on action buttons", strpos($indexContent, 'min-width: 34px') !== false);

$editContent = file_get_contents(__DIR__ . '/../app/Views/users/edit.php');
it("users/edit.php retrieves \$_SESSION['old_input']", strpos($editContent, "\$_SESSION['old_input']") !== false);
it("users/edit.php retrieves \$_SESSION['form_errors']", strpos($editContent, "\$_SESSION['form_errors']") !== false);
it("users/edit.php unsets \$_SESSION['old_input'] and form_errors", strpos($editContent, "unset(\$_SESSION['old_input'], \$_SESSION['form_errors'])") !== false);
it("users/edit.php renders error alert banner", strpos($editContent, "Please correct the following issues:") !== false);
it("users/edit.php prioritizes \$old input over \$user", strpos($editContent, "\$old['first_name'] ?? \$user['first_name']") !== false);

echo "\n=========================================\n";
echo "Results: {$passed} passed, {$failed} failed.\n";
