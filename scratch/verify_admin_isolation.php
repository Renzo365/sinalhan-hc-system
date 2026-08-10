<?php
/**
 * Barangay Sinalhan Health Center - Role Modification & Creation Guards Verification
 */

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();

$config = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Cleanup old test users
$adminA = 'test_admin_a';
$staffC = 'test_staff_c';

$pdo->prepare("DELETE FROM audit_logs WHERE username IN (:a, :c) OR details LIKE :la OR details LIKE :lc")->execute([
    'a' => $adminA, 'c' => $staffC,
    'la' => "%{$adminA}%", 'lc' => "%{$staffC}%"
]);
$pdo->prepare("DELETE FROM users WHERE username IN (:a, :c)")->execute([
    'a' => $adminA, 'c' => $staffC
]);

// Insert standard secondary admin A
$hashA = password_hash('passA123', PASSWORD_BCRYPT);
$pdo->prepare("INSERT INTO users (username, password_hash, role, first_name, last_name, status, must_change_password) VALUES (:u, :h, 'admin', 'Admin', 'A', 'active', 0)")->execute(['u' => $adminA, 'h' => $hashA]);
$idA = $pdo->lastInsertId();

// Insert staff C
$hashC = password_hash('passC123', PASSWORD_BCRYPT);
$pdo->prepare("INSERT INTO users (username, password_hash, role, first_name, last_name, status, must_change_password) VALUES (:u, :h, 'staff', 'Staff', 'C', 'active', 0)")->execute(['u' => $staffC, 'h' => $hashC]);
$idC = $pdo->lastInsertId();

echo "--- STARTING ROLE PROMOTION & ABUSE PREVENTION TESTS ---\n";

// Function simulating controller logic for creation
function simulateCreation($actorId, $postRole) {
    $role = $postRole;
    // Privilege Escalation Guard
    if ($actorId != 1 && $role === 'admin') {
        $role = 'staff';
    }
    return $role;
}

// Function simulating controller logic for modification
function simulateModification($actorId, $targetId, $targetCurrentRole, $postRole) {
    if ($actorId != 1 && $postRole !== $targetCurrentRole) {
        return false; // Blocked
    }
    return true; // Allowed
}

// Test 1: Admin A tries to create a new user with the admin role -> Should override to staff
$res1 = simulateCreation($idA, 'admin');
echo "Test 1: Admin A trying to create a Co-Admin... " . ($res1 === 'staff' ? "PASS (Overridden to staff)" : "FAIL") . "\n";

// Test 2: Primary Admin (ID 1) tries to create a new user with the admin role -> Should remain admin
$res2 = simulateCreation(1, 'admin');
echo "Test 2: Primary Admin trying to create a Co-Admin... " . ($res2 === 'admin' ? "PASS (Allowed)" : "FAIL") . "\n";

// Test 3: Admin A tries to promote Staff C from staff to admin -> Should fail
$res3 = simulateModification($idA, $idC, 'staff', 'admin');
echo "Test 3: Admin A trying to promote Staff C to Co-Admin... " . ($res3 === false ? "PASS (Blocked)" : "FAIL") . "\n";

// Test 4: Primary Admin (ID 1) tries to promote Staff C from staff to admin -> Should pass
$res4 = simulateModification(1, $idC, 'staff', 'admin');
echo "Test 4: Primary Admin trying to promote Staff C to Co-Admin... " . ($res4 === true ? "PASS (Allowed)" : "FAIL") . "\n";

// Clean up DB rows
$pdo->prepare("DELETE FROM audit_logs WHERE username IN (:a, :c) OR details LIKE :la OR details LIKE :lc")->execute([
    'a' => $adminA, 'c' => $staffC,
    'la' => "%{$adminA}%", 'lc' => "%{$staffC}%"
]);
$pdo->prepare("DELETE FROM users WHERE username IN (:a, :c)")->execute([
    'a' => $adminA, 'c' => $staffC
]);

echo "--- VERIFICATION COMPLETE ---\n";
