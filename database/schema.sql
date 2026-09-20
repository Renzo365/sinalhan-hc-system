-- -------------------------------------------------------------
-- Barangay Sinalhan Health Center - Patient Management System
-- Schema Initialization Script
-- -------------------------------------------------------------

CREATE DATABASE IF NOT EXISTS `sinalhan_hc_system` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `sinalhan_hc_system`;

-- Disable Foreign Key Checks temporarily to prevent drop order conflicts
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `pcb_service_logs`;
DROP TABLE IF EXISTS `pcb_obligated_services`;
DROP TABLE IF EXISTS `child_growth_logs`;
DROP TABLE IF EXISTS `wellbaby_records`;
DROP TABLE IF EXISTS `past_obstetric_histories`;
DROP TABLE IF EXISTS `prenatal_visits`;
DROP TABLE IF EXISTS `prenatal_records`;
DROP TABLE IF EXISTS `patient_external_immunizations`;
DROP TABLE IF EXISTS `patient_surgeries`;
DROP TABLE IF EXISTS `patient_conditions`;
DROP TABLE IF EXISTS `patient_medical_histories`;
DROP TABLE IF EXISTS `child_health_records`;
DROP TABLE IF EXISTS `maternal_records`;
DROP TABLE IF EXISTS `lab_results`;
DROP TABLE IF EXISTS `lab_requests`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `immunizations`;
DROP TABLE IF EXISTS `prescriptions`;
DROP TABLE IF EXISTS `queue_daily_counters`;
DROP TABLE IF EXISTS `queue_entries`;
DROP TABLE IF EXISTS `appointments`;
DROP TABLE IF EXISTS `consultations`;
DROP TABLE IF EXISTS `vital_signs`;
DROP TABLE IF EXISTS `patients`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'staff') NOT NULL,
  `first_name` VARCHAR(50) NOT NULL,
  `middle_name` VARCHAR(50) DEFAULT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `contact_no` VARCHAR(20) DEFAULT NULL,
  `job_title` VARCHAR(50) DEFAULT NULL,
  `employee_id` VARCHAR(50) DEFAULT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `failed_attempts` INT NOT NULL DEFAULT 0,
  `must_change_password` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME DEFAULT NULL,
  `last_failed_login_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_username` (`username`),
  UNIQUE KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Patients Table
