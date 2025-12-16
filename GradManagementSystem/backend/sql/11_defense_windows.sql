-- Admin-published defense date window per year.
-- Students must pick a defense_date inside the published window for that year.

CREATE TABLE IF NOT EXISTS defense_windows (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  year INT NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_defense_windows_year (year),
  KEY idx_defense_windows_start (start_date),
  KEY idx_defense_windows_end (end_date)
);

