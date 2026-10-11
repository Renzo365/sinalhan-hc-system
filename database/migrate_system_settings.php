<?php
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once dirname(__DIR__) . '/app/helpers.php';

$db = App\Core\Database::getInstance()->getConnection();

try {
    echo "Running system settings migration...\n";

    // 1. Create system_settings table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS `system_settings` (
            `setting_key` VARCHAR(64) NOT NULL,
            `setting_value` TEXT NULL DEFAULT NULL,
            `category` VARCHAR(32) NOT NULL DEFAULT 'general',
            `updated_by` INT(11) NULL DEFAULT NULL,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`setting_key`),
            KEY `idx_settings_category` (`category`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Add index and foreign key constraint if users table exists and constraint is not present
    $stmt = $db->query("SHOW INDEX FROM `system_settings` WHERE Key_name = 'idx_settings_updated_by'");
    if (!$stmt->fetch()) {
        try {
            $db->exec("ALTER TABLE `system_settings` ADD INDEX `idx_settings_updated_by` (`updated_by`)");
        } catch (\Exception $e) {}
    }

    $stmt = $db->prepare("
        SELECT 1 FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'system_settings' 
          AND CONSTRAINT_NAME = 'fk_sys_settings_user' 
        LIMIT 1
    ");
    $stmt->execute();
    if (!$stmt->fetch()) {
        try {
            $db->exec("ALTER TABLE `system_settings` ADD CONSTRAINT `fk_sys_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL");
        } catch (\Exception $fkEx) {
            echo "Note: FK constraint fk_sys_settings_user could not be added: " . $fkEx->getMessage() . "\n";
        }
    }

    // 2. Add preferences column to users table if missing
    $stmt = $db->query("SHOW COLUMNS FROM `users` LIKE 'preferences'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE `users` ADD COLUMN `preferences` TEXT NULL DEFAULT NULL");
        echo "Added 'preferences' column to 'users' table.\n";
    }

    // 3. Seed default system settings
    $defaultSettings = [
        // Facility Information & Report Headers
        [
            'key' => 'facility_name',
            'value' => 'Barangay Health Station Sinalhan 2',
            'category' => 'facility'
        ],
        [
            'key' => 'facility_address',
            'value' => 'Barangay Sinalhan, City of Santa Rosa, Laguna',
            'category' => 'facility'
        ],
        [
            'key' => 'facility_contact',
            'value' => '09175558888',
            'category' => 'facility'
        ],
        [
            'key' => 'facility_email',
            'value' => 'bhc.sinalhan2@santarosa.gov.ph',
            'category' => 'facility'
        ],
        [
            'key' => 'facility_mho_name',
            'value' => 'Dr. Maria Santos, MD',
            'category' => 'facility'
        ],
        [
            'key' => 'facility_phic_code',
            'value' => 'PHIC-R4A-LAG-SR-0042',
            'category' => 'facility'
        ],
        [
            'key' => 'facility_doh_code',
            'value' => 'DOH-BHS-043428-02',
            'category' => 'facility'
        ],
        // Scheduling & Clinic Operations
        [
            'key' => 'appointment_slot_capacity',
            'value' => '2',
            'category' => 'scheduling'
        ],
        [
            'key' => 'morning_opd_start',
            'value' => '08:30',
            'category' => 'scheduling'
        ],
        [
            'key' => 'morning_opd_end',
            'value' => '12:00',
            'category' => 'scheduling'
        ],
        [
            'key' => 'afternoon_opd_start',
            'value' => '13:00',
            'category' => 'scheduling'
        ],
        [
            'key' => 'afternoon_opd_end',
            'value' => '17:00',
            'category' => 'scheduling'
        ],
        // Security & Session Policies (Super Admin)
        [
            'key' => 'session_idle_timeout',
            'value' => '7200',
            'category' => 'security'
        ],
        [
            'key' => 'max_login_attempts',
            'value' => '5',
            'category' => 'security'
        ]
    ];

    $insertStmt = $db->prepare("
        INSERT INTO `system_settings` (`setting_key`, `setting_value`, `category`)
        VALUES (:key, :value, :category)
        ON DUPLICATE KEY UPDATE `category` = VALUES(`category`)
    ");

    $insertedCount = 0;
    foreach ($defaultSettings as $setting) {
        $insertStmt->execute([
            ':key' => $setting['key'],
            ':value' => $setting['value'],
            ':category' => $setting['category']
        ]);
        $insertedCount++;
    }

    echo "System settings table ready. Seeded/verified {$insertedCount} settings.\n";
    echo "Migration completed successfully!\n";

} catch (\Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
