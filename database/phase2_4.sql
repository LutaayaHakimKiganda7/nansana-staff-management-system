CREATE TABLE IF NOT EXISTS schools (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  type ENUM('primary','secondary') NOT NULL DEFAULT 'primary',
  location VARCHAR(160) NULL,
  po_box VARCHAR(60) NULL,
  uneb_centre_no VARCHAR(30) NULL,
  emis_code VARCHAR(30) NULL UNIQUE,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (name), INDEX (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS teachers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  registration_no VARCHAR(40) NOT NULL UNIQUE,
  file_no VARCHAR(40) NULL,
  surname VARCHAR(80) NOT NULL,
  first_name VARCHAR(80) NOT NULL,
  photo VARCHAR(120) NULL,
  date_of_birth DATE NOT NULL,
  date_opened DATE NULL,
  contact VARCHAR(40) NULL,
  date_employed DATE NOT NULL,
  department VARCHAR(80) NULL,
  designation VARCHAR(100) NOT NULL,
  retirement_age TINYINT UNSIGNED NOT NULL DEFAULT 60,
  retirement_year SMALLINT UNSIGNED NOT NULL,
  salary_scale VARCHAR(30) NULL,
  nssf_no VARCHAR(40) NULL,
  ipps_no VARCHAR(40) NULL UNIQUE,
  nin VARCHAR(20) NULL UNIQUE,
  tin VARCHAR(30) NULL,
  health_status VARCHAR(160) NULL,
  subjects VARCHAR(255) NULL,
  classes VARCHAR(255) NULL,
  school_id INT UNSIGNED NULL,
  school_since DATE NULL,
  payroll_status ENUM('on_payroll','off_payroll') NOT NULL DEFAULT 'on_payroll',
  termination_status ENUM('active','retired','terminated','deceased') NOT NULL DEFAULT 'active',
  biometrics_status ENUM('pending','captured') NOT NULL DEFAULT 'pending',
  verified TINYINT(1) NOT NULL DEFAULT 0,
  verified_by INT UNSIGNED NULL,
  verified_at DATETIME NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (surname,first_name), INDEX (school_id), INDEX (retirement_year), INDEX (payroll_status), INDEX (termination_status),
  FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS teacher_kin (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  relationship VARCHAR(40) NULL,
  contact VARCHAR(40) NULL,
  nin VARCHAR(20) NULL,
  FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS teacher_postings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT UNSIGNED NOT NULL,
  school_id INT UNSIGNED NOT NULL,
  designation VARCHAR(100) NULL,
  from_date DATE NOT NULL,
  to_date DATE NULL,
  INDEX (teacher_id),
  FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
  FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
