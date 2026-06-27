<?php

// Check if run from CLI
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

$dbConfig = require dirname(__DIR__) . '/config/database.php';

try {
    // Connect to MySQL server first (without database to avoid error if DB doesn't exist)
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "Connected to MySQL server.\n";

    // Load schema
    $schemaPath = dirname(__DIR__) . '/database/schema.sql';
    if (!file_exists($schemaPath)) {
        die("Error: schema.sql not found at {$schemaPath}\n");
    }
    
    echo "Executing schema.sql...\n";
    $schemaSql = file_get_contents($schemaPath);
    $pdo->exec($schemaSql);
    echo "Database schema initialized successfully.\n";

    // Load seed data
    $seedPath = dirname(__DIR__) . '/database/seed.sql';
    if (!file_exists($seedPath)) {
        die("Error: seed.sql not found at {$seedPath}\n");
    }
    
    echo "Executing seed.sql...\n";
    $seedSql = file_get_contents($seedPath);
    $pdo->exec($seedSql);
    echo "Database seeded successfully.\n";
    
    echo "Database initialization completed successfully!\n";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage() . "\n");
}
