-- Add login attempts tracking
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,
    INDEX idx_username_ip (username, ip_address),
    INDEX idx_attempt_time (attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add system settings table
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add system maintenance logs
CREATE TABLE IF NOT EXISTS maintenance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action_type ENUM('backup', 'restore', 'update', 'maintenance', 'other') NOT NULL,
    description TEXT,
    status ENUM('started', 'completed', 'failed') NOT NULL,
    performed_by INT,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add user activity logs
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_action (user_id, action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error') NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add academic terms table
CREATE TABLE IF NOT EXISTS academic_terms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(9) NOT NULL,
    term ENUM('First Semester', 'Second Semester', 'Summer') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('upcoming', 'active', 'completed') NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_term (academic_year, term),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add default system settings
INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES
('maintenance_mode', 'false', 'System maintenance mode status'),
('grade_submission_deadline', '7', 'Days allowed for grade submission after term end'),
('min_attendance_percentage', '75', 'Minimum required attendance percentage'),
('max_units_per_semester', '24', 'Maximum units allowed per semester'),
('grading_scale', 'JSON:{"A":95,"B":85,"C":75,"D":65,"F":0}', 'Grade scale configuration'),
('academic_year', CONCAT(YEAR(CURDATE()), '-', YEAR(CURDATE()) + 1), 'Current academic year'),
('current_term', 'First Semester', 'Current academic term'),
('system_email', 'system@school.edu', 'System email address for notifications'),
('backup_retention_days', '30', 'Number of days to retain system backups'),
('password_expiry_days', '90', 'Days before password expiration');

-- Add indexes to existing tables for better performance
ALTER TABLE grades
ADD INDEX idx_student_subject_term (student_id, subject_id, academic_year, term);

ALTER TABLE attendance
ADD INDEX idx_student_subject_date (student_id, subject_id, date);

ALTER TABLE reports
ADD INDEX idx_status_date (status, submission_date);

-- Add constraints to prevent invalid data
ALTER TABLE grades
ADD CONSTRAINT check_score
CHECK (score >= 0 AND score <= 100);

ALTER TABLE student_subjects
ADD CONSTRAINT check_enrollment_date
CHECK (enrollment_date <= CURDATE()); 