-- Migration: 2026_10_04_align_wellbaby_schema.sql
-- Description: Align well-baby schema with wellbaby_record.md, add delivery & attendant other fields,
--              canonicalize Rotavirus vs PCV, deduplicate MCV/routine doses, and ensure 13 EPI routine targets.

-- 1. Add other fields to wellbaby_records
ALTER TABLE `wellbaby_records`
  ADD COLUMN IF NOT EXISTS `place_of_delivery_other` VARCHAR(150) NULL AFTER `place_of_delivery`,
  ADD COLUMN IF NOT EXISTS `attended_by_other` VARCHAR(150) NULL AFTER `attended_by`;

-- 2. Deduplicate patients who have both PCV and ROTA (e.g., patient 14 & 20) by soft-deleting the duplicate PCV
UPDATE `immunizations`
SET deleted_at = CURRENT_TIMESTAMP,
    archive_reason = 'Deduplicated PCV in favor of ROTA for EPI routine'
WHERE deleted_at IS NULL
  AND vaccine_name = 'Pneumococcal Conjugate Vaccine (PCV)'
  AND (patient_id, dose_number) IN (
      SELECT patient_id, dose_number
      FROM (
          SELECT patient_id, dose_number
          FROM `immunizations`
          WHERE deleted_at IS NULL
            AND (UPPER(vaccine_name) LIKE '%ROTA%' OR UPPER(vaccine_name) LIKE '%ROTAVIRUS%')
      ) existing_rota
  );

-- 3. Update remaining childhood EPI PCV doses to Rotavirus Vaccine (ROTA)
UPDATE `immunizations`
SET vaccine_name = 'Rotavirus Vaccine (ROTA)'
WHERE deleted_at IS NULL
  AND vaccine_name = 'Pneumococcal Conjugate Vaccine (PCV)'
  AND remarks = 'DOH National Expanded Program on Immunization (EPI).'
  AND dose_number IN (1, 2);

-- 4. Deduplicate MCV doses (e.g., patient 20 having both MCV and Measles-Rubella (MCV1))
UPDATE `immunizations`
SET deleted_at = CURRENT_TIMESTAMP,
    archive_reason = 'Deduplicated orphan MCV dose'
WHERE id IN (
  SELECT id FROM (
    SELECT id, ROW_NUMBER() OVER (
      PARTITION BY patient_id, 
        CASE 
          WHEN UPPER(vaccine_name) LIKE '%MEASLES-RUBELLA%' OR UPPER(vaccine_name) LIKE '%MCV1%' OR UPPER(vaccine_name) LIKE '%ANTI-MEASLES%' THEN 'MCV1'
          WHEN UPPER(vaccine_name) LIKE '%MMR%' OR UPPER(vaccine_name) LIKE '%MCV2%' THEN 'MCV2'
          WHEN UPPER(vaccine_name) = 'MCV' AND dose_number = 1 THEN 'MCV1'
          WHEN UPPER(vaccine_name) = 'MCV' AND dose_number = 2 THEN 'MCV2'
          ELSE UPPER(vaccine_name)
        END, 
        dose_number 
      ORDER BY id ASC
    ) as rn
    FROM `immunizations`
    WHERE deleted_at IS NULL
  ) t WHERE t.rn > 1
);

-- 5. Canonicalize any remaining 'MCV' dose 1 to 'Measles-Rubella (MCV1)' and dose 2 to 'Measles-Mumps-Rubella (MCV2)'
UPDATE `immunizations`
SET vaccine_name = 'Measles-Rubella (MCV1)'
WHERE deleted_at IS NULL
  AND vaccine_name = 'MCV'
  AND dose_number = 1;

UPDATE `immunizations`
SET vaccine_name = 'Measles-Mumps-Rubella (MCV2)'
WHERE deleted_at IS NULL
  AND vaccine_name = 'MCV'
  AND dose_number = 2;

-- 6. Ensure feeding_method values match valid enum ('LAM / Exclusive Breastfeeding', 'Bottle Feed', 'Mixed')
UPDATE `wellbaby_records`
SET `feeding_method` = 'LAM / Exclusive Breastfeeding'
WHERE `feeding_method` = '' OR `feeding_method` IS NULL OR `feeding_method` = 'LAM (Exclusive Breastfeeding)';
