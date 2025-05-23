-- Drop database if exists and create new
DROP DATABASE IF EXISTS academicperfsystem;
CREATE DATABASE academicperfsystem;
USE academicperfsystem;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create teachers table
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    teacher_id VARCHAR(20) UNIQUE NOT NULL,
    department VARCHAR(100) NOT NULL,
    specialization VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create students table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    course VARCHAR(100) NOT NULL,
    year_level INT NOT NULL,
    section VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create subjects table
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) UNIQUE NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    description TEXT,
    units INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create academic_terms table
CREATE TABLE academic_terms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(9) NOT NULL,
    term ENUM('First Semester', 'Second Semester', 'Summer') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('upcoming', 'active', 'completed') NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create activities table
CREATE TABLE activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    activity_type ENUM('quiz', 'assignment', 'exam', 'project', 'other') NOT NULL,
    scheduled_date DATE NOT NULL,
    due_date DATE,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES teachers(id) ON DELETE CASCADE,
    INDEX idx_scheduled_date (scheduled_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create activity_logs table
CREATE TABLE activity_logs (
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

-- Create curriculum table
CREATE TABLE curriculum (
    id INT AUTO_INCREMENT PRIMARY KEY,
    degree_program VARCHAR(50) NOT NULL,
    year_level VARCHAR(20) NOT NULL,
    subject_id INT NOT NULL,
    semester ENUM('First Semester', 'Second Semester') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_curriculum (degree_program, year_level, subject_id, semester),
    INDEX idx_program_year (degree_program, year_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create student_subjects table
CREATE TABLE student_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    academic_year VARCHAR(9) NOT NULL,
    term ENUM('First Semester', 'Second Semester', 'Summer') NOT NULL,
    status ENUM('enrolled', 'completed', 'dropped', 'failed') DEFAULT 'enrolled',
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, subject_id, academic_year, term)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create grades table
CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    category ENUM('written', 'performance', 'exams') NOT NULL,
    grade_type VARCHAR(20) NOT NULL,
    score DECIMAL(5,2) NOT NULL CHECK (score >= 0 AND score <= 100),
    attempt_number INT DEFAULT 1,
    academic_year VARCHAR(9) NOT NULL,
    term VARCHAR(20) NOT NULL,
    remarks TEXT,
    graded_by INT NOT NULL,
    graded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES teachers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_grade_attempt (student_id, subject_id, category, grade_type, attempt_number, academic_year, term)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create grade_history table
CREATE TABLE grade_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grade_id INT NOT NULL,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    category ENUM('written', 'performance', 'exams') NOT NULL,
    grade_type VARCHAR(20) NOT NULL,
    old_score DECIMAL(5,2),
    new_score DECIMAL(5,2) NOT NULL,
    modified_by INT NOT NULL,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reason TEXT,
    FOREIGN KEY (grade_id) REFERENCES grades(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (modified_by) REFERENCES teachers(id) ON DELETE CASCADE,
    INDEX idx_grade_id (grade_id),
    INDEX idx_modified_at (modified_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create attendance table
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') NOT NULL,
    remarks TEXT,
    recorded_by INT NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES teachers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (student_id, subject_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create login_attempts table
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) DEFAULT 0,
    INDEX idx_username_ip (username, ip_address),
    INDEX idx_attempt_time (attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create login_history table
CREATE TABLE login_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    login_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_login_history_user (user_id),
    INDEX idx_login_history_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create maintenance_logs table
CREATE TABLE maintenance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action_type ENUM('backup', 'restore', 'update', 'maintenance', 'other') NOT NULL,
    description TEXT,
    status ENUM('started', 'completed', 'failed') NOT NULL,
    performed_by INT,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error') NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create password_resets table
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_token (token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create remember_tokens table
CREATE TABLE remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_token (token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create reports table
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_type ENUM('term', 'progress', 'comprehensive', 'special') NOT NULL,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    academic_year VARCHAR(9) NOT NULL,
    term VARCHAR(20) NOT NULL,
    content TEXT NOT NULL,
    generated_by INT NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_report_type (report_type),
    INDEX idx_student_subject (student_id, subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create report_requests table
CREATE TABLE report_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    request_type ENUM('term', 'progress', 'comprehensive', 'special') NOT NULL,
    term_period ENUM('preliminary', 'midterm', 'semi_final', 'final'),
    request_reason TEXT,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    response_date TIMESTAMP NULL,
    response_by INT,
    response_notes TEXT,
    report_id INT,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (response_by) REFERENCES teachers(id) ON DELETE SET NULL,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_request_date (request_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create system_settings table
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    description TEXT,
    is_public TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Users
INSERT INTO users (id, email, password, full_name, role, status, created_at) VALUES 
(1, 'marvin.ramos@school.edu', '$2y$10$JzTCD/tsHHVJCB9AAQ6v3OBDWQBaTqph.siy5ePNBJog4Czg7t9cO', 'Marvin Ramos', 'teacher', 'active', '2025-05-23 00:51:01'),
(2, 'shane.abina@school.edu', '$2y$10$JzTCD/tsHHVJCB9AAQ6v3OBDWQBaTqph.siy5ePNBJog4Czg7t9cO', 'Shane Abina', 'teacher', 'active', '2025-05-23 00:51:01'),
(3, 'jovemer.agudo@school.edu', '$2y$10$JzTCD/tsHHVJCB9AAQ6v3OBDWQBaTqph.siy5ePNBJog4Czg7t9cO', 'Jovemer Agudo', 'teacher', 'active', '2025-05-23 00:51:01'),
(4, 'jonathan.sabalo@school.edu', '$2y$10$JzTCD/tsHHVJCB9AAQ6v3OBDWQBaTqph.siy5ePNBJog4Czg7t9cO', 'Jonathan Sabalo', 'teacher', 'active', '2025-05-23 00:51:01'),
(5, 'admin@school.edu', '$2y$10$UC2vE74ojZfSQGQ3f5hKsuAttDecrapH9zCE1Arbd6EXkSQh343zO', 'System Administrator', 'admin', 'active', '2025-05-23 00:53:50'),
(6, 'test@example.com', '$2y$10$Ea0jZpsbjNNf3U4zgjUq3eywt6m9XadKpblMKPNs5RwUdMR7zaTtS', 'Test Student', 'student', 'active', '2025-05-23 00:55:29'),
(7, 'testuser@example.com', '$2y$10$Ea0jZpsbjNNf3U4zgjUq3eywt6m9XadKpblMKPNs5RwUdMR7zaTtS', 'Test User', 'student', 'active', '2025-05-23 01:00:58'),
(8, 'student1@example.com', '$2y$10$Ea0jZpsbjNNf3U4zgjUq3eywt6m9XadKpblMKPNs5RwUdMR7zaTtS', 'Student One', 'student', 'active', '2025-05-23 01:02:29'),
(9, 'student2@example.com', '$2y$10$Ea0jZpsbjNNf3U4zgjUq3eywt6m9XadKpblMKPNs5RwUdMR7zaTtS', 'Student Two', 'student', 'active', '2025-05-23 01:02:30'),
(10, 'student3@example.com', '$2y$10$Ea0jZpsbjNNf3U4zgjUq3eywt6m9XadKpblMKPNs5RwUdMR7zaTtS', 'Student Three', 'student', 'active', '2025-05-23 01:02:31'),
(11, 'student4@example.com', '$2y$10$Ea0jZpsbjNNf3U4zgjUq3eywt6m9XadKpblMKPNs5RwUdMR7zaTtS', 'Student Four', 'student', 'active', '2025-05-23 01:02:32'),
(12, 'student5@example.com', '$2y$10$Ea0jZpsbjNNf3U4zgjUq3eywt6m9XadKpblMKPNs5RwUdMR7zaTtS', 'Student Five', 'student', 'active', '2025-05-23 01:02:33'),
(18, 'teststudent1@gmail.com', '$2y$10$Ea0jZpsbjNNf3U4zgjUq3eywt6m9XadKpblMKPNs5RwUdMR7zaTtS', 'Test Student', 'student', 'active', '2025-05-23 01:09:15');

-- Insert Teachers
INSERT INTO teachers (id, user_id, teacher_id, department, specialization, created_at) VALUES 
(1, 1, 'T2024-001', 'Information Technology', 'Web Development', '2025-05-23 00:51:01'),
(2, 2, 'T2024-002', 'Information Technology', 'Database Management', '2025-05-23 00:51:01'),
(3, 3, 'T2024-003', 'Information Technology', 'Network Security', '2025-05-23 00:51:01'),
(4, 4, 'T2024-004', 'Information Technology', 'Software Engineering', '2025-05-23 00:51:01'),
(5, 5, 'ADMIN-001', 'System Administration', 'System Administration', '2025-05-23 01:14:30');

-- Insert Students
INSERT INTO students (id, user_id, student_id, course, year_level, section, created_at) VALUES 
(1, 6, 'STU20240001', 'BSIT', 1, 'A', '2025-05-23 00:59:40'),
(2, 7, 'STU20240002', 'BSIT', 1, 'A', '2025-05-23 01:01:07'),
(3, 8, 'STU20240003', 'BSIT', 1, 'B', '2025-05-23 01:02:40'),
(4, 9, 'STU20240004', 'BSIT', 1, 'B', '2025-05-23 01:02:41'),
(5, 10, 'STU20240005', 'BSIT', 2, 'A', '2025-05-23 01:02:42'),
(6, 11, 'STU20240006', 'BSIT', 2, 'A', '2025-05-23 01:02:43'),
(7, 12, 'STU20240007', 'BSIT', 2, 'B', '2025-05-23 01:02:44'),
(8, 18, 'STU20250018', 'BSIT', 2, 'A', '2025-05-23 01:09:15');

-- Insert Subjects
INSERT INTO subjects (subject_code, subject_name, description, units) VALUES
('COMP101', 'Introduction to Computing', 'Basic concepts of computer systems', 3),
('PROG101', 'Programming 1', 'Introduction to programming concepts', 3),
('MATH101', 'Mathematics in the Modern World', 'Modern applications of mathematics', 3),
('ENG101', 'Technical Writing', 'Technical communication skills', 3),
('COMP201', 'Data Structures', 'Advanced data organization and manipulation', 3),
('PROG201', 'Object-Oriented Programming', 'OOP concepts and implementation', 3),
('MATH201', 'Discrete Mathematics', 'Mathematical structures for computing', 3),
('NET101', 'Networking Fundamentals', 'Basic computer networking concepts', 3),
('DB101', 'Database Management Systems', 'Introduction to database systems', 3),
('SEC101', 'Information Security', 'Basic information security concepts', 3),
('WEB101', 'Web Development', 'Basic web development technologies', 3),
('SYS101', 'Operating Systems', 'Introduction to operating systems', 3),
('PROG102', 'Programming 2', 'Advanced programming concepts', 3),
('COMP102', 'Computer Organization', 'Computer architecture and organization', 3),
('DB102', 'Advanced Database Systems', 'Advanced database concepts and implementation', 3);

-- Insert Academic Terms
INSERT INTO academic_terms (academic_year, term, start_date, end_date, status, created_by) VALUES
('2024-2025', 'First Semester', '2024-08-15', '2024-12-20', 'active', 5),
('2024-2025', 'Second Semester', '2025-01-06', '2025-05-15', 'upcoming', 5);

-- Insert Activities
INSERT INTO activities (subject_id, title, description, activity_type, scheduled_date, due_date, status, created_by) VALUES
(1, 'Quiz 1', 'Basic Computing Concepts', 'quiz', '2024-08-20', '2024-08-20', 'completed', 1),
(1, 'Assignment 1', 'Computer Hardware Research', 'assignment', '2024-08-25', '2024-09-01', 'completed', 1),
(2, 'Programming Quiz', 'Basic Programming Concepts', 'quiz', '2024-08-22', '2024-08-22', 'completed', 2),
(2, 'Coding Project', 'Simple Calculator Program', 'project', '2024-09-01', '2024-09-15', 'in_progress', 2),
(3, 'Math Quiz 1', 'Basic Math Concepts', 'quiz', '2024-08-23', '2024-08-23', 'completed', 3),
(3, 'Math Assignment', 'Problem Set 1', 'assignment', '2024-08-26', '2024-09-02', 'completed', 3),
(4, 'Writing Exercise', 'Technical Documentation', 'assignment', '2024-08-27', '2024-09-03', 'in_progress', 4),
(4, 'Midterm Exam', 'Comprehensive Assessment', 'exam', '2024-09-15', '2024-09-15', 'scheduled', 4),
(5, 'Data Structures Quiz', 'Arrays and Linked Lists', 'quiz', '2024-08-28', '2024-08-28', 'scheduled', 1),
(5, 'Programming Project', 'Implementation of Data Structures', 'project', '2024-09-10', '2024-09-24', 'scheduled', 1);

-- Insert Student Subjects
INSERT INTO student_subjects (student_id, subject_id, academic_year, term, status) VALUES
(1, 1, '2024-2025', 'First Semester', 'enrolled'),
(1, 2, '2024-2025', 'First Semester', 'enrolled'),
(1, 3, '2024-2025', 'First Semester', 'enrolled'),
(2, 1, '2024-2025', 'First Semester', 'enrolled'),
(2, 2, '2024-2025', 'First Semester', 'enrolled'),
(3, 1, '2024-2025', 'First Semester', 'enrolled'),
(3, 3, '2024-2025', 'First Semester', 'enrolled'),
(4, 4, '2024-2025', 'First Semester', 'enrolled'),
(4, 5, '2024-2025', 'First Semester', 'enrolled'),
(5, 6, '2024-2025', 'First Semester', 'enrolled'),
(5, 7, '2024-2025', 'First Semester', 'enrolled'),
(6, 6, '2024-2025', 'First Semester', 'enrolled'),
(6, 8, '2024-2025', 'First Semester', 'enrolled'),
(7, 9, '2024-2025', 'First Semester', 'enrolled'),
(7, 10, '2024-2025', 'First Semester', 'enrolled'),
(8, 11, '2024-2025', 'First Semester', 'enrolled'),
(8, 12, '2024-2025', 'First Semester', 'enrolled');

-- Insert Curriculum Data
INSERT INTO curriculum (degree_program, year_level, subject_id, semester) VALUES
('BSIT', '1st Year', 1, 'First Semester'),
('BSIT', '1st Year', 2, 'First Semester'),
('BSIT', '1st Year', 3, 'First Semester'),
('BSIT', '1st Year', 4, 'Second Semester'),
('BSIT', '1st Year', 5, 'Second Semester'),
('BSIT', '2nd Year', 6, 'First Semester'),
('BSIT', '2nd Year', 7, 'First Semester'),
('BSIT', '2nd Year', 8, 'Second Semester'),
('BSIT', '2nd Year', 9, 'Second Semester');

-- Insert Grades
INSERT INTO grades (student_id, subject_id, category, grade_type, score, academic_year, term, remarks, graded_by) VALUES
(1, 1, 'written', 'Quiz 1', 85.00, '2024-2025', 'First Semester', 'Good performance', 1),
(1, 1, 'performance', 'Assignment 1', 90.00, '2024-2025', 'First Semester', 'Excellent work', 1),
(2, 1, 'written', 'Quiz 1', 78.00, '2024-2025', 'First Semester', 'Needs improvement', 1),
(2, 2, 'performance', 'Project 1', 88.00, '2024-2025', 'First Semester', 'Well done', 2),
(3, 1, 'written', 'Quiz 1', 92.00, '2024-2025', 'First Semester', 'Excellent performance', 1),
(3, 3, 'performance', 'Assignment 1', 85.00, '2024-2025', 'First Semester', 'Good work', 3),
(4, 4, 'written', 'Quiz 1', 88.00, '2024-2025', 'First Semester', 'Very good', 4),
(4, 5, 'performance', 'Project 1', 91.00, '2024-2025', 'First Semester', 'Excellent implementation', 1),
(5, 6, 'written', 'Midterm', 87.00, '2024-2025', 'First Semester', 'Good understanding', 2),
(5, 7, 'performance', 'Assignment 1', 89.00, '2024-2025', 'First Semester', 'Well executed', 3);

-- Insert Attendance Records
INSERT INTO attendance (student_id, subject_id, date, status, remarks, recorded_by) VALUES
(1, 1, '2024-08-15', 'present', NULL, 1),
(1, 1, '2024-08-16', 'present', NULL, 1),
(2, 1, '2024-08-15', 'present', NULL, 1),
(2, 1, '2024-08-16', 'late', 'Arrived 10 minutes late', 1),
(3, 1, '2024-08-15', 'present', NULL, 1),
(3, 3, '2024-08-15', 'present', NULL, 3),
(4, 4, '2024-08-15', 'absent', 'Sick leave', 4),
(4, 5, '2024-08-15', 'present', NULL, 1),
(5, 6, '2024-08-15', 'present', NULL, 2),
(5, 7, '2024-08-15', 'present', NULL, 3),
(6, 6, '2024-08-15', 'late', 'Traffic delay', 2),
(6, 8, '2024-08-15', 'present', NULL, 4),
(7, 9, '2024-08-15', 'present', NULL, 2),
(7, 10, '2024-08-15', 'present', NULL, 3),
(8, 11, '2024-08-15', 'present', NULL, 1),
(8, 12, '2024-08-15', 'present', NULL, 4);

-- Insert System Settings
INSERT INTO system_settings (setting_key, setting_value, description, is_public) VALUES
('school_name', 'Sample School', 'Name of the educational institution', 1),
('school_address', '123 Education Street, City', 'Address of the school', 1),
('academic_year', '2024-2025', 'Current academic year', 1),
('grading_system', 'percentage', 'Type of grading system used', 1),
('passing_grade', '75', 'Minimum passing grade', 1),
('maintenance_mode', 'false', 'System maintenance mode status', 0),
('max_file_size', '5242880', 'Maximum file upload size in bytes', 0),
('attendance_lock_days', '7', 'Days after which attendance cannot be modified', 0),
('grade_submission_deadline', '7', 'Days after term end for grade submission', 0),
('max_login_attempts', '5', 'Maximum failed login attempts before lockout', 0);

-- Insert Activity Logs
INSERT INTO activity_logs (user_id, action, entity_type, entity_id, old_value, new_value, ip_address) VALUES
(5, 'login', 'user', 5, NULL, NULL, '127.0.0.1'),
(1, 'grade_update', 'grades', 1, '80.00', '85.00', '127.0.0.1'),
(2, 'attendance_record', 'attendance', 1, NULL, 'present', '127.0.0.1'),
(3, 'grade_entry', 'grades', 3, NULL, '92.00', '127.0.0.1'),
(4, 'attendance_update', 'attendance', 4, 'present', 'absent', '127.0.0.1'),
(1, 'activity_create', 'activities', 1, NULL, 'Quiz 1', '127.0.0.1'),
(2, 'grade_submission', 'grades', 2, NULL, '88.00', '127.0.0.1'),
(5, 'system_update', 'settings', 1, 'maintenance_mode:true', 'maintenance_mode:false', '127.0.0.1');

-- Insert Notifications
INSERT INTO notifications (user_id, title, message, type, is_read) VALUES
(6, 'Grade Posted', 'Your grade for Quiz 1 in COMP101 has been posted', 'info', 0),
(7, 'Assignment Due', 'Reminder: Assignment 1 is due tomorrow', 'warning', 0),
(8, 'System Maintenance', 'System will undergo maintenance this weekend', 'info', 1),
(9, 'Grade Update', 'Your grade for Project 1 has been updated', 'success', 0),
(10, 'Attendance Warning', 'You have been marked late for today\'s class', 'warning', 0),
(11, 'Assignment Feedback', 'New feedback posted for your recent assignment', 'info', 0),
(12, 'Grade Posted', 'Midterm grades have been posted', 'info', 0),
(18, 'Course Registration', 'Please complete your course registration', 'warning', 0); 