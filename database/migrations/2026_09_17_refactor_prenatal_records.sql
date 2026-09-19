-- Migration: Refactor Prenatal Records to Align with IHP Master Record
-- Date: 2026-09-17
-- Description: Drops misplaced IHP columns from prenatal_records and ensures soft-delete audit columns exist.

ALTER TABLE `prenatal_records` 
  DROP COLUMN IF EXISTS `pre_eclampsia`,
  DROP COLUMN IF EXISTS `fp_counselling`;
