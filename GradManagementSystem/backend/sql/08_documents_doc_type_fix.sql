-- Ensure documents.doc_type can store values like 'major_professor_form' without truncation.
-- Safe to re-run. Compatible with MySQL 5.7+.

SET @db := DATABASE();

SELECT COUNT(*) INTO @tbl_exists
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'documents';

SET @col_type := NULL;
SET @is_nullable := NULL;

SELECT COLUMN_TYPE, IS_NULLABLE
INTO @col_type, @is_nullable
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'documents' AND COLUMN_NAME = 'doc_type'
LIMIT 1;

SET @col_type_lc := LOWER(IFNULL(@col_type, ''));
SET @len := CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(@col_type_lc, '(', -1), ')', 1) AS UNSIGNED);
SET @null_sql := IF(@is_nullable = 'YES', 'NULL', 'NOT NULL');

SET @needs_upgrade := CASE
  WHEN @tbl_exists = 0 THEN 0
  WHEN @col_type IS NULL THEN 0
  WHEN @col_type_lc LIKE 'enum(%' THEN 1
  WHEN @col_type_lc LIKE 'varchar(%' AND @len > 0 AND @len < 20 THEN 1
  WHEN @col_type_lc LIKE 'char(%' AND @len > 0 AND @len < 20 THEN 1
  ELSE 0
END;

SET @sql := IF(@needs_upgrade = 1, CONCAT('ALTER TABLE documents MODIFY COLUMN doc_type VARCHAR(64) ', @null_sql), 'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
