<?php
require_once 'db.php';

// Create users table
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Create students table
$sql_students = "CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    year_level VARCHAR(20) NOT NULL,
    degree_program VARCHAR(50) NOT NULL,
    semester ENUM('1st Sem', '2nd Sem', 'Summer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

// Create teachers table
$sql_teachers = "CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    teacher_id VARCHAR(20) UNIQUE NOT NULL,
    department VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

// Create subjects table with teacher assignments
$sql_subjects = "CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) UNIQUE NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    description TEXT,
    teacher_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
)";

// Create student_subjects table for enrollment
$sql_student_subjects = "CREATE TABLE IF NOT EXISTS student_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    enrollment_date DATE NOT NULL,
    status ENUM('active', 'dropped', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, subject_id)
)";

// Create grades table
$sql_grades = "CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    grade_type ENUM('quiz', 'assignment', 'exam', 'project', 'final') NOT NULL,
    remarks TEXT,
    graded_by INT NOT NULL,
    graded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES teachers(id) ON DELETE CASCADE
)";

// Create attendance table
$sql_attendance = "CREATE TABLE IF NOT EXISTS attendance (
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
)";

// Create activities table
$sql_activities = "CREATE TABLE IF NOT EXISTS activities (
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
    FOREIGN KEY (created_by) REFERENCES teachers(id) ON DELETE CASCADE
)";

// Create reports table
$sql_reports = "CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    report_type ENUM('progress', 'midterm', 'final', 'special') NOT NULL,
    content TEXT NOT NULL,
    status ENUM('draft', 'pending', 'approved', 'rejected') DEFAULT 'draft',
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    comments TEXT,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES teachers(id) ON DELETE SET NULL
)";

// Create report requests table
$sql_report_requests = "CREATE TABLE IF NOT EXISTS report_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    request_type ENUM('progress', 'midterm', 'final', 'special') NOT NULL,
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
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE SET NULL
)";

// Create curriculum table
$sql_curriculum = "CREATE TABLE IF NOT EXISTS curriculum (
    id INT AUTO_INCREMENT PRIMARY KEY,
    degree_program VARCHAR(50) NOT NULL,
    year_level VARCHAR(20) NOT NULL,
    subject_id INT NOT NULL,
    semester ENUM('1st Sem', '2nd Sem', 'Summer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_curriculum (degree_program, year_level, subject_id, semester)
)";

// Insert default subjects with teacher assignments
$sql_insert_subjects = "INSERT IGNORE INTO subjects (subject_code, subject_name, description) VALUES 
    ('IM101', 'Information Management', 'Fundamentals of Information Management'),
    ('WSD101', 'Web System and Development', 'Web Development and System Design'),
    ('ADB101', 'Advanced Database Management', 'Advanced Database Concepts and Management'),
    ('NET201', 'Networking II', 'Advanced Networking Concepts and Implementation'),
    ('PROG101', 'Programming Fundamentals', 'Introduction to Programming Concepts and Logic'),
    ('OOP201', 'Object-Oriented Programming', 'Advanced Programming with OOP Principles'),
    ('DSA201', 'Data Structures and Algorithms', 'Core Data Structures and Algorithm Analysis'),
    ('OS201', 'Operating Systems', 'Operating System Concepts and Management'),
    ('SEC201', 'Information Security', 'Cybersecurity and Information Protection'),
    ('PM201', 'Project Management', 'IT Project Planning and Management'),
    ('UIUX101', 'User Interface Design', 'Principles of UI/UX Design and Implementation'),
    ('MOB201', 'Mobile Development', 'Mobile Application Development and Design')
";

// Insert default curriculum for all programs
$sql_insert_curriculum = "INSERT IGNORE INTO curriculum (degree_program, year_level, subject_id, semester) 
-- BSIT Program
SELECT 'BSIT', '1st Year', id, '1st Sem' FROM subjects WHERE subject_code IN ('PROG101', 'IM101', 'WSD101', 'UIUX101')
UNION ALL
SELECT 'BSIT', '1st Year', id, '2nd Sem' FROM subjects WHERE subject_code IN ('OOP201', 'ADB101', 'NET201', 'MOB201')
UNION ALL
SELECT 'BSIT', '2nd Year', id, '1st Sem' FROM subjects WHERE subject_code IN ('DSA201', 'OS201', 'SEC201', 'PM201')
UNION ALL
SELECT 'BSIT', '2nd Year', id, '2nd Sem' FROM subjects WHERE subject_code IN ('NET201', 'ADB101', 'WSD101', 'UIUX101')
UNION ALL
SELECT 'BSIT', '3rd Year', id, '1st Sem' FROM subjects WHERE subject_code IN ('SEC201', 'PM201', 'MOB201', 'DSA201')
UNION ALL
SELECT 'BSIT', '3rd Year', id, '2nd Sem' FROM subjects WHERE subject_code IN ('OS201', 'IM101', 'PROG101', 'OOP201')
UNION ALL
SELECT 'BSIT', '4th Year', id, '1st Sem' FROM subjects WHERE subject_code IN ('PM201', 'SEC201', 'NET201', 'ADB101')
UNION ALL
SELECT 'BSIT', '4th Year', id, '2nd Sem' FROM subjects WHERE subject_code IN ('WSD101', 'UIUX101', 'MOB201', 'DSA201')

