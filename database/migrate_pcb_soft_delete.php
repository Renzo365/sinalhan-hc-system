<?php
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once dirname(__DIR__) . '/app/helpers.php';

$db = App\Core\Database::getInstance()->getConnection();

try {
    // Add the soft-delete columns when upgrading an existing database.
    $stmt = $db->query("SHOW COLUMNS FROM pcb_service_logs LIKE 'deleted_at'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE pcb_service_logs 
            ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at,
            ADD COLUMN deleted_by INT NULL DEFAULT NULL AFTER deleted_at,
            ADD COLUMN archive_reason TEXT NULL DEFAULT NULL AFTER deleted_by
        ");
    }

    $stmt = $db->query("SHOW INDEX FROM pcb_service_logs WHERE Key_name = 'idx_pcb_service_logs_deleted'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE pcb_service_logs ADD INDEX idx_pcb_service_logs_deleted (deleted_at)");
    }

    $stmt = $db->prepare("SELECT 1 FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pcb_service_logs' AND COLUMN_NAME = 'deleted_by' AND REFERENCED_TABLE_NAME = 'users' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE pcb_service_logs ADD CONSTRAINT fk_pcb_service_logs_deleted_by FOREIGN KEY (deleted_by) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL");
    }

    echo "PCB service-log soft-delete schema is up to date.\n";
} catch (\Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
