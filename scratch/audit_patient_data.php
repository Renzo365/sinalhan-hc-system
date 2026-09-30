<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Core/Database.php';

$pdo = \App\Core\Database::getInstance()->getConnection();

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables:\n";
print_r($tables);

// Check columns in patients
$cols = $pdo->query("DESCRIBE patients")->fetchAll(PDO::FETCH_ASSOC);
echo "\nPatients Columns:\n";
foreach ($cols as $c) {
    echo " - {$c['Field']} ({$c['Type']})\n";
}
