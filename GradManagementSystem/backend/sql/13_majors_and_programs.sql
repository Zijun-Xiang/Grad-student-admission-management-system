-- Adds "majors" (programs) support + stores each user's major in user_profiles,
-- and tags each core_courses row with a major_code for filtering.
-- Default for existing users/courses: CS (Computer Science).
--
-- Run in MySQL Workbench on database: grad_system

CREATE TABLE IF NOT EXISTS majors (
  major_code VARCHAR(16) NOT NULL,
  major_name VARCHAR(128) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (major_code),
  KEY idx_majors_active (is_active)
);

INSERT IGNORE INTO majors (major_code, major_name, is_active) VALUES
  ('CS', 'Computer Science', 1),
  ('SE', 'Software Engineering', 1),
  ('DS', 'Data Science', 1);

-- Ensure user_profiles exists and has major_code
CREATE TABLE IF NOT EXISTS user_profiles (
  user_id BIGINT UNSIGNED NOT NULL,
  entry_date DATE NULL,
  entry_term_code VARCHAR(32) NULL,
  major_code VARCHAR(16) NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  KEY idx_user_profiles_term (entry_term_code),
  KEY idx_user_profiles_major (major_code)
);

-- Add major_code to user_profiles if this DB was created with older schema
SET @db := DATABASE();
SELECT COUNT(*) INTO @has_major_col
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'user_profiles' AND COLUMN_NAME = 'major_code';
SET @sql := IF(@has_major_col = 0,
  'ALTER TABLE user_profiles ADD COLUMN major_code VARCHAR(16) NULL AFTER entry_term_code, ADD INDEX idx_user_profiles_major (major_code)',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE user_profiles SET major_code = 'CS' WHERE major_code IS NULL OR major_code = '';

-- Tag courses by major for major-based filtering
SELECT COUNT(*) INTO @has_cc_major
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'core_courses' AND COLUMN_NAME = 'major_code';
SET @sql2 := IF(@has_cc_major = 0,
  'ALTER TABLE core_courses ADD COLUMN major_code VARCHAR(16) NULL AFTER course_code, ADD INDEX idx_core_courses_major (major_code)',
  'SELECT 1');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

UPDATE core_courses SET major_code = 'CS' WHERE major_code IS NULL OR major_code = '';

