-- Migration: 2026_10_09_add_soft_delete_to_appointments.sql
-- Description: Adds deleted_at, deleted_by, and archive_reason columns to appointments for admin-only void/error soft-delete capability.

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `appointments`
  ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`,
  ADD COLUMN `deleted_by` INT NULL DEFAULT NULL AFTER `deleted_at`,
  ADD COLUMN `archive_reason` TEXT NULL DEFAULT NULL AFTER `deleted_by`,
  ADD INDEX `idx_appointments_deleted` (`deleted_at`),
  ADD CONSTRAINT `fk_appointments_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;
