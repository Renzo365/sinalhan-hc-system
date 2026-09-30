-- Migration: Drop lineage from patient_conditions
-- Description: Completely removes the non-standard lineage column from patient_conditions to strictly align with PhilHealth Annex A1 specification.

ALTER TABLE `patient_conditions` DROP COLUMN `lineage`;
