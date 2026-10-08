-- Migration: Relax PhilHealth PIN Unique Constraint
-- Description: Convert strict unique index on philhealth_no to a standard index so legal dependents (children, spouses) can share the principal member's 12-digit PIN in compliance with PhilHealth Konsulta / PCB regulations.

ALTER TABLE `patients` DROP INDEX `idx_philhealth`;
ALTER TABLE `patients` ADD INDEX `idx_philhealth` (`philhealth_no`);
