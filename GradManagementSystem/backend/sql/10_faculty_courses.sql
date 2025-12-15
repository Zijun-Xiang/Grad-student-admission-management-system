-- Faculty taught courses mapping (Faculty Portal: "My Courses")
-- Safe to re-run. MySQL 5.7+ compatible.

SET @db := DATABASE();
SELECT COUNT(*) INTO @faculty_courses_exists
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'faculty_courses';

SET @sql := IF(
  @faculty_courses_exists = 0,
  'CREATE TABLE faculty_courses (
     id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
     faculty_id BIGINT UNSIGNED NOT NULL,
     course_code VARCHAR(32) NOT NULL,
     created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     PRIMARY KEY (id),
     UNIQUE KEY uniq_faculty_course (faculty_id, course_code),
     KEY idx_faculty_courses_faculty (faculty_id),
     KEY idx_faculty_courses_course (course_code)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
  'SELECT ''faculty_courses already exists'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
