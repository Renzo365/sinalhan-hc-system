<?php
/**
 * Migration Script for User Account Module Revamp
 */

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();

$config = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "Starting DB Migration for User Module Revamp...\n";

// 1. Add employee_id column if not exists
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN employee_id VARCHAR(50) DEFAULT NULL AFTER job_title");
    echo "[+] Added employee_id column.\n";
} catch (\PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "[*] Column employee_id already exists.\n";
    } else {
        echo "[-] Error adding employee_id: " . $e->getMessage() . "\n";
    }
}

// 2. Add department column if not exists
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN department VARCHAR(100) DEFAULT NULL AFTER employee_id");
    echo "[+] Added department column.\n";
} catch (\PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "[*] Column department already exists.\n";
    } else {
        echo "[-] Error adding department: " . $e->getMessage() . "\n";
    }
}

// 3. Convert any 'suspended' status or soft-deleted accounts to 'inactive'
$pdo->exec("UPDATE users SET status = 'inactive' WHERE status = 'suspended'");
$pdo->exec("UPDATE users SET deleted_at = NULL WHERE deleted_at IS NOT NULL");
echo "[+] Converted suspended and soft-deleted records to inactive status.\n";

// 4. Modify status ENUM column to ('active', 'inactive')
try {
    $pdo->exec("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'");
    echo "[+] Modified status ENUM definition to ('active', 'inactive').\n";
} catch (\PDOException $e) {
    echo "[-] Error modifying status ENUM: " . $e->getMessage() . "\n";
}

echo "DB Migration Completed Successfully!\n";
