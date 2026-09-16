<?php
$pdo = new PDO('mysql:host=localhost;dbname=sinalhan_hc_system', 'root', '');
$hash = password_hash('admin1234', PASSWORD_BCRYPT);
$pdo->query("UPDATE users SET password_hash = '{$hash}', must_change_password=0, failed_attempts=0 WHERE username = 'admin'");
echo "done";
