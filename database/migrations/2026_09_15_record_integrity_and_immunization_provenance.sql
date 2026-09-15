-- Record integrity and immunization provenance.
-- Take a database backup and verify the target database before applying.
-- The guards make this migration safe to retry after an interrupted upgrade.

SET @db_name = DATABASE();

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `immunizations` ADD COLUMN `source` ENUM(''Health Center'', ''External'', ''Patient Reported'', ''Unknown'') NOT NULL DEFAULT ''Health Center'' AFTER `administered_date`',
    'SELECT 1'
  )
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'immunizations'
    AND COLUMN_NAME = 'source'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `immunizations` ADD COLUMN `documentation_status` ENUM(''Administered'', ''Reported'', ''Unknown'') NOT NULL DEFAULT ''Administered'' AFTER `source`',
    'SELECT 1'
  )
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'immunizations'
    AND COLUMN_NAME = 'documentation_status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `prenatal_records` ADD INDEX `idx_pr_patient_active` (`patient_id`, `is_active`)',
    'SELECT 1'
  )
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'prenatal_records'
    AND INDEX_NAME = 'idx_pr_patient_active'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
