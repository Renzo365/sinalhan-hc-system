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

DROP TABLE IF EXISTS `child_health_records`;
DROP TABLE IF EXISTS `maternal_records`;
DROP TABLE IF EXISTS `lab_results`;
DROP TABLE IF EXISTS `lab_requests`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `immunizations`;
DROP TABLE IF EXISTS `prescriptions`;
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
  `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
  `failed_attempts` INT NOT NULL DEFAULT 0,
  `must_change_password` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME DEFAULT NULL,
  `last_failed_login_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_username` (`username`),
  UNIQUE KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Patients Table
CREATE TABLE `patients` (
  `id` INT AUTO_INCREMENT,
  `patient_no` VARCHAR(20) NOT NULL,
  `first_name` VARCHAR(50) NOT NULL,
  `middle_name` VARCHAR(50) DEFAULT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `dob` DATE NOT NULL,
  `sex` ENUM('Male', 'Female') NOT NULL,
  `civil_status` ENUM('Single', 'Married', 'Widowed', 'Divorced', 'Separated') NOT NULL,
  `contact_no` VARCHAR(20) DEFAULT NULL,
  `barangay` VARCHAR(100) NOT NULL DEFAULT 'Sinalhan',
  `address` TEXT NOT NULL,
  `emergency_name` VARCHAR(100) DEFAULT NULL,
  `emergency_no` VARCHAR(20) DEFAULT NULL,
  `philhealth_no` VARCHAR(20) DEFAULT NULL,
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
  `oxygen_saturation` INT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `recorded_by` INT NOT NULL,
  `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_vitals_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_vitals_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
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
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_prescription_consultation` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_prescription_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_prescription_prescribed_by` FOREIGN KEY (`prescribed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Immunizations Table
CREATE TABLE `immunizations` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `vaccine_name` VARCHAR(100) NOT NULL,
  `dose_number` INT NOT NULL DEFAULT 1,
  `administered_date` DATE NOT NULL,
  `remarks` TEXT DEFAULT NULL,
  `administered_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_immunization_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_immunization_administered_by` FOREIGN KEY (`administered_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Audit Logs Table
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

-- 10. Settings Table
CREATE TABLE `settings` (
  `setting_key` VARCHAR(50) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`),
  CONSTRAINT `fk_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Lab Requests Table
CREATE TABLE `lab_requests` (
  `id` INT AUTO_INCREMENT,
  `consultation_id` INT NOT NULL,
  `patient_id` INT NOT NULL,
  `test_name` VARCHAR(100) NOT NULL,
  `status` ENUM('Pending', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
  `notes` TEXT DEFAULT NULL,
  `requested_by` INT NOT NULL,
  `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_lab_request_consultation` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lab_request_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_lab_request_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Lab Results Table
CREATE TABLE `lab_results` (
  `id` INT AUTO_INCREMENT,
  `lab_request_id` INT NOT NULL,
  `patient_id` INT NOT NULL,
  `result_details` TEXT NOT NULL,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `recorded_by` INT NOT NULL,
  `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_lab_result_request` FOREIGN KEY (`lab_request_id`) REFERENCES `lab_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lab_result_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_lab_result_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Maternal Records Table
CREATE TABLE `maternal_records` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `lmp` DATE DEFAULT NULL,
  `edc` DATE DEFAULT NULL,
  `gravida` INT DEFAULT NULL,
  `para` INT DEFAULT NULL,
  `abortion` INT DEFAULT NULL,
  `stillbirth` INT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_maternal_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_maternal_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Child Health Records Table
CREATE TABLE `child_health_records` (
  `id` INT AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `birth_weight_kg` DECIMAL(4,2) DEFAULT NULL,
  `birth_height_cm` DECIMAL(4,1) DEFAULT NULL,
  `delivery_type` VARCHAR(50) DEFAULT NULL,
  `place_of_delivery` VARCHAR(150) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_child_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_child_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Secondary Indexing for Performance Optimization
-- -------------------------------------------------------------

-- Patients search indexes (by name, birthday, sex, barangay, and archiving status)
CREATE INDEX `idx_patients_search` ON `patients` (`last_name`, `first_name`);
CREATE INDEX `idx_patients_dob` ON `patients` (`dob`);
CREATE INDEX `idx_patients_sex` ON `patients` (`sex`);
CREATE INDEX `idx_patients_barangay` ON `patients` (`barangay`);
CREATE INDEX `idx_patients_archived` ON `patients` (`deleted_at`);

-- Vital Signs indexes for history retrieval and charts
CREATE INDEX `idx_vitals_patient_date` ON `vital_signs` (`patient_id`, `recorded_at`);

-- Consultations indexes for history and soft delete filtering
CREATE INDEX `idx_consultation_patient_date` ON `consultations` (`patient_id`, `consulted_at`);
CREATE INDEX `idx_consultation_deleted` ON `consultations` (`deleted_at`);

-- Appointments indexes for daily dashboard scheduling, status, and duplication prevention
CREATE INDEX `idx_appointment_date_time` ON `appointments` (`appointment_date`, `appointment_time`);
CREATE INDEX `idx_appointment_status` ON `appointments` (`status`);
CREATE INDEX `idx_appointment_patient_date` ON `appointments` (`patient_id`, `appointment_date`);

-- Queue indexes for dashboard and public display polling
CREATE INDEX `idx_queue_daily` ON `queue_entries` (`queue_date`, `queue_no`);
CREATE INDEX `idx_queue_status_date` ON `queue_entries` (`queue_date`, `status`);

-- Immunization tracking index
CREATE INDEX `idx_immunization_lookup` ON `immunizations` (`patient_id`, `vaccine_name`);

-- Audit logs search indexes
CREATE INDEX `idx_audit_logs_search` ON `audit_logs` (`created_at`, `module`, `action`);
