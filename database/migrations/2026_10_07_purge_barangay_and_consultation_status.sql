-- Migration: Purge redundant barangay and make consultation status nullable
-- Description: Align schema with single full-address architecture and remove reliance on consultation statuses.

-- 1. Patients: Allow barangay to be NULL and default to NULL
ALTER TABLE `patients` MODIFY COLUMN `barangay` VARCHAR(100) NULL DEFAULT NULL;

-- 2. Consultations: Ensure status defaults to 'Completed' and allows NULL
ALTER TABLE `consultations` MODIFY COLUMN `status` ENUM('Open', 'Completed', 'Cancelled') NULL DEFAULT 'Completed';
