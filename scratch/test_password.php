<?php
require 'app/Core/Database.php';
$db = App\Core\Database::getInstance()->getConnection();
$hash = $db->query("SELECT password_hash FROM users WHERE username = 'admin'")->fetchColumn();
foreach (["admin1234", "Admin1234", "Password123!", "Admin123!", "admin123", "admin", "AdminPass123!", "Admin@123", "password"] as $p) {
    if (password_verify($p, $hash)) {
        echo "PASSWORD: $p\n";
        exit;
    }
}
echo "No match\n";
