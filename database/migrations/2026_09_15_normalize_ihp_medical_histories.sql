-- Migration: 2026_09_15_normalize_ihp_medical_histories.sql
-- Description: Normalizes IHP medical history JSON/text fields into relational tables

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Create patient_conditions table (for Past and Family Medical History)
CREATE TABLE IF NOT EXISTS `patient_conditions` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `condition_type` ENUM('Past', 'Family') NOT NULL DEFAULT 'Past',
  `condition_name` VARCHAR(150) NOT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_patient_conditions_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_patient_conditions_patient_type` (`patient_id`, `condition_type`),
  INDEX `idx_patient_conditions_name` (`condition_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create patient_surgeries table
CREATE TABLE IF NOT EXISTS `patient_surgeries` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `procedure_name` VARCHAR(255) NOT NULL,
  `surgery_date` VARCHAR(50) DEFAULT NULL,
  `hospital` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_patient_surgeries_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_patient_surgeries_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create patient_external_immunizations table
CREATE TABLE IF NOT EXISTS `patient_external_immunizations` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'general',
  `vaccine_name` VARCHAR(150) NOT NULL,
  `administered_date` DATE DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_patient_ext_imm_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_patient_ext_imm_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Clean up patient_medical_histories JSON/TEXT columns
ALTER TABLE `patient_medical_histories`
  DROP COLUMN IF EXISTS `past_medical_history`,
  DROP COLUMN IF EXISTS `surgical_history`,
  DROP COLUMN IF EXISTS `family_history`,
  DROP COLUMN IF EXISTS `external_immunizations`;

SET FOREIGN_KEY_CHECKS = 1;
