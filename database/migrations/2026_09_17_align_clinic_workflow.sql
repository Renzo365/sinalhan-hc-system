-- Migration: Align System with Clinic Workflow
-- Date: 2026-09-17
-- Description: Adds envelope_no for physical logbook tracking and lineage for family medical history

-- 1. Add envelope_no to patients table
ALTER TABLE `patients` 
  ADD COLUMN `envelope_no` VARCHAR(50) DEFAULT NULL AFTER `patient_no`,
  ADD INDEX `idx_patients_envelope_no` (`envelope_no`);

-- 2. Add lineage to patient_conditions table
ALTER TABLE `patient_conditions` 
  ADD COLUMN `lineage` ENUM('Mother', 'Father', 'Both', 'Unknown') DEFAULT NULL AFTER `condition_type`;
