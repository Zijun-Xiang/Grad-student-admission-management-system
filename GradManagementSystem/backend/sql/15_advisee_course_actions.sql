-- Advisor -> student course action requests (add/drop) with optional comments.
-- Students apply the request from My Courses (used for event notifications).

CREATE TABLE IF NOT EXISTS advisee_course_actions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  faculty_id BIGINT UNSIGNED NOT NULL,
  student_id BIGINT UNSIGNED NOT NULL,
  action_type ENUM('add','drop') NOT NULL,
  course_code VARCHAR(32) NOT NULL,
  comment TEXT NULL,
  status ENUM('pending','applied','cancelled') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  applied_at DATETIME NULL,
  cancelled_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_aca_student_status (student_id, status, created_at),
  KEY idx_aca_faculty_status (faculty_id, status, created_at),
  KEY idx_aca_course (course_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

