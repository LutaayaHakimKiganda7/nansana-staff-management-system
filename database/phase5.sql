CREATE TABLE IF NOT EXISTS movements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type ENUM('transfer','payroll_add','payroll_remove','retirement','termination') NOT NULL,
  teacher_id INT UNSIGNED NOT NULL,
  from_school_id INT UNSIGNED NULL,
  to_school_id INT UNSIGNED NULL,
  new_designation VARCHAR(100) NULL,
  outcome VARCHAR(20) NULL,
  effective_date DATE NOT NULL,
  reason TEXT NULL,
  status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  requested_by INT UNSIGNED NULL,
  requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  decided_by INT UNSIGNED NULL,
  decided_at DATETIME NULL,
  decision_note VARCHAR(255) NULL,
  INDEX (teacher_id), INDEX (status), INDEX (type),
  FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
