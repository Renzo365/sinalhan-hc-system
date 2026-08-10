<?php
/**
 * Migration Script for Patients Module Validation & Demographic Enhancements
 */

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();

$config = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "Starting DB Migration for Patients Module Enhancements...\n";

// 1. Add emergency_relationship column
try {
    $pdo->exec("ALTER TABLE patients ADD COLUMN emergency_relationship VARCHAR(50) DEFAULT NULL AFTER emergency_name");
    echo "[+] Added emergency_relationship column.\n";
} catch (\PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "[*] Column emergency_relationship already exists.\n";
    } else {
        echo "[-] Error adding emergency_relationship: " . $e->getMessage() . "\n";
    }
}

// 2. Add blood_type column
try {
    $pdo->exec("ALTER TABLE patients ADD COLUMN blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown') NOT NULL DEFAULT 'Unknown' AFTER civil_status");
    echo "[+] Added blood_type column.\n";
} catch (\PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "[*] Column blood_type already exists.\n";
    } else {
        echo "[-] Error adding blood_type: " . $e->getMessage() . "\n";
    }
}

// 3. Add occupation column
try {
    $pdo->exec("ALTER TABLE patients ADD COLUMN occupation VARCHAR(100) DEFAULT NULL AFTER blood_type");
    echo "[+] Added occupation column.\n";
} catch (\PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "[*] Column occupation already exists.\n";
    } else {
        echo "[-] Error adding occupation: " . $e->getMessage() . "\n";
    }
}

echo "Patients Migration Completed Successfully!\n";
