-- Migration: Add appointment_id to queue_entries for appointment check-in tracking
-- Date: 2026-10-12

ALTER TABLE `queue_entries`
ADD COLUMN `appointment_id` INT(11) NULL DEFAULT NULL AFTER `patient_id`,
ADD INDEX `idx_queue_appointment_id` (`appointment_id`);
