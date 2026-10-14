<?php

namespace App\Core;

use PDO;
use Exception;

class SchemaMigrator {
    private const CURRENT_VERSION = '2026_10_14_v1';

    /**
     * Check and ensure database schema is compatible with the latest codebase.
     * Uses a lock file in storage to avoid executing checks on every request.
     *
     * @param PDO $db
     * @param bool $force Force re-verification regardless of lock file
     * @return array List of executed changes
     */
    public static function ensureCompatibility(PDO $db, bool $force = false): array {
        $storageDir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'storage';
        $lockFile = $storageDir . DIRECTORY_SEPARATOR . 'schema_verified.lock';

        if (!$force && file_exists($lockFile)) {
            $content = @file_get_contents($lockFile);
            if (trim($content) === self::CURRENT_VERSION) {
                return [];
            }
        }

        $changes = self::runMigrations($db);

        // Save lock file once verified
        if (is_dir($storageDir) && is_writable($storageDir)) {
            @file_put_contents($lockFile, self::CURRENT_VERSION);
        }

        return $changes;
    }

    /**
     * Run all required idempotent schema migrations.
     *
     * @param PDO $db
     * @return array
     */
    public static function runMigrations(PDO $db): array {
        $changes = [];

        // 1. consultations.consulting_provider
        if (!self::columnExists($db, 'consultations', 'consulting_provider')) {
            try {
                $db->exec("ALTER TABLE `consultations` ADD COLUMN `consulting_provider` VARCHAR(150) NULL DEFAULT NULL AFTER `status`");
                $changes[] = "Added consultations.consulting_provider";
            } catch (Exception $e) {
                error_log("SchemaMigrator error on consultations: " . $e->getMessage());
            }
        }

        // 2. appointments.deleted_at, deleted_by, archive_reason
        if (!self::columnExists($db, 'appointments', 'deleted_at')) {
            try {
                $db->exec("ALTER TABLE `appointments` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`");
                $changes[] = "Added appointments.deleted_at";
            } catch (Exception $e) {}
        }
        if (!self::columnExists($db, 'appointments', 'deleted_by')) {
            try {
                $db->exec("ALTER TABLE `appointments` ADD COLUMN `deleted_by` INT NULL DEFAULT NULL AFTER `deleted_at`");
                $changes[] = "Added appointments.deleted_by";
            } catch (Exception $e) {}
        }
        if (!self::columnExists($db, 'appointments', 'archive_reason')) {
            try {
                $db->exec("ALTER TABLE `appointments` ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL AFTER `deleted_by`");
                $changes[] = "Added appointments.archive_reason";
            } catch (Exception $e) {}
        }
        // Add index on appointments.deleted_at if missing
        try {
            $idxStmt = $db->query("SHOW INDEX FROM `appointments` WHERE Key_name = 'idx_appointments_deleted'");
            if (!$idxStmt->fetch()) {
                $db->exec("ALTER TABLE `appointments` ADD INDEX `idx_appointments_deleted` (`deleted_at`)");
            }
        } catch (Exception $e) {}

        // 3. queue_entries.appointment_id, priority_level
        if (!self::columnExists($db, 'queue_entries', 'appointment_id')) {
            try {
                $db->exec("ALTER TABLE `queue_entries` ADD COLUMN `appointment_id` INT(11) NULL DEFAULT NULL AFTER `patient_id`");
                $changes[] = "Added queue_entries.appointment_id";
            } catch (Exception $e) {}
        }
        if (!self::columnExists($db, 'queue_entries', 'priority_level')) {
            try {
                $db->exec("ALTER TABLE `queue_entries` ADD COLUMN `priority_level` VARCHAR(30) NOT NULL DEFAULT 'Regular' AFTER `service_type`");
                $changes[] = "Added queue_entries.priority_level";
            } catch (Exception $e) {}
        }
        try {
            $idxStmt = $db->query("SHOW INDEX FROM `queue_entries` WHERE Key_name = 'idx_queue_appointment_id'");
            if (!$idxStmt->fetch()) {
                $db->exec("ALTER TABLE `queue_entries` ADD INDEX `idx_queue_appointment_id` (`appointment_id`)");
            }
        } catch (Exception $e) {}

        // 4. users.role, is_super_admin, preferences
        try {
            $roleStmt = $db->query("SHOW COLUMNS FROM `users` LIKE 'role'");
            $roleRow = $roleStmt->fetch(PDO::FETCH_ASSOC);
            if ($roleRow && strpos($roleRow['Type'], "'super_admin'") === false) {
                $db->exec("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('super_admin', 'admin', 'staff') NOT NULL DEFAULT 'staff'");
                $changes[] = "Updated users.role enum to include super_admin";
            }
        } catch (Exception $e) {}

        if (!self::columnExists($db, 'users', 'is_super_admin')) {
            try {
                $db->exec("ALTER TABLE `users` ADD COLUMN `is_super_admin` TINYINT(1) NOT NULL DEFAULT 0 AFTER `role`");
                $changes[] = "Added users.is_super_admin";
            } catch (Exception $e) {}
        }
        if (!self::columnExists($db, 'users', 'preferences')) {
            try {
                $db->exec("ALTER TABLE `users` ADD COLUMN `preferences` TEXT NULL DEFAULT NULL");
                $changes[] = "Added users.preferences";
            } catch (Exception $e) {}
        }

        // 5. wellbaby_records.place_of_delivery_other, attended_by_other
        if (!self::columnExists($db, 'wellbaby_records', 'place_of_delivery_other')) {
            try {
                $db->exec("ALTER TABLE `wellbaby_records` ADD COLUMN `place_of_delivery_other` VARCHAR(150) NULL AFTER `place_of_delivery`");
                $changes[] = "Added wellbaby_records.place_of_delivery_other";
            } catch (Exception $e) {}
        }
        if (!self::columnExists($db, 'wellbaby_records', 'attended_by_other')) {
            try {
                $db->exec("ALTER TABLE `wellbaby_records` ADD COLUMN `attended_by_other` VARCHAR(150) NULL AFTER `attended_by`");
                $changes[] = "Added wellbaby_records.attended_by_other";
            } catch (Exception $e) {}
        }

        // 6. system_settings table
        if (!self::tableExists($db, 'system_settings')) {
            try {
                $db->exec("
                    CREATE TABLE IF NOT EXISTS `system_settings` (
                        `setting_key` VARCHAR(64) NOT NULL,
                        `setting_value` TEXT NULL DEFAULT NULL,
                        `category` VARCHAR(32) NOT NULL DEFAULT 'general',
                        `updated_by` INT(11) NULL DEFAULT NULL,
                        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (`setting_key`),
                        KEY `idx_settings_category` (`category`),
                        KEY `idx_settings_updated_by` (`updated_by`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
                $changes[] = "Created system_settings table";

                // Seed default settings
                $defaultSettings = [
                    ['facility_name', 'Barangay Health Station Sinalhan 2', 'general'],
                    ['facility_subtext', 'City Health Office I - Santa Rosa City, Laguna', 'general'],
                    ['facility_address', 'Barangay Sinalhan, Santa Rosa City, Laguna', 'general'],
                    ['facility_contact', '0912-345-6789', 'general'],
                    ['facility_email', 'bhs.sinalhan2@santarosacity.gov.ph', 'general'],
                    ['operating_hours', 'Monday - Friday: 8:00 AM - 5:00 PM', 'general'],
                    ['doctor_name', 'Dr. Maria Santos, MD', 'clinical'],
                    ['doctor_title', 'Municipal Health Officer', 'clinical'],
                    ['doctor_license', 'PRC #0123456', 'clinical'],
                    ['midwife_name', 'Juana Dela Cruz, RM', 'clinical'],
                    ['nurse_name', 'Elena Bautista, RN', 'clinical'],
                    ['system_timezone', 'Asia/Manila', 'technical'],
                    ['enable_audit_logging', '1', 'technical'],
                    ['session_lifetime_minutes', '120', 'technical'],
                    ['backup_retention_days', '30', 'technical']
                ];
                $seedStmt = $db->prepare("
                    INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `category`)
                    VALUES (:key, :value, :category)
                ");
                foreach ($defaultSettings as $setting) {
                    $seedStmt->execute([
                        ':key' => $setting[0],
                        ':value' => $setting[1],
                        ':category' => $setting[2]
                    ]);
                }
            } catch (Exception $e) {
                error_log("SchemaMigrator error on system_settings: " . $e->getMessage());
            }
        }

        // 7. queue_entries.call_count for tracking calls and recalls
        if (!self::columnExists($db, 'queue_entries', 'call_count')) {
            try {
                $db->exec("ALTER TABLE `queue_entries` ADD COLUMN `call_count` INT NOT NULL DEFAULT 0 AFTER `time_called`");
                $changes[] = "Added queue_entries.call_count";
            } catch (Exception $e) {
                error_log("SchemaMigrator error on queue_entries.call_count: " . $e->getMessage());
            }
        }

        return $changes;
    }

    /**
     * Check if a column exists in a given table.
     */
    public static function columnExists(PDO $db, string $table, string $column): bool {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
        try {
            $stmt = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
            return (bool)$stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if a table exists.
     */
    public static function tableExists(PDO $db, string $table): bool {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        try {
            $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
            return (bool)$stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    }
}
