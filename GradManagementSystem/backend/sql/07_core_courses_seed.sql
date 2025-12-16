-- Core courses (CS undergrad prerequisites) + metadata columns (recommended).
-- Run in MySQL Workbench on database: grad_system
--
-- Notes:
-- - This table is used by My Courses and deficiency management.
-- - If you already have a `core_courses` table, this script will add `level` and `is_required` columns (MySQL 5.7+).
-- - `level` defaults to 'GRAD' so existing rows (e.g. CS690) won't become undergrad required.
-- - Undergrad required courses have `level='UG'` and `is_required=1`.
-- - Avoids MySQL warnings:
--   - No `TINYINT(1)` (display width deprecation warning)
--   - No `CREATE TABLE IF NOT EXISTS` (table-exists warning)

SET @db := DATABASE();

-- Create table only when missing (no "already exists" warning)
SET @has_tbl := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'core_courses'
);

SET @sql_create := IF(
  @has_tbl = 0,
  'CREATE TABLE core_courses (
     course_code VARCHAR(32) NOT NULL,
     course_name VARCHAR(255) NOT NULL,
     credits INT NOT NULL DEFAULT 3,
     status VARCHAR(32) NULL,
     level VARCHAR(16) NOT NULL DEFAULT ''GRAD'',
     is_required TINYINT UNSIGNED NOT NULL DEFAULT 0,
     PRIMARY KEY (course_code),
     KEY idx_core_courses_level (level),
     KEY idx_core_courses_required (is_required)
   )',
  'SELECT 1'
);
PREPARE stmt FROM @sql_create; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- If core_courses already exists without these columns (MySQL 5.7+ compatible)
SET @has_level := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'core_courses' AND COLUMN_NAME = 'level'
);
SET @sql_level := IF(
  @has_level = 0,
  'ALTER TABLE core_courses ADD COLUMN level VARCHAR(16) NOT NULL DEFAULT ''GRAD''',
  'SELECT 1'
);
PREPARE stmt FROM @sql_level; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_req := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'core_courses' AND COLUMN_NAME = 'is_required'
);
SET @sql_req := IF(
  @has_req = 0,
  'ALTER TABLE core_courses ADD COLUMN is_required TINYINT UNSIGNED NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE stmt FROM @sql_req; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Indexes (MySQL 5.7+ compatible conditional create)
SET @has_idx_level := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'core_courses' AND INDEX_NAME = 'idx_core_courses_level'
);
SET @sql_idx_level := IF(
  @has_idx_level = 0,
  'CREATE INDEX idx_core_courses_level ON core_courses (level)',
  'SELECT 1'
);
PREPARE stmt FROM @sql_idx_level; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_idx_req := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'core_courses' AND INDEX_NAME = 'idx_core_courses_required'
);
SET @sql_idx_req := IF(
  @has_idx_req = 0,
  'CREATE INDEX idx_core_courses_required ON core_courses (is_required)',
  'SELECT 1'
);
PREPARE stmt FROM @sql_idx_req; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Example CS undergrad core prerequisites (feel free to adjust to your curriculum)
INSERT IGNORE INTO core_courses (course_code, course_name, credits, level, is_required) VALUES
('CS200', 'Programming Fundamentals', 3, 'UG', 1),
('CS210', 'Discrete Structures', 3, 'UG', 1),
('CS220', 'Data Structures', 3, 'UG', 1),
('CS230', 'Computer Organization & Architecture', 3, 'UG', 1),
('CS240', 'Algorithms', 3, 'UG', 1),
('CS250', 'Operating Systems', 3, 'UG', 1),
('CS260', 'Database Systems', 3, 'UG', 1),
('CS270', 'Software Engineering', 3, 'UG', 1),
('CS280', 'Computer Networks', 3, 'UG', 1),
('CS290', 'Theory of Computation', 3, 'UG', 1);
