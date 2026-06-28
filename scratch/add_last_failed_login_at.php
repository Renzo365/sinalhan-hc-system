<?php
$config = require __DIR__ . '/../config/database.php';
try {
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Check if column already exists
    $columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_failed_login_at'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_failed_login_at DATETIME DEFAULT NULL AFTER last_login_at");
        echo "Column 'last_failed_login_at' added successfully to 'users' table.\n";
    } else {
        echo "Column 'last_failed_login_at' already exists.\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
