<?php
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once dirname(__DIR__) . '/app/helpers.php';

try {
    $db = App\Core\Database::getInstance()->getConnection();
    $sql = file_get_contents(__DIR__ . '/migrations/2026_09_24_drop_lab_tables.sql');
    $db->exec($sql);
    echo "Lab tables dropped successfully or did not exist.\n";
} catch (\Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
