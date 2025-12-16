-- Creates a table for "digital footprint" codes when holds are lifted.
-- Run in MySQL Workbench on database: grad_system

CREATE TABLE IF NOT EXISTS registrar_signals (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id BIGINT UNSIGNED NOT NULL,
  hold_type VARCHAR(64) NOT NULL,
  term_code VARCHAR(32) NULL,
  code VARCHAR(64) NOT NULL,
  created_by BIGINT UNSIGNED NULL,
  payload TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  confirmed_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_registrar_signals_code (code),
  KEY idx_registrar_signals_student (student_id),
  KEY idx_registrar_signals_hold (hold_type)
);

