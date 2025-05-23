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
(4, 18, 'STU20250018', 'BSIT', 2, 'A', '2025-05-23 01:09:15');

-- Insert default subjects
INSERT INTO subjects (subject_code, subject_name, description, units) VALUES
('COMP101', 'Introduction to Computing', 'Basic concepts of computer systems', 3),
('PROG101', 'Programming 1', 'Introduction to programming concepts', 3),
('MATH101', 'Mathematics in the Modern World', 'Modern applications of mathematics', 3),
('ENG101', 'Technical Writing', 'Technical communication skills', 3); 