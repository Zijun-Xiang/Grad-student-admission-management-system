-- Thesis/Project timeline table.
-- Rule is enforced in API: defense_date must be >= submission_date + 1 month.

CREATE TABLE IF NOT EXISTS thesis_projects (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id BIGINT UNSIGNED NOT NULL,
  type VARCHAR(16) NOT NULL DEFAULT 'thesis',
  title VARCHAR(255) NULL,
  submission_date DATE NOT NULL,
  defense_date DATE NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_thesis_projects_student (student_id)
);

