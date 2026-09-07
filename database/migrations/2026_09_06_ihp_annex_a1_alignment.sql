-- -------------------------------------------------------------
-- Migration: 2026_09_06_ihp_annex_a1_alignment.sql
-- Alignment with PhilHealth Annex A1 Individual Health Profile
-- -------------------------------------------------------------

-- 1. Align patients civil_status ENUM and add civil_status_other column
ALTER TABLE `patients` 
  MODIFY COLUMN `civil_status` ENUM('Single', 'Married', 'Widow/Widower', 'Annulled', 'Separated', 'Others') NOT NULL,
  ADD COLUMN IF NOT EXISTS `civil_status_other` VARCHAR(100) NULL AFTER `civil_status`;

-- 2. Align patients education_attainment ENUM
ALTER TABLE `patients` 
  MODIFY COLUMN `education_attainment` ENUM('No Schooling', 'Elementary', 'High School', 'Vocational', 'College degree, post graduate') DEFAULT NULL;

-- 3. Ensure patient_medical_histories has physical_examination and external_immunizations
ALTER TABLE `patient_medical_histories`
  ADD COLUMN IF NOT EXISTS `physical_examination` LONGTEXT NULL COMMENT 'JSON structured 6-system physical examination findings',
  ADD COLUMN IF NOT EXISTS `external_immunizations` LONGTEXT NULL COMMENT 'JSON structured Annex A1 lifetime immunization checklist';