<?php
require_once __DIR__ . '/../app/Core/Database.php';
$db = App\Core\Database::getInstance()->getConnection();
$admin = $db->query("SELECT * FROM users WHERE username='admin'")->fetch();
if (password_verify('admin1234', $admin['password_hash'])) {
    echo "MATCH admin1234!\n";
} else {
    echo "No match. Hash is: " . $admin['password_hash'] . "\n";
}
