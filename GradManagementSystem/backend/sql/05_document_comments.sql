-- Adds a comment thread for documents (faculty/admin feedback, student notes).
-- Run in MySQL Workbench on database: grad_system

CREATE TABLE IF NOT EXISTS document_comments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  doc_id BIGINT UNSIGNED NOT NULL,
  author_id BIGINT UNSIGNED NOT NULL,
  author_role VARCHAR(32) NOT NULL,
  comment TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_doc_comments_doc (doc_id),
  KEY idx_doc_comments_author (author_id)
);

