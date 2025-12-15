-- Add grading fields to assignment_submissions (safe to re-run).
-- Allows faculty to grade student submissions and students to see grades.

SET @db := DATABASE();

SELECT COUNT(*) INTO @tbl_exists
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'assignment_submissions';

SET @sql := IF(@tbl_exists = 0, 'SELECT ''assignment_submissions not found. Run backend/sql/09_assignments.sql first.'' AS info', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add grade column
SELECT COUNT(*) INTO @has_grade
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'assignment_submissions' AND COLUMN_NAME = 'grade';

SET @sql := IF(@has_grade = 0, 'ALTER TABLE assignment_submissions ADD COLUMN grade DECIMAL(6,2) NULL AFTER submitted_at', 'SELECT ''grade already exists'' AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add graded_at column
SELECT COUNT(*) INTO @has_graded_at
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'assignment_submissions' AND COLUMN_NAME = 'graded_at';

SET @sql := IF(@has_graded_at = 0, 'ALTER TABLE assignment_submissions ADD COLUMN graded_at DATETIME NULL AFTER grade', 'SELECT ''graded_at already exists'' AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add graded_by column
SELECT COUNT(*) INTO @has_graded_by
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'assignment_submissions' AND COLUMN_NAME = 'graded_by';

SET @sql := IF(@has_graded_by = 0, 'ALTER TABLE assignment_submissions ADD COLUMN graded_by BIGINT UNSIGNED NULL AFTER graded_at', 'SELECT ''graded_by already exists'' AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Optional index for reporting
SELECT COUNT(*) INTO @has_idx
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'assignment_submissions' AND INDEX_NAME = 'idx_submissions_grade';

SET @sql := IF(@has_idx = 0, 'ALTER TABLE assignment_submissions ADD INDEX idx_submissions_grade (grade)', 'SELECT ''idx_submissions_grade already exists'' AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

