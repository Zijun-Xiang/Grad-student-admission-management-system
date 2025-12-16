-- Stores entry (admission) date and derived term code for any user (student/faculty/admin).
-- Run in MySQL Workbench on database: grad_system

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