-- BSCS Program (similar structure but with some variations)
UNION ALL
SELECT 'BSCS', '1st Year', id, '1st Sem' FROM subjects WHERE subject_code IN ('PROG101', 'IM101', 'WSD101', 'UIUX101')
UNION ALL
SELECT 'BSCS', '1st Year', id, '2nd Sem' FROM subjects WHERE subject_code IN ('OOP201', 'ADB101', 'NET201', 'MOB201')
UNION ALL
SELECT 'BSCS', '2nd Year', id, '1st Sem' FROM subjects WHERE subject_code IN ('DSA201', 'OS201', 'SEC201', 'PM201')
UNION ALL
SELECT 'BSCS', '2nd Year', id, '2nd Sem' FROM subjects WHERE subject_code IN ('NET201', 'ADB101', 'WSD101', 'UIUX101')
UNION ALL
SELECT 'BSCS', '3rd Year', id, '1st Sem' FROM subjects WHERE subject_code IN ('SEC201', 'PM201', 'MOB201', 'DSA201')
UNION ALL
SELECT 'BSCS', '3rd Year', id, '2nd Sem' FROM subjects WHERE subject_code IN ('OS201', 'IM101', 'PROG101', 'OOP201')
UNION ALL
SELECT 'BSCS', '4th Year', id, '1st Sem' FROM subjects WHERE subject_code IN ('PM201', 'SEC201', 'NET201', 'ADB101')
UNION ALL
SELECT 'BSCS', '4th Year', id, '2nd Sem' FROM subjects WHERE subject_code IN ('WSD101', 'UIUX101', 'MOB201', 'DSA201')

-- BSCE Program (similar structure but with some variations)
UNION ALL
SELECT 'BSCE', '1st Year', id, '1st Sem' FROM subjects WHERE subject_code IN ('PROG101', 'IM101', 'WSD101', 'UIUX101')
UNION ALL
SELECT 'BSCE', '1st Year', id, '2nd Sem' FROM subjects WHERE subject_code IN ('OOP201', 'ADB101', 'NET201', 'MOB201')
UNION ALL
SELECT 'BSCE', '2nd Year', id, '1st Sem' FROM subjects WHERE subject_code IN ('DSA201', 'OS201', 'SEC201', 'PM201')
UNION ALL
SELECT 'BSCE', '2nd Year', id, '2nd Sem' FROM subjects WHERE subject_code IN ('NET201', 'ADB101', 'WSD101', 'UIUX101')
UNION ALL
SELECT 'BSCE', '3rd Year', id, '1st Sem' FROM subjects WHERE subject_code IN ('SEC201', 'PM201', 'MOB201', 'DSA201')
UNION ALL
SELECT 'BSCE', '3rd Year', id, '2nd Sem' FROM subjects WHERE subject_code IN ('OS201', 'IM101', 'PROG101', 'OOP201')
UNION ALL
SELECT 'BSCE', '4th Year', id, '1st Sem' FROM subjects WHERE subject_code IN ('PM201', 'SEC201', 'NET201', 'ADB101')
UNION ALL
SELECT 'BSCE', '4th Year', id, '2nd Sem' FROM subjects WHERE subject_code IN ('WSD101', 'UIUX101', 'MOB201', 'DSA201');";

// Insert default teacher accounts
$sql_insert_teachers = "INSERT IGNORE INTO users (username, password, role, full_name, email) VALUES 
    ('mramos', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Marvin Ramos', 'marvin.ramos@school.edu'),
    ('sabina', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Shane Abina', 'shane.abina@school.edu'),
    ('jagudo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Jovemer Agudo', 'jovemer.agudo@school.edu'),
    ('jsabalo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Jonathan Sabalo', 'jonathan.sabalo@school.edu')";

// Insert teacher records
$sql_insert_teacher_records = "INSERT IGNORE INTO teachers (user_id, teacher_id, department) 
SELECT id, 
    CASE 
        WHEN username = 'mramos' THEN 'T2024-001'
        WHEN username = 'sabina' THEN 'T2024-002'
        WHEN username = 'jagudo' THEN 'T2024-003'
        WHEN username = 'jsabalo' THEN 'T2024-004'
    END,
    'Information Technology'
FROM users 
WHERE role = 'teacher'";

// Execute the SQL statements
try {
    // Create tables
    $conn->query($sql_users);
    $conn->query($sql_students);
    $conn->query($sql_teachers);
    $conn->query($sql_subjects);
    $conn->query($sql_student_subjects);
    $conn->query($sql_grades);
    $conn->query($sql_attendance);
    $conn->query($sql_activities);
    $conn->query($sql_reports);
    $conn->query($sql_report_requests);
    $conn->query($sql_curriculum);
    
    // Insert default subjects
    $conn->query($sql_insert_subjects);
    
    // Insert curriculum data
    $conn->query($sql_insert_curriculum);
    
    // Insert teacher accounts and records
    $conn->query($sql_insert_teachers);
    $conn->query($sql_insert_teacher_records);
    
    // Update teacher assignments
    $sql_update_teachers = "UPDATE teachers t 
        JOIN users u ON t.user_id = u.id 
        JOIN subjects s ON 
            CASE 
                WHEN u.full_name = 'Marvin Ramos' THEN s.subject_code IN ('IM101', 'PM201')
                WHEN u.full_name = 'Shane Abina' THEN s.subject_code IN ('WSD101', 'UIUX101')
                WHEN u.full_name = 'Jovemer Agudo' THEN s.subject_code IN ('ADB101', 'DSA201')
                WHEN u.full_name = 'Jonathan Sabalo' THEN s.subject_code IN ('NET201', 'SEC201')
            END
        SET s.teacher_id = t.id";
    
    $conn->query($sql_update_teachers);
    
    echo "Database tables created and updated successfully!";
} catch (Exception $e) {
    echo "Error creating/updating tables: " . $e->getMessage();
}

$conn->close();
?> 