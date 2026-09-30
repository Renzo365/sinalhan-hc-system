-- Migration: Add priority_level to queue_entries
-- Date: 2026-09-30

ALTER TABLE `queue_entries` 
ADD COLUMN `priority_level` VARCHAR(30) NOT NULL DEFAULT 'Regular' AFTER `service_type`;
