-- Migration: 2026_09_15_add_soft_deletes_to_clinical_tables.sql
-- Description: Adds deleted_at, deleted_by, and archive_reason columns to clinical sub-records for legal audit and soft-delete compliance.

SET FOREIGN_KEY_CHECKS = 0;

-- 1. vital_signs
ALTER TABLE `vital_signs`
  ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN `deleted_by` INT NULL DEFAULT NULL,
  ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL,
  ADD INDEX `idx_vital_signs_deleted` (`deleted_at`),
  ADD CONSTRAINT `fk_vital_signs_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;

-- 2. prescriptions
ALTER TABLE `prescriptions`
  ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN `deleted_by` INT NULL DEFAULT NULL,
  ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL,
  ADD INDEX `idx_prescriptions_deleted` (`deleted_at`),
  ADD CONSTRAINT `fk_prescriptions_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;

-- 3. immunizations
ALTER TABLE `immunizations`
  ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN `deleted_by` INT NULL DEFAULT NULL,
  ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL,
  ADD INDEX `idx_immunizations_deleted` (`deleted_at`),
  ADD CONSTRAINT `fk_immunizations_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;

-- 4. patient_medical_histories
ALTER TABLE `patient_medical_histories`
  ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN `deleted_by` INT NULL DEFAULT NULL,
  ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL,
  ADD INDEX `idx_pmh_deleted` (`deleted_at`),
  ADD CONSTRAINT `fk_pmh_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;

-- 5. patient_conditions
ALTER TABLE `patient_conditions`
  ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN `deleted_by` INT NULL DEFAULT NULL,
  ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL,
  ADD INDEX `idx_patient_conditions_deleted` (`deleted_at`),
  ADD CONSTRAINT `fk_patient_conditions_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;

-- 6. patient_surgeries
ALTER TABLE `patient_surgeries`
  ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN `deleted_by` INT NULL DEFAULT NULL,
  ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL,
  ADD INDEX `idx_patient_surgeries_deleted` (`deleted_at`),
  ADD CONSTRAINT `fk_patient_surgeries_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;

-- 7. patient_external_immunizations
ALTER TABLE `patient_external_immunizations`
  ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN `deleted_by` INT NULL DEFAULT NULL,
  ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL,
  ADD INDEX `idx_patient_ext_imm_deleted` (`deleted_at`),
  ADD CONSTRAINT `fk_patient_ext_imm_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;

-- 8. prenatal_records
ALTER TABLE `prenatal_records`
  ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN `deleted_by` INT NULL DEFAULT NULL,
  ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL,
  ADD INDEX `idx_prenatal_records_deleted` (`deleted_at`),
  ADD CONSTRAINT `fk_prenatal_records_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;

-- 9. prenatal_visits
ALTER TABLE `prenatal_visits`
  ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN `deleted_by` INT NULL DEFAULT NULL,
  ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL,
  ADD INDEX `idx_prenatal_visits_deleted` (`deleted_at`),
  ADD CONSTRAINT `fk_prenatal_visits_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;

-- 10. past_obstetric_histories
ALTER TABLE `past_obstetric_histories`
  ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN `deleted_by` INT NULL DEFAULT NULL,
  ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL,
  ADD INDEX `idx_poh_deleted` (`deleted_at`),
  ADD CONSTRAINT `fk_poh_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;

-- 11. wellbaby_records
ALTER TABLE `wellbaby_records`
  ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN `deleted_by` INT NULL DEFAULT NULL,
  ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL,
  ADD INDEX `idx_wellbaby_records_deleted` (`deleted_at`),
  ADD CONSTRAINT `fk_wellbaby_records_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;

-- 12. child_growth_logs
ALTER TABLE `child_growth_logs`
  ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN `deleted_by` INT NULL DEFAULT NULL,
  ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL,
  ADD INDEX `idx_cgl_deleted` (`deleted_at`),
  ADD CONSTRAINT `fk_cgl_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;

-- 13. lab_requests
ALTER TABLE `lab_requests`
  ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN `deleted_by` INT NULL DEFAULT NULL,
  ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL,
  ADD INDEX `idx_lab_requests_deleted` (`deleted_at`),
  ADD CONSTRAINT `fk_lab_requests_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;

-- 14. lab_results
ALTER TABLE `lab_results`
  ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN `deleted_by` INT NULL DEFAULT NULL,
  ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL,
  ADD INDEX `idx_lab_results_deleted` (`deleted_at`),
  ADD CONSTRAINT `fk_lab_results_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;