CREATE TABLE `patients` (
  `id` INT AUTO_INCREMENT,
  `patient_no` VARCHAR(20) NOT NULL,
  `envelope_no` VARCHAR(50) DEFAULT NULL,
  `family_no` VARCHAR(50) DEFAULT NULL,
  `first_name` VARCHAR(50) NOT NULL,
  `middle_name` VARCHAR(50) DEFAULT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `suffix` VARCHAR(20) DEFAULT NULL,
  `dob` DATE NOT NULL,
  `sex` ENUM('Male', 'Female') NOT NULL,
  `civil_status` ENUM('Single', 'Married', 'Annulled', 'Widow/Widower', 'Separated', 'Others') NOT NULL,
  `civil_status_other` VARCHAR(100) DEFAULT NULL,
  `blood_type` ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown') NOT NULL DEFAULT 'Unknown',
  `religion` VARCHAR(100) DEFAULT NULL,
  `occupation` VARCHAR(100) DEFAULT NULL,
  `education_attainment` ENUM('No Schooling', 'Elementary', 'High School', 'Vocational', 'College degree, post graduate') DEFAULT NULL,
  `contact_no` VARCHAR(20) DEFAULT NULL,
  `barangay` VARCHAR(100) NOT NULL DEFAULT 'Sinalhan',
  `address` TEXT NOT NULL,
  `phic_status` ENUM('Member', 'Dependent', 'Non-Member') NOT NULL DEFAULT 'Non-Member',
  `phic_type` VARCHAR(100) DEFAULT NULL,
  `philhealth_no` VARCHAR(20) DEFAULT NULL,
  `father_name` VARCHAR(150) DEFAULT NULL,
  `father_dob` DATE DEFAULT NULL,
  `mother_name` VARCHAR(150) DEFAULT NULL,
  `mother_dob` DATE DEFAULT NULL,
  `spouse_name` VARCHAR(150) DEFAULT NULL,
  `spouse_dob` DATE DEFAULT NULL,
  `emergency_name` VARCHAR(100) DEFAULT NULL,
  `emergency_relationship` VARCHAR(50) DEFAULT NULL,
  `emergency_no` VARCHAR(20) DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_by` INT DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  `created_by` INT NOT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_patient_no` (`patient_no`),
  UNIQUE KEY `idx_philhealth` (`philhealth_no`),
  INDEX `idx_patients_envelope_no` (`envelope_no`),
  INDEX `idx_patients_family_no` (`family_no`),
  CONSTRAINT `fk_patients_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patients_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patients_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Vital Signs Table
CREATE TABLE `vital_signs` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `bp_systolic` INT DEFAULT NULL,
  `bp_diastolic` INT DEFAULT NULL,
  `heart_rate` INT DEFAULT NULL,
  `respiratory_rate` INT DEFAULT NULL,
  `temperature` DECIMAL(4,2) DEFAULT NULL,
  `weight` DECIMAL(5,2) DEFAULT NULL,
  `height` DECIMAL(5,2) DEFAULT NULL,
  `bmi` DECIMAL(4,2) DEFAULT NULL,
  `waist_circumference` DECIMAL(5,2) DEFAULT NULL,
  `oxygen_saturation` INT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `recorded_by` INT NOT NULL,
  `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_by` INT DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_vital_signs_deleted` (`deleted_at`),
  CONSTRAINT `fk_vitals_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_vitals_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_vital_signs_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Consultations Table
CREATE TABLE `consultations` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `vital_signs_id` INT DEFAULT NULL,
  `subjective` TEXT NOT NULL,
  `objective` TEXT NOT NULL,
  `assessment` TEXT NOT NULL,
  `plan` TEXT NOT NULL,
  `status` ENUM('Open', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Completed',
  `consulted_by` INT NOT NULL,
  `consulted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT NOT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_by` INT DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_consultation_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_vitals` FOREIGN KEY (`vital_signs_id`) REFERENCES `vital_signs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_consulted_by` FOREIGN KEY (`consulted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Appointments Table
CREATE TABLE `appointments` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `program_type` VARCHAR(50) DEFAULT 'General OPD',
  `appointment_date` DATE NOT NULL,
  `appointment_time` TIME NOT NULL,
  `purpose` VARCHAR(255) NOT NULL,
  `status` ENUM('Scheduled', 'Completed', 'Cancelled', 'Missed') NOT NULL DEFAULT 'Scheduled',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT NOT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_appointment_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_appointment_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_appointment_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Queue Entries Table
CREATE TABLE `queue_entries` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `service_type` VARCHAR(50) DEFAULT 'General OPD',
  `queue_date` DATE NOT NULL,
  `queue_no` INT NOT NULL,
  `status` ENUM('Waiting', 'Called', 'Serving', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Waiting',
  `time_in` TIME NOT NULL,
  `time_called` TIME DEFAULT NULL,
  `time_completed` TIME DEFAULT NULL,
  `created_by` INT NOT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_queue_date_no` (`queue_date`, `queue_no`),
  CONSTRAINT `fk_queue_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_queue_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_queue_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- 6a. Queue Daily Counters Table
CREATE TABLE `queue_daily_counters` (
  `queue_date` DATE NOT NULL,
  `last_queue_no` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`queue_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Prescriptions Table
CREATE TABLE `prescriptions` (
  `id` INT AUTO_INCREMENT,
  `consultation_id` INT NOT NULL,
  `patient_id` INT NOT NULL,
  `medicine_name` VARCHAR(150) NOT NULL,
  `dosage` VARCHAR(50) NOT NULL,
  `frequency` VARCHAR(50) NOT NULL,
  `duration` VARCHAR(50) NOT NULL,
  `instructions` TEXT DEFAULT NULL,
  `prescribed_by` INT NOT NULL,
  `prescribed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_by` INT DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_prescriptions_deleted` (`deleted_at`),
  CONSTRAINT `fk_prescription_consultation` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_prescription_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_prescription_prescribed_by` FOREIGN KEY (`prescribed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_prescriptions_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Immunizations Table
CREATE TABLE `immunizations` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `vaccine_name` VARCHAR(100) NOT NULL,
  `dose_number` INT NOT NULL DEFAULT 1,
  `administered_date` DATE NOT NULL,
  `source` ENUM('Health Center', 'External', 'Patient Reported', 'Unknown') NOT NULL DEFAULT 'Health Center',
  `documentation_status` ENUM('Administered', 'Reported', 'Unknown') NOT NULL DEFAULT 'Administered',
  `remarks` TEXT DEFAULT NULL,
  `administered_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_by` INT DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_immunizations_deleted` (`deleted_at`),
  CONSTRAINT `fk_immunization_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_immunization_administered_by` FOREIGN KEY (`administered_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_immunizations_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Patient Medical Histories (Annex A1 IHP Baseline & Demographics)
CREATE TABLE `patient_medical_histories` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL UNIQUE,
  `smoking_status` ENUM('Never', 'Yes', 'Quit') NULL DEFAULT 'Never',
  `smoking_pack_years` DECIMAL(4,1) NULL,
  `alcohol_status` ENUM('Never', 'Yes', 'Quit') NULL DEFAULT 'Never',
  `alcohol_bottles_per_day` DECIMAL(4,1) NULL,
  `illicit_drugs` TINYINT(1) NULL DEFAULT 0,
  `menarche_age` INT NULL,
  `sexual_onset_age` INT NULL,
  `lmp` DATE NULL,
  `period_duration_days` INT NULL,
  `cycle_interval_days` INT NULL,
  `pads_per_day` INT NULL,
  `is_menopausal` TINYINT(1) NULL DEFAULT 0,
  `menopause_age` INT NULL,
  `birth_control_method` VARCHAR(100) NULL,
  `baseline_bp_systolic` INT NULL,
  `baseline_bp_diastolic` INT NULL,
  `baseline_heart_rate` INT NULL,
  `baseline_respiratory_rate` INT NULL,
  `baseline_height` DECIMAL(5,2) NULL,
  `baseline_weight` DECIMAL(5,2) NULL,
  `baseline_waist_circumference` DECIMAL(5,2) NULL,
  `gravida` INT NULL,
  `para` INT NULL,
  `delivery_type` VARCHAR(100) NULL,
  `term_births` INT NULL,
  `preterm_births` INT NULL,
  `abortions` INT NULL,
  `living_children` INT NULL,
  `pre_eclampsia` TINYINT(1) NULL,
  `fp_counselling` TINYINT(1) NULL,
  `physical_examination` LONGTEXT NULL COMMENT 'JSON structured 6-system physical examination findings',
  `updated_by` INT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_by` INT DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_pmh_patient` (`patient_id`),
  INDEX `idx_pmh_deleted` (`deleted_at`),
  CONSTRAINT `fk_pmh_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pmh_updater` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pmh_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9a. Patient Conditions (Past Medical and Family Hereditary Illnesses)
CREATE TABLE `patient_conditions` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `condition_type` ENUM('Past', 'Family') NOT NULL DEFAULT 'Past',
  `lineage` ENUM('Mother', 'Father', 'Both', 'Unknown') DEFAULT NULL,
  `condition_name` VARCHAR(150) NOT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_by` INT DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_patient_conditions_patient_type` (`patient_id`, `condition_type`),
  INDEX `idx_patient_conditions_name` (`condition_name`),
  INDEX `idx_patient_conditions_deleted` (`deleted_at`),
  CONSTRAINT `fk_patient_conditions_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_conditions_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9b. Patient Surgeries (Surgical History)
CREATE TABLE `patient_surgeries` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `procedure_name` VARCHAR(255) NOT NULL,
  `surgery_date` VARCHAR(50) DEFAULT NULL,
  `hospital` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_by` INT DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_patient_surgeries_patient` (`patient_id`),
  INDEX `idx_patient_surgeries_deleted` (`deleted_at`),
  CONSTRAINT `fk_patient_surgeries_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_surgeries_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9c. Patient External Immunizations (IHP Annex A1 Lifetime Immunization Checklist)
CREATE TABLE `patient_external_immunizations` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'general',
  `vaccine_name` VARCHAR(150) NOT NULL,
  `administered_date` DATE DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_by` INT DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_patient_ext_imm_patient` (`patient_id`),
  INDEX `idx_patient_ext_imm_deleted` (`deleted_at`),
  CONSTRAINT `fk_patient_ext_imm_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_patient_ext_imm_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Prenatal Records (Maternal Pregnancy Episodes)
CREATE TABLE `prenatal_records` (
  `id` INT AUTO_INCREMENT,
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
  `delivery_date` DATE NULL,
  `delivery_outcome` ENUM('Live Birth', 'Stillbirth', 'Miscarriage', 'Ectopic', 'Other') NULL,
  `notes` TEXT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active_patient_id` INT GENERATED ALWAYS AS (IF(`is_active` = 1, `patient_id`, NULL)) STORED,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_by` INT DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pr_active_patient` (`active_patient_id`),
  INDEX `idx_pr_patient` (`patient_id`),
  INDEX `idx_pr_active` (`is_active`),
  INDEX `idx_pr_patient_active` (`patient_id`, `is_active`),
  INDEX `idx_prenatal_records_deleted` (`deleted_at`),
  CONSTRAINT `fk_pr_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  CONSTRAINT `fk_pr_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_prenatal_records_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Prenatal Visits (Serial Trimester Checkup Logs)
CREATE TABLE `prenatal_visits` (
  `id` INT AUTO_INCREMENT,
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
  `fetal_presentation` ENUM('Cephalic', 'Breech', 'Transverse', 'Undetermined') NULL DEFAULT 'Cephalic',
  `tcb` VARCHAR(100) NULL COMMENT 'Target Client Benefit / Tetanus status',
  `remarks` TEXT NULL,
  `attended_by` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_by` INT DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_pv_prenatal` (`prenatal_id`),
  INDEX `idx_pv_visit_date` (`visit_date`),
  INDEX `idx_prenatal_visits_deleted` (`deleted_at`),
  CONSTRAINT `fk_pv_prenatal` FOREIGN KEY (`prenatal_id`) REFERENCES `prenatal_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pv_attendant` FOREIGN KEY (`attended_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_prenatal_visits_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Past Obstetric Histories (Prior Delivery Matrix)
CREATE TABLE `past_obstetric_histories` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `gravida_no` INT NOT NULL,
  `delivery_type` ENUM('NSD', 'CS', 'Abortion', 'Other') NOT NULL DEFAULT 'NSD',
  `infant_sex` ENUM('Male', 'Female', 'Unknown') NULL DEFAULT 'Unknown',
  `place_of_delivery` VARCHAR(150) NULL,
  `year_delivered` INT NULL,
  `attended_by` VARCHAR(100) NULL,
  `status` ENUM('Alive', 'Not Alive') NOT NULL DEFAULT 'Alive',
  `birth_date` DATE NULL,
  `tt_status` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_by` INT DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_poh_patient` (`patient_id`),
  INDEX `idx_poh_deleted` (`deleted_at`),
  CONSTRAINT `fk_poh_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_poh_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Well Baby Records (Infant Birth Context & Child Health)
CREATE TABLE `wellbaby_records` (
  `id` INT AUTO_INCREMENT,
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
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_by` INT DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_wb_patient` (`patient_id`),
  INDEX `idx_wb_mother` (`mother_patient_id`),
  INDEX `idx_wellbaby_records_deleted` (`deleted_at`),
  CONSTRAINT `fk_wb_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wb_mother` FOREIGN KEY (`mother_patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_wb_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_wellbaby_records_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Child Growth Logs (Monthly Anthropometrics & EPI Immunizations)
CREATE TABLE `child_growth_logs` (
  `id` INT AUTO_INCREMENT,
  `wellbaby_id` INT NOT NULL,
  `log_date` DATE NOT NULL,
  `age_months` DECIMAL(4,1) NOT NULL,
  `weight_kg` DECIMAL(5,2) NOT NULL,
  `height_cm` DECIMAL(5,2) NOT NULL,
  `head_circumference_cm` DECIMAL(4,1) NULL,
  `chest_circumference_cm` DECIMAL(4,1) NULL,
  `temperature` DECIMAL(4,2) NULL,
  `feeding_method` ENUM('LAM / Exclusive Breastfeeding', 'Bottle Feed', 'Mixed') NOT NULL DEFAULT 'LAM / Exclusive Breastfeeding',
  `vaccines_administered` VARCHAR(255) NULL COMMENT 'Encounter note only; authoritative vaccine events are stored in immunizations',
  `vitamin_a_dose` TINYINT(1) NULL DEFAULT 0,
  `deworming_dose` TINYINT(1) NULL DEFAULT 0,
  `tcb_notes` TEXT NULL,
  `recorded_by` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_by` INT DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_cgl_wellbaby` (`wellbaby_id`),
  INDEX `idx_cgl_log_date` (`log_date`),
  INDEX `idx_cgl_deleted` (`deleted_at`),
  CONSTRAINT `fk_cgl_wellbaby` FOREIGN KEY (`wellbaby_id`) REFERENCES `wellbaby_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cgl_recorder` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cgl_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Lab Requests Table
CREATE TABLE `lab_requests` (
  `id` INT AUTO_INCREMENT,
  `consultation_id` INT NOT NULL,
  `patient_id` INT NOT NULL,
  `test_name` VARCHAR(100) NOT NULL,
  `status` ENUM('Pending', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
  `notes` TEXT DEFAULT NULL,
  `requested_by` INT NOT NULL,
  `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_by` INT DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_lab_requests_deleted` (`deleted_at`),
  CONSTRAINT `fk_lab_request_consultation` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lab_request_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_lab_request_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_lab_requests_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Lab Results Table
CREATE TABLE `lab_results` (
  `id` INT AUTO_INCREMENT,
  `lab_request_id` INT NOT NULL,
  `patient_id` INT NOT NULL,
  `result_details` TEXT NOT NULL,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `recorded_by` INT NOT NULL,
  `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_by` INT DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_lab_results_deleted` (`deleted_at`),
  CONSTRAINT `fk_lab_result_request` FOREIGN KEY (`lab_request_id`) REFERENCES `lab_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lab_result_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_lab_result_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_lab_results_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Settings Table
CREATE TABLE `settings` (
  `setting_key` VARCHAR(50) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`),
  CONSTRAINT `fk_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Audit Logs Table
CREATE TABLE `audit_logs` (
  `id` INT AUTO_INCREMENT,
  `user_id` INT DEFAULT NULL,
  `username` VARCHAR(50) DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. PCB Obligated Services Table (PhilHealth Page 3 Annual Surveillance)
CREATE TABLE `pcb_obligated_services` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `service_year` INT NOT NULL,
  `is_hypertensive` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0: Non-Hypertensive (Once a year), 1: Hypertensive (Once a month)',
  `bp_q1` DATE NULL,
  `bp_q2` DATE NULL,
  `bp_q3` DATE NULL,
  `bp_q4` DATE NULL,
  `cbe_q1` DATE NULL,
  `cbe_q2` DATE NULL,
  `cbe_q3` DATE NULL,
  `cbe_q4` DATE NULL,
  `via_q1` DATE NULL,
  `via_q2` DATE NULL,
  `via_q3` DATE NULL,
  `via_q4` DATE NULL,
  `remarks` TEXT NULL,
  `updated_by` INT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pcb_patient_year` (`patient_id`, `service_year`),
  INDEX `idx_pcb_year` (`service_year`),
  CONSTRAINT `fk_pcb_obligated_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pcb_obligated_updater` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. PCB Service Logs Table (PhilHealth Page 3 Encounter Ledger)
CREATE TABLE `pcb_service_logs` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `service_category` ENUM('Diagnostic', 'PCB1', 'Other') NOT NULL DEFAULT 'Diagnostic',
  `service_date` DATE NOT NULL,
  `diagnosis` VARCHAR(255) NULL,
  `service_type` VARCHAR(150) NOT NULL COMMENT 'Diagnostic test name or service type (e.g. CBC, Urinalysis, FBS, Chest X-ray, Counseling)',
  `status_given` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1: Service given in clinic, 0: Not given',
  `status_referred` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1: Referred, 0: Not referred',
  `referred_to` VARCHAR(150) NULL COMMENT 'Facility or specialist referred to',
  `remarks` TEXT NULL,
  `recorded_by` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_by` INT DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_pcb_logs_patient` (`patient_id`, `service_category`),
  INDEX `idx_pcb_logs_date` (`service_date`),
  INDEX `idx_pcb_service_logs_deleted` (`deleted_at`),
  CONSTRAINT `fk_pcb_logs_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pcb_logs_recorder` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pcb_service_logs_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Secondary Indexing for Performance Optimization
-- -------------------------------------------------------------

-- Patients search indexes
CREATE INDEX `idx_patients_search` ON `patients` (`last_name`, `first_name`);
CREATE INDEX `idx_patients_dob` ON `patients` (`dob`);
CREATE INDEX `idx_patients_sex` ON `patients` (`sex`);
CREATE INDEX `idx_patients_barangay` ON `patients` (`barangay`);
CREATE INDEX `idx_patients_archived` ON `patients` (`deleted_at`);

-- Vital Signs indexes
CREATE INDEX `idx_vitals_patient_date` ON `vital_signs` (`patient_id`, `recorded_at`);

-- Consultations indexes
CREATE INDEX `idx_consultation_patient_date` ON `consultations` (`patient_id`, `consulted_at`);
CREATE INDEX `idx_consultation_deleted` ON `consultations` (`deleted_at`);

-- Appointments indexes
CREATE INDEX `idx_appointment_date_time` ON `appointments` (`appointment_date`, `appointment_time`);
CREATE INDEX `idx_appointment_status` ON `appointments` (`status`);
CREATE INDEX `idx_appointment_patient_date` ON `appointments` (`patient_id`, `appointment_date`);

-- Queue indexes
CREATE INDEX `idx_queue_daily` ON `queue_entries` (`queue_date`, `queue_no`);
CREATE INDEX `idx_queue_status_date` ON `queue_entries` (`queue_date`, `status`);

-- Immunization tracking index
CREATE INDEX `idx_immunization_lookup` ON `immunizations` (`patient_id`, `vaccine_name`);

-- PCB Ledger search indexes
CREATE INDEX `idx_pcb_logs_category_date` ON `pcb_service_logs` (`service_category`, `service_date`);

-- Audit logs search indexes
CREATE INDEX `idx_audit_logs_search` ON `audit_logs` (`created_at`, `module`, `action`);
