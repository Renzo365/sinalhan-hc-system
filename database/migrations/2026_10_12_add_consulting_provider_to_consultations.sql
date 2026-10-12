-- Migration: 2026_10_12_add_consulting_provider_to_consultations.sql
-- Description: Adds consulting_provider column to consultations table for documenting attending physicians and external healthcare providers.

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `consultations`
  ADD COLUMN `consulting_provider` VARCHAR(150) NULL DEFAULT NULL AFTER `status`;

SET FOREIGN_KEY_CHECKS = 1;
