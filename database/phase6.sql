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
