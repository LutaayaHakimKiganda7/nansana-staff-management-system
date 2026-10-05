CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  phone VARCHAR(30) NULL,
  role ENUM('admin','meo','records','payroll','viewer') NOT NULL DEFAULT 'viewer',
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  last_login DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  user_name VARCHAR(120) NOT NULL,
  action VARCHAR(60) NOT NULL,
  entity VARCHAR(60) NULL,
  entity_id VARCHAR(40) NULL,
  old_values JSON NULL,
  new_values JSON NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (created_at), INDEX (user_id), INDEX (action), INDEX (entity, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(60) PRIMARY KEY,
  v TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO settings (k,v) VALUES
 ('system_name','MGTM System'),
 ('system_full_name','Municipal Government Teachers Management System'),
 ('municipality','Your Municipality'),
 ('district','Your District'),
 ('contact_email',''),
 ('contact_phone','');

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


ALTER TABLE teachers ADD COLUMN email VARCHAR(160) NULL AFTER contact;

CREATE TABLE IF NOT EXISTS integrations (
  provider VARCHAR(20) PRIMARY KEY,
  config MEDIUMTEXT NOT NULL,
  updated_by INT UNSIGNED NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS message_templates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL,
  channel ENUM('sms','email') NOT NULL,
  name VARCHAR(80) NOT NULL,
  subject VARCHAR(160) NULL,
  body TEXT NOT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY code_channel (code,channel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS message_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  channel ENUM('sms','email') NOT NULL,
  recipient VARCHAR(160) NOT NULL,
  subject VARCHAR(160) NULL,
  body TEXT NULL,
  status ENUM('sent','failed') NOT NULL,
  error VARCHAR(255) NULL,
  template_code VARCHAR(40) NULL,
  teacher_id INT UNSIGNED NULL,
  user_id INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (created_at), INDEX (teacher_id), INDEX (channel,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO settings (k,v) VALUES ('retirement_notice_days','90');

INSERT IGNORE INTO message_templates (code,channel,name,subject,body) VALUES
('transfer_approved','sms','Transfer approved',NULL,'Dear {name}, you have been transferred from {old_school} to {new_school} effective {date}. {municipality} Education Office.'),
('transfer_approved','email','Transfer approved','Transfer notice','Dear {name},

You have been transferred from {old_school} to {new_school}, effective {date}.

{municipality} Education Office'),
('payroll_added','sms','Added to payroll',NULL,'Dear {name}, you have been added to the payroll effective {date}. {municipality} Education Office.'),
('payroll_added','email','Added to payroll','Payroll update','Dear {name},

You have been added to the payroll, effective {date}.

{municipality} Education Office'),
('payroll_removed','sms','Removed from payroll',NULL,'Dear {name}, you have been removed from the payroll effective {date}. Contact the {municipality} Education Office on {office_phone} for details.'),
('payroll_removed','email','Removed from payroll','Payroll update','Dear {name},

You have been removed from the payroll, effective {date}. Please contact the education office on {office_phone} for details.

{municipality} Education Office'),
('retirement_approved','sms','Retirement approved',NULL,'Dear {name}, your retirement has been approved effective {date}. Thank you for your service. {municipality} Education Office.'),
('retirement_approved','email','Retirement approved','Retirement notice','Dear {name},

Your retirement has been approved, effective {date}. Thank you for your service to education.

{municipality} Education Office'),
('retirement_reminder','sms','Retirement reminder',NULL,'Dear {name}, our records show you retire on {date}. Please contact the {municipality} Education Office to prepare your retirement documents.'),
('retirement_reminder','email','Retirement reminder','Your upcoming retirement','Dear {name},

Our records show that you are due to retire on {date}. Please contact the education office on {office_phone} to prepare your retirement documents.

{municipality} Education Office')


ALTER TABLE teachers ADD COLUMN biometric_consent_at DATETIME NULL;

CREATE TABLE IF NOT EXISTS biometric_samples (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT UNSIGNED NOT NULL,
  slot VARCHAR(20) NOT NULL,
  quality TINYINT UNSIGNED NULL,
  hash CHAR(64) NOT NULL,
  payload MEDIUMTEXT NOT NULL,
  captured_by INT UNSIGNED NULL,
  captured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY teacher_slot (teacher_id,slot),
  INDEX (hash),
  FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4