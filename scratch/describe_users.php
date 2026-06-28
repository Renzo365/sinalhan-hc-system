<?php
$config = require __DIR__ . '/../config/database.php';
try {
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    echo "COLUMNS OF users TABLE:\n";
    foreach ($columns as $col) {
        echo "{$col['Field']} - {$col['Type']} - Null: {$col['Null']} - Key: {$col['Key']} - Default: {$col['Default']} - Extra: {$col['Extra']}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
