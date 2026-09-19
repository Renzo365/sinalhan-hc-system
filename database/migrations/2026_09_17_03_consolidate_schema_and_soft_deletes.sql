-- =============================================================
-- Migration: 2026_09_17_03_consolidate_schema_and_soft_deletes.sql
-- Description: Universal catch-up migration to synchronize any older
--              database with full soft-delete compliance, physical envelope
--              tracking, active pregnancy constraints, and queue counters.
-- Safe and idempotent: can be rerun safely on any existing database.
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET @db_name = DATABASE();

-- 1. Queue Daily Counters Table
CREATE TABLE IF NOT EXISTS `queue_daily_counters` (
  `queue_date` DATE NOT NULL,
  `last_queue_no` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`queue_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Patients: Physical Envelope Number
SET @sql = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'patients') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'patients' AND COLUMN_NAME = 'envelope_no') = 0,
    'ALTER TABLE `patients` ADD COLUMN `envelope_no` VARCHAR(50) DEFAULT NULL AFTER `patient_no`, ADD INDEX `idx_patients_envelope_no` (`envelope_no`)',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Patient Conditions: Family Lineage
SET @sql = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'patient_conditions') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'patient_conditions' AND COLUMN_NAME = 'lineage') = 0,
    'ALTER TABLE `patient_conditions` ADD COLUMN `lineage` ENUM(\'Mother\', \'Father\', \'Both\', \'Unknown\') DEFAULT NULL AFTER `condition_type`',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. Immunizations: Source & Documentation Status Provenance
SET @sql = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'immunizations') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'immunizations' AND COLUMN_NAME = 'source') = 0,
    'ALTER TABLE `immunizations` ADD COLUMN `source` ENUM(\'Health Center\', \'External\', \'Patient Reported\', \'Unknown\') NOT NULL DEFAULT \'Health Center\' AFTER `administered_date`',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'immunizations') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'immunizations' AND COLUMN_NAME = 'documentation_status') = 0,
    'ALTER TABLE `immunizations` ADD COLUMN `documentation_status` ENUM(\'Administered\', \'Reported\', \'Unknown\') NOT NULL DEFAULT \'Administered\' AFTER `source`',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. Prenatal Records: Active Pregnancy Episode Virtual Column & Unique Constraint
SET @sql = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'prenatal_records') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'prenatal_records' AND COLUMN_NAME = 'active_patient_id') = 0,
    'ALTER TABLE `prenatal_records` ADD COLUMN `active_patient_id` INT GENERATED ALWAYS AS (IF(`is_active` = 1, `patient_id`, NULL)) STORED, ADD UNIQUE KEY `uq_pr_active_patient` (`active_patient_id`)',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 6. Prenatal Records: Remove Misplaced IHP Columns
SET @sql = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'prenatal_records') > 0,
    'ALTER TABLE `prenatal_records` DROP COLUMN IF EXISTS `pre_eclampsia`, DROP COLUMN IF EXISTS `fp_counselling`',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7. Soft Delete Audit Columns on All Clinical Tables
-- 7.1 vital_signs
SET @sql = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'vital_signs') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'vital_signs' AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `vital_signs` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL, ADD COLUMN `deleted_by` INT NULL DEFAULT NULL, ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL, ADD INDEX `idx_vital_signs_deleted` (`deleted_at`), ADD CONSTRAINT `fk_vital_signs_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7.2 prescriptions
SET @sql = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'prescriptions') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'prescriptions' AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `prescriptions` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL, ADD COLUMN `deleted_by` INT NULL DEFAULT NULL, ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL, ADD INDEX `idx_prescriptions_deleted` (`deleted_at`), ADD CONSTRAINT `fk_prescriptions_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7.3 immunizations
SET @sql = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'immunizations') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'immunizations' AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `immunizations` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL, ADD COLUMN `deleted_by` INT NULL DEFAULT NULL, ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL, ADD INDEX `idx_immunizations_deleted` (`deleted_at`), ADD CONSTRAINT `fk_immunizations_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7.4 patient_medical_histories
SET @sql = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'patient_medical_histories') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'patient_medical_histories' AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `patient_medical_histories` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL, ADD COLUMN `deleted_by` INT NULL DEFAULT NULL, ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL, ADD INDEX `idx_pmh_deleted` (`deleted_at`), ADD CONSTRAINT `fk_pmh_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7.5 patient_conditions
SET @sql = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'patient_conditions') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'patient_conditions' AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `patient_conditions` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL, ADD COLUMN `deleted_by` INT NULL DEFAULT NULL, ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL, ADD INDEX `idx_patient_conditions_deleted` (`deleted_at`), ADD CONSTRAINT `fk_patient_conditions_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7.6 patient_surgeries
SET @sql = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'patient_surgeries') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'patient_surgeries' AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `patient_surgeries` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL, ADD COLUMN `deleted_by` INT NULL DEFAULT NULL, ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL, ADD INDEX `idx_patient_surgeries_deleted` (`deleted_at`), ADD CONSTRAINT `fk_patient_surgeries_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7.7 patient_external_immunizations
SET @sql = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'patient_external_immunizations') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'patient_external_immunizations' AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `patient_external_immunizations` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL, ADD COLUMN `deleted_by` INT NULL DEFAULT NULL, ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL, ADD INDEX `idx_patient_ext_imm_deleted` (`deleted_at`), ADD CONSTRAINT `fk_patient_ext_imm_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7.8 prenatal_records
SET @sql = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'prenatal_records') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'prenatal_records' AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `prenatal_records` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL, ADD COLUMN `deleted_by` INT NULL DEFAULT NULL, ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL, ADD INDEX `idx_prenatal_records_deleted` (`deleted_at`), ADD CONSTRAINT `fk_prenatal_records_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7.9 prenatal_visits
SET @sql = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'prenatal_visits') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'prenatal_visits' AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `prenatal_visits` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL, ADD COLUMN `deleted_by` INT NULL DEFAULT NULL, ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL, ADD INDEX `idx_prenatal_visits_deleted` (`deleted_at`), ADD CONSTRAINT `fk_prenatal_visits_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7.10 past_obstetric_histories
SET @sql = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'past_obstetric_histories') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'past_obstetric_histories' AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `past_obstetric_histories` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL, ADD COLUMN `deleted_by` INT NULL DEFAULT NULL, ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL, ADD INDEX `idx_poh_deleted` (`deleted_at`), ADD CONSTRAINT `fk_poh_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7.11 wellbaby_records
SET @sql = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'wellbaby_records') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'wellbaby_records' AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `wellbaby_records` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL, ADD COLUMN `deleted_by` INT NULL DEFAULT NULL, ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL, ADD INDEX `idx_wellbaby_records_deleted` (`deleted_at`), ADD CONSTRAINT `fk_wellbaby_records_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7.12 child_growth_logs
SET @sql = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'child_growth_logs') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'child_growth_logs' AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `child_growth_logs` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL, ADD COLUMN `deleted_by` INT NULL DEFAULT NULL, ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL, ADD INDEX `idx_cgl_deleted` (`deleted_at`), ADD CONSTRAINT `fk_cgl_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7.13 lab_requests
SET @sql = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'lab_requests') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'lab_requests' AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `lab_requests` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL, ADD COLUMN `deleted_by` INT NULL DEFAULT NULL, ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL, ADD INDEX `idx_lab_requests_deleted` (`deleted_at`), ADD CONSTRAINT `fk_lab_requests_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7.14 lab_results
SET @sql = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'lab_results') > 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'lab_results' AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `lab_results` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL, ADD COLUMN `deleted_by` INT NULL DEFAULT NULL, ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL, ADD INDEX `idx_lab_results_deleted` (`deleted_at`), ADD CONSTRAINT `fk_lab_results_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
