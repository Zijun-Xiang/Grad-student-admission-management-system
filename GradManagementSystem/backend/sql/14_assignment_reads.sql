USE grad_system;

CREATE TABLE IF NOT EXISTS assignment_reads (
  assignment_id BIGINT UNSIGNED NOT NULL,
  student_id BIGINT UNSIGNED NOT NULL,
  read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (assignment_id, student_id),
  KEY idx_assignment_reads_student (student_id),
  KEY idx_assignment_reads_read_at (read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

