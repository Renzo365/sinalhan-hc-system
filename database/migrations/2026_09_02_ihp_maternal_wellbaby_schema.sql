-- -------------------------------------------------------------
-- Barangay Sinalhan Health Center - Patient Management System
-- Migration: IHP, Maternal & Pre-Natal, and Well Baby Child Health
-- Date: 2026-09-02
-- -------------------------------------------------------------

USE `sinalhan_hc_system`;

-- Disable foreign key checks during migration
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- 1. Alter `patients` Table
-- -------------------------------------------------------------
-- Add household, socioeconomic, and immediate family columns if not present
SET @dbname = DATABASE();
SET @tablename = "patients";

-- Helper procedure to add column if it doesn't exist
DELIMITER $$
DROP PROCEDURE IF EXISTS AddColIfNotExists $$
CREATE PROCEDURE AddColIfNotExists(
    IN p_table VARCHAR(64),
    IN p_col VARCHAR(64),
    IN p_type_and_pos VARCHAR(255)
)
BEGIN
    IF NOT EXISTS (
        SELECT NULL 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = p_table 
          AND COLUMN_NAME = p_col
    ) THEN
        SET @query = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_col, '` ', p_type_and_pos);
        PREPARE stmt FROM @query;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$
DELIMITER ;

CALL AddColIfNotExists('patients', 'family_no', 'VARCHAR(50) NULL AFTER `patient_no`');
CALL AddColIfNotExists('patients', 'suffix', 'VARCHAR(20) NULL AFTER `last_name`');
CALL AddColIfNotExists('patients', 'religion', 'VARCHAR(100) NULL AFTER `blood_type`');
CALL AddColIfNotExists('patients', 'education_attainment', "ENUM('No Schooling', 'Elementary', 'High School', 'Vocational', 'College / Post-Graduate') NULL AFTER `occupation`");
CALL AddColIfNotExists('patients', 'phic_status', "ENUM('Member', 'Dependent', 'Non-Member') NOT NULL DEFAULT 'Non-Member' AFTER `address`");
CALL AddColIfNotExists('patients', 'phic_type', 'VARCHAR(100) NULL AFTER `phic_status`');
CALL AddColIfNotExists('patients', 'father_name', 'VARCHAR(150) NULL AFTER `philhealth_no`');
CALL AddColIfNotExists('patients', 'father_dob', 'DATE NULL AFTER `father_name`');
CALL AddColIfNotExists('patients', 'mother_name', 'VARCHAR(150) NULL AFTER `father_dob`');
CALL AddColIfNotExists('patients', 'mother_dob', 'DATE NULL AFTER `mother_name`');
CALL AddColIfNotExists('patients', 'spouse_name', 'VARCHAR(150) NULL AFTER `mother_dob`');
CALL AddColIfNotExists('patients', 'spouse_dob', 'DATE NULL AFTER `spouse_name`');

-- Add index on family_no for fast household clustering search
IF NOT EXISTS (
    SELECT NULL 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'patients' 
      AND INDEX_NAME = 'idx_patients_family_no'
) THEN
    ALTER TABLE `patients` ADD INDEX `idx_patients_family_no` (`family_no`);
END IF;

-- -------------------------------------------------------------
-- 2. Alter `vital_signs` Table
-- -------------------------------------------------------------
CALL AddColIfNotExists('vital_signs', 'waist_circumference', 'DECIMAL(5,2) NULL AFTER `bmi`');

-- Clean up helper procedure
DROP PROCEDURE IF EXISTS AddColIfNotExists;

