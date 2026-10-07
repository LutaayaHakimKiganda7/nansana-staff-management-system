CREATE TABLE IF NOT EXISTS quarterly_verifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT UNSIGNED NOT NULL,
  year SMALLINT UNSIGNED NOT NULL,
  quarter TINYINT UNSIGNED NOT NULL,
  consented_at DATETIME NULL,
  verified_by INT UNSIGNED NULL,
  verified_at DATETIME NULL,
  fingerprint_count TINYINT UNSIGNED NULL,
  note VARCHAR(255) NULL,
  UNIQUE KEY teacher_period (teacher_id,year,quarter),
  INDEX (year,quarter,verified_at),
  FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quarterly_biometric_samples (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT UNSIGNED NOT NULL,
  year SMALLINT UNSIGNED NOT NULL,
  quarter TINYINT UNSIGNED NOT NULL,
  slot VARCHAR(20) NOT NULL,
  quality TINYINT UNSIGNED NULL,
  hash CHAR(64) NOT NULL,
  payload MEDIUMTEXT NOT NULL,
  captured_by INT UNSIGNED NULL,
  captured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY teacher_period_slot (teacher_id,year,quarter,slot),
  INDEX (hash),
  FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
