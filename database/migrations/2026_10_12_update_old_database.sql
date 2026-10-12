-- ----------------------------------------------------------------------------
-- SinalHealth Database Catch-up & Schema Modernization Script
-- Date: October 12, 2026
-- Description: Run this SQL script in phpMyAdmin or MySQL CLI to upgrade any
--              older database to the latest schema version without data loss.
-- ----------------------------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Consultations: add consulting_provider
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'consulting_provider');
SET @stmt = IF(@col_exists = 0, 'ALTER TABLE `consultations` ADD COLUMN `consulting_provider` VARCHAR(150) NULL DEFAULT NULL AFTER `status`', 'SELECT 1');
PREPARE stmt FROM @stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Appointments: add soft-delete fields and index
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'appointments' AND COLUMN_NAME = 'deleted_at');
SET @stmt = IF(@col_exists = 0, 'ALTER TABLE `appointments` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`, ADD COLUMN `deleted_by` INT NULL DEFAULT NULL AFTER `deleted_at`, ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL AFTER `deleted_by`, ADD INDEX `idx_appointments_deleted` (`deleted_at`)', 'SELECT 1');
PREPARE stmt FROM @stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Queue Entries: add appointment_id and priority_level
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'queue_entries' AND COLUMN_NAME = 'appointment_id');
SET @stmt = IF(@col_exists = 0, 'ALTER TABLE `queue_entries` ADD COLUMN `appointment_id` INT(11) NULL DEFAULT NULL AFTER `patient_id`, ADD INDEX `idx_queue_appointment_id` (`appointment_id`)', 'SELECT 1');
PREPARE stmt FROM @stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'queue_entries' AND COLUMN_NAME = 'priority_level');
SET @stmt = IF(@col_exists = 0, 'ALTER TABLE `queue_entries` ADD COLUMN `priority_level` VARCHAR(30) NOT NULL DEFAULT \'Regular\' AFTER `service_type`', 'SELECT 1');
PREPARE stmt FROM @stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Users: add super_admin enum option, is_super_admin, and preferences
ALTER TABLE `users` MODIFY COLUMN `role` ENUM('super_admin', 'admin', 'staff') NOT NULL DEFAULT 'staff';

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_super_admin');
SET @stmt = IF(@col_exists = 0, 'ALTER TABLE `users` ADD COLUMN `is_super_admin` TINYINT(1) NOT NULL DEFAULT 0 AFTER `role`', 'SELECT 1');
PREPARE stmt FROM @stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'preferences');
SET @stmt = IF(@col_exists = 0, 'ALTER TABLE `users` ADD COLUMN `preferences` TEXT NULL DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Well-Baby: add place_of_delivery_other and attended_by_other
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wellbaby_records' AND COLUMN_NAME = 'place_of_delivery_other');
SET @stmt = IF(@col_exists = 0, 'ALTER TABLE `wellbaby_records` ADD COLUMN `place_of_delivery_other` VARCHAR(150) NULL AFTER `place_of_delivery`', 'SELECT 1');
PREPARE stmt FROM @stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wellbaby_records' AND COLUMN_NAME = 'attended_by_other');
SET @stmt = IF(@col_exists = 0, 'ALTER TABLE `wellbaby_records` ADD COLUMN `attended_by_other` VARCHAR(150) NULL AFTER `attended_by`', 'SELECT 1');
PREPARE stmt FROM @stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. System Settings Table
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

INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `category`) VALUES
('facility_name', 'Barangay Health Station Sinalhan 2', 'general'),
('facility_subtext', 'City Health Office I - Santa Rosa City, Laguna', 'general'),
('facility_address', 'Barangay Sinalhan, Santa Rosa City, Laguna', 'general'),
('facility_contact', '0912-345-6789', 'general'),
('facility_email', 'bhs.sinalhan2@santarosacity.gov.ph', 'general'),
('operating_hours', 'Monday - Friday: 8:00 AM - 5:00 PM', 'general'),
('doctor_name', 'Dr. Maria Santos, MD', 'clinical'),
('doctor_title', 'Municipal Health Officer', 'clinical'),
('doctor_license', 'PRC #0123456', 'clinical'),
('midwife_name', 'Juana Dela Cruz, RM', 'clinical'),
('nurse_name', 'Elena Bautista, RN', 'clinical'),
('system_timezone', 'Asia/Manila', 'technical'),
('enable_audit_logging', '1', 'technical'),
('session_lifetime_minutes', '120', 'technical'),
('backup_retention_days', '30', 'technical');

SET FOREIGN_KEY_CHECKS = 1;