-- -------------------------------------------------------------
-- 3. Table: `patient_medical_histories` (Annex A1 IHP Medical History)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `patient_medical_histories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL UNIQUE,
    `past_medical_history` TEXT NULL COMMENT 'JSON/Text structured checklist of chronic illnesses',
    `surgical_history` TEXT NULL COMMENT 'JSON/Text structured past operations and dates',
    `family_history` TEXT NULL COMMENT 'JSON/Text structured family hereditary illnesses',
    `smoking_status` ENUM('Never', 'Yes', 'Quit') NOT NULL DEFAULT 'Never',
    `smoking_pack_years` DECIMAL(4,1) NULL,
    `alcohol_status` ENUM('Never', 'Yes', 'Quit') NOT NULL DEFAULT 'Never',
    `alcohol_bottles_per_day` DECIMAL(4,1) NULL,
    `illicit_drugs` TINYINT(1) NOT NULL DEFAULT 0,
    `menarche_age` INT NULL,
    `sexual_onset_age` INT NULL,
    `lmp` DATE NULL,
    `period_duration_days` INT NULL,
    `cycle_interval_days` INT NULL,
    `pads_per_day` INT NULL,
    `is_menopausal` TINYINT(1) NOT NULL DEFAULT 0,
    `menopause_age` INT NULL,
    `birth_control_method` VARCHAR(100) NULL,
    `updated_by` INT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pmh_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pmh_updater` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_pmh_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 4. Table: `prenatal_records` (Maternal Pregnancy Episodes)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prenatal_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `husband_name` VARCHAR(150) NULL,
    `gravida` INT NOT NULL DEFAULT 1,
    `para` INT NOT NULL DEFAULT 0,
    `term_births` INT NOT NULL DEFAULT 0,
    `preterm_births` INT NOT NULL DEFAULT 0,
    `abortions` INT NOT NULL DEFAULT 0,
    `living_children` INT NOT NULL DEFAULT 0,
    `lmp` DATE NOT NULL,
    `edc` DATE NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `pre_eclampsia` TINYINT(1) NOT NULL DEFAULT 0,
    `fp_counselling` TINYINT(1) NOT NULL DEFAULT 1,
    `delivery_date` DATE NULL,
    `delivery_outcome` ENUM('Live Birth', 'Stillbirth', 'Miscarriage', 'Ectopic', 'Other') NULL,
    `notes` TEXT NULL,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pr_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_pr_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_pr_patient` (`patient_id`),
    INDEX `idx_pr_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 5. Table: `prenatal_visits` (Serial Trimester Checkup Logs)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prenatal_visits` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `prenatal_id` INT NOT NULL,
    `visit_date` DATE NOT NULL,
    `chief_complaint` VARCHAR(255) NULL,
    `aog_weeks` DECIMAL(4,1) NOT NULL,
    `bp_systolic` INT NULL,
    `bp_diastolic` INT NULL,
    `weight_kg` DECIMAL(5,2) NULL,
    `height_cm` DECIMAL(5,2) NULL,
    `fetal_heart_tone` INT NULL COMMENT 'FHT in bpm',
    `fundal_height_cm` DECIMAL(4,1) NULL COMMENT 'Fundal height in cm',
    `fetal_presentation` ENUM('Cephalic', 'Breech', 'Transverse', 'Undetermined') NOT NULL DEFAULT 'Cephalic',
    `tcb` VARCHAR(100) NULL COMMENT 'Target Client Benefit / Tetanus status',
    `remarks` TEXT NULL,
    `attended_by` INT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pv_prenatal` FOREIGN KEY (`prenatal_id`) REFERENCES `prenatal_records` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pv_attendant` FOREIGN KEY (`attended_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_pv_prenatal` (`prenatal_id`),
    INDEX `idx_pv_visit_date` (`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 6. Table: `past_obstetric_histories` (Prior Delivery Matrix)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `past_obstetric_histories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `gravida_no` INT NOT NULL,
    `delivery_type` ENUM('NSD', 'CS', 'Abortion', 'Other') NOT NULL DEFAULT 'NSD',
    `infant_sex` ENUM('Male', 'Female', 'Unknown') NOT NULL DEFAULT 'Unknown',
    `place_of_delivery` VARCHAR(150) NULL,
    `year_delivered` INT NULL,
    `attended_by` VARCHAR(100) NULL,
    `status` ENUM('Alive', 'Not Alive') NOT NULL DEFAULT 'Alive',
    `birth_date` DATE NULL,
    `tt_status` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_poh_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
    INDEX `idx_poh_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 7. Table: `wellbaby_records` (Infant Birth Context & Child Health)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wellbaby_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL UNIQUE,
    `mother_patient_id` INT NULL,
    `birth_time` TIME NULL,
    `birth_weight_kg` DECIMAL(4,2) NOT NULL,
    `birth_length_cm` DECIMAL(4,1) NOT NULL,
    `place_of_delivery` ENUM('Hospital', 'Lying-in', 'Home', 'Others') NOT NULL DEFAULT 'Lying-in',
    `delivery_type` ENUM('Normal Spontaneous Delivery (NSD)', 'Caesarean Section (CS)', 'Others') NOT NULL DEFAULT 'Normal Spontaneous Delivery (NSD)',
    `attended_by` ENUM('Doctor', 'Nurse', 'Midwife', 'Hilot/TBA', 'Others') NOT NULL DEFAULT 'Midwife',
    `newborn_screening_done` TINYINT(1) NOT NULL DEFAULT 0,
    `newborn_screening_date` DATE NULL,
    `newborn_screening_result` VARCHAR(100) NULL,
    `mother_cpab_tt` VARCHAR(100) NULL,
    `feeding_method` ENUM('LAM / Exclusive Breastfeeding', 'Bottle Feed', 'Mixed') NOT NULL DEFAULT 'LAM / Exclusive Breastfeeding',
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_wb_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_wb_mother` FOREIGN KEY (`mother_patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_wb_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_wb_patient` (`patient_id`),
    INDEX `idx_wb_mother` (`mother_patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 8. Table: `child_growth_logs` (Monthly Anthropometrics & EPI Immunizations)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `child_growth_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `wellbaby_id` INT NOT NULL,
    `log_date` DATE NOT NULL,
    `age_months` DECIMAL(4,1) NOT NULL,
    `weight_kg` DECIMAL(5,2) NOT NULL,
    `height_cm` DECIMAL(5,2) NOT NULL,
    `head_circumference_cm` DECIMAL(4,1) NULL,
    `chest_circumference_cm` DECIMAL(4,1) NULL,
    `temperature` DECIMAL(4,2) NULL,
    `feeding_method` ENUM('LAM / Exclusive Breastfeeding', 'Bottle Feed', 'Mixed') NOT NULL DEFAULT 'LAM / Exclusive Breastfeeding',
    `vaccines_administered` VARCHAR(255) NULL,
    `vitamin_a_dose` TINYINT(1) NOT NULL DEFAULT 0,
    `deworming_dose` TINYINT(1) NOT NULL DEFAULT 0,
    `tcb_notes` TEXT NULL,
    `recorded_by` INT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_cgl_wellbaby` FOREIGN KEY (`wellbaby_id`) REFERENCES `wellbaby_records` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cgl_recorder` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_cgl_wellbaby` (`wellbaby_id`),
    INDEX `idx_cgl_log_date` (`log_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;
