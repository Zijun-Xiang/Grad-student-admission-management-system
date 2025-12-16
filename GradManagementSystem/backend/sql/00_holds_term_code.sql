-- Adds term_code to holds table so holds can be tracked per semester.
-- Run in MySQL Workbench on database: grad_system
--
-- Safe to run multiple times (MySQL 5.7+).

SET @db := DATABASE();

SET @has_col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'holds' AND COLUMN_NAME = 'term_code'
);
SET @sql_col := IF(
  @has_col = 0,
  'ALTER TABLE holds ADD COLUMN term_code VARCHAR(32) NULL AFTER hold_type',
  'SELECT 1'
);
PREPARE stmt FROM @sql_col; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_idx := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'holds' AND INDEX_NAME = 'idx_holds_term'
);
SET @sql_idx := IF(
  @has_idx = 0,
  'CREATE INDEX idx_holds_term ON holds (term_code)',
  'SELECT 1'
);
PREPARE stmt FROM @sql_idx; EXECUTE stmt; DEALLOCATE PREPARE stmt;
