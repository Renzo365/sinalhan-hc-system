-- ============================================================
-- Migration: PhilHealth PCB Patient Ledger Tables (Page 3)
-- Date: 2026-09-07
-- Description: Creates pcb_obligated_services for annual quarterly 
--              preventive tracking (BP, CBE, VIA) and pcb_service_logs 
--              for Diagnostic, PCB1, and Other services encounters.
-- ============================================================

-- 1. Table: `pcb_obligated_services`
CREATE TABLE IF NOT EXISTS `pcb_obligated_services` (
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

-- 2. Table: `pcb_service_logs`
CREATE TABLE IF NOT EXISTS `pcb_service_logs` (
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
  PRIMARY KEY (`id`),
  INDEX `idx_pcb_logs_patient` (`patient_id`, `service_category`),
  INDEX `idx_pcb_logs_date` (`service_date`),
  CONSTRAINT `fk_pcb_logs_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pcb_logs_recorder` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;