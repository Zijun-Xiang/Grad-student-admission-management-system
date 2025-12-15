-- Assignments module (Faculty -> publish; Student -> submit; Faculty -> comment)
-- Safe to re-run. MySQL 5.7+ compatible.

SET @db := DATABASE();

-- assignments
SELECT COUNT(*) INTO @assignments_exists
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'assignments';

SET @sql := IF(
  @assignments_exists = 0,
  'CREATE TABLE assignments (
     id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
     created_by BIGINT UNSIGNED NOT NULL,
     title VARCHAR(255) NOT NULL,
     description TEXT NULL,
     due_at DATETIME NULL,
     attachment_path VARCHAR(255) NULL,
     created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     PRIMARY KEY (id),
     KEY idx_assignments_created_by (created_by),
     KEY idx_assignments_created_at (created_at)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
  'SELECT ''assignments already exists'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- assignment_targets
SELECT COUNT(*) INTO @assignment_targets_exists
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'assignment_targets';

SET @sql := IF(
  @assignment_targets_exists = 0,
  'CREATE TABLE assignment_targets (
     id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
     assignment_id BIGINT UNSIGNED NOT NULL,
     target_type VARCHAR(16) NOT NULL,
     target_value VARCHAR(64) NULL,
     PRIMARY KEY (id),
     KEY idx_assignment_targets_assignment (assignment_id),
     KEY idx_assignment_targets_lookup (target_type, target_value),
     CONSTRAINT fk_assignment_targets_assignment
       FOREIGN KEY (assignment_id) REFERENCES assignments(id)
       ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
  'SELECT ''assignment_targets already exists'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- assignment_submissions
SELECT COUNT(*) INTO @assignment_submissions_exists
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'assignment_submissions';

SET @sql := IF(
  @assignment_submissions_exists = 0,
  'CREATE TABLE assignment_submissions (
     id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
     assignment_id BIGINT UNSIGNED NOT NULL,
     student_id BIGINT UNSIGNED NOT NULL,
     file_path VARCHAR(255) NOT NULL,
     submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     PRIMARY KEY (id),
     UNIQUE KEY uniq_submission (assignment_id, student_id),
     KEY idx_submissions_assignment (assignment_id),
     KEY idx_submissions_student (student_id),
     CONSTRAINT fk_assignment_submissions_assignment
       FOREIGN KEY (assignment_id) REFERENCES assignments(id)
       ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
  'SELECT ''assignment_submissions already exists'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- assignment_submission_comments
SELECT COUNT(*) INTO @assignment_submission_comments_exists
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'assignment_submission_comments';

SET @sql := IF(
  @assignment_submission_comments_exists = 0,
  'CREATE TABLE assignment_submission_comments (
     id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
     submission_id BIGINT UNSIGNED NOT NULL,
     author_id BIGINT UNSIGNED NOT NULL,
     comment TEXT NOT NULL,
     created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     PRIMARY KEY (id),
     KEY idx_assignment_comments_submission (submission_id),
     KEY idx_assignment_comments_created_at (created_at),
     CONSTRAINT fk_assignment_comments_submission
       FOREIGN KEY (submission_id) REFERENCES assignment_submissions(id)
       ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
  'SELECT ''assignment_submission_comments already exists'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
