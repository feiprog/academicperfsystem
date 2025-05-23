<?php
require_once 'db.php';

// Drop existing tables if they exist
$sql_drop_reports = "DROP TABLE IF EXISTS reports";
$sql_drop_report_requests = "DROP TABLE IF EXISTS report_requests";
$sql_drop_student_subjects = "DROP TABLE IF EXISTS student_subjects";
$sql_drop_curriculum = "DROP TABLE IF EXISTS curriculum";
$sql_drop_subjects = "DROP TABLE IF EXISTS subjects";

// Create users table
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher', 'admin') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_username (username)
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
    semester ENUM('First Semester', 'Second Semester') NOT NULL,
    academic_year VARCHAR(9) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_year_program (year_level, degree_program),
    INDEX idx_academic_term (semester, academic_year)
)";

// Create teachers table
$sql_teachers = "CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    teacher_id VARCHAR(20) UNIQUE NOT NULL,
    department VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_teacher_id (teacher_id)
)";

// Create subjects table with teacher assignments
$sql_subjects = "CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) UNIQUE NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    description TEXT,
    teacher_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL,
    INDEX idx_subject_code (subject_code),
    INDEX idx_teacher (teacher_id)
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
    UNIQUE KEY unique_enrollment (student_id, subject_id),
    INDEX idx_status (status),
    INDEX idx_enrollment_date (enrollment_date)
)";

// Drop and recreate grades table
$sql_drop_grades = "DROP TABLE IF EXISTS grades";

// Create grades table
$sql_grades = "CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    category ENUM('written', 'performance', 'exams') NOT NULL,
    grade_type ENUM('assignment', 'activity', 'quiz', 'attendance', 'prelim', 'midterm', 'semi_final', 'final') NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    remarks TEXT,
    graded_by INT NOT NULL,
    graded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES teachers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_grade (student_id, subject_id, grade_type),
    INDEX idx_category (category),
    INDEX idx_grade_type (grade_type),
    INDEX idx_graded_at (graded_at)
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
    UNIQUE KEY unique_attendance (student_id, subject_id, date),
    INDEX idx_date (date),
    INDEX idx_status (status)
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
    FOREIGN KEY (created_by) REFERENCES teachers(id) ON DELETE CASCADE,
    INDEX idx_scheduled_date (scheduled_date),
    INDEX idx_status (status)
)";

// Create reports table
$sql_reports = "CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    report_type ENUM('term', 'progress', 'comprehensive', 'special') NOT NULL,
    term_period ENUM('preliminary', 'midterm', 'semi_final', 'final') NULL,
    content TEXT NOT NULL,
    status ENUM('draft', 'pending', 'approved', 'rejected') DEFAULT 'draft',
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    comments TEXT,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES teachers(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_submission_date (submission_date)
)";

// Create report requests table
$sql_report_requests = "CREATE TABLE IF NOT EXISTS report_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    request_type ENUM('term', 'progress', 'comprehensive', 'special') NOT NULL,
    term_period ENUM('preliminary', 'midterm', 'semi_final', 'final') NULL,
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
)";

// Create curriculum table
$sql_curriculum = "CREATE TABLE IF NOT EXISTS curriculum (
    id INT AUTO_INCREMENT PRIMARY KEY,
    degree_program VARCHAR(50) NOT NULL,
    year_level VARCHAR(20) NOT NULL,
    subject_id INT NOT NULL,
    semester ENUM('First Semester', 'Second Semester') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_curriculum (degree_program, year_level, subject_id, semester),
    INDEX idx_program_year (degree_program, year_level)
)";

// Create remember_tokens table
$sql_remember_tokens = "CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_token (token),
    INDEX idx_expires (expires_at)
)";

// Create password_resets table
$sql_password_resets = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_token (token),
    INDEX idx_expires (expires_at)
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
SELECT 'BSIT', '1st Year', id, 'First Semester' FROM subjects WHERE subject_code IN ('PROG101', 'IM101', 'WSD101', 'UIUX101')
UNION ALL
SELECT 'BSIT', '1st Year', id, 'Second Semester' FROM subjects WHERE subject_code IN ('OOP201', 'ADB101', 'NET201', 'MOB201')
UNION ALL
SELECT 'BSIT', '2nd Year', id, 'First Semester' FROM subjects WHERE subject_code IN ('DSA201', 'OS201', 'SEC201', 'PM201')
UNION ALL
SELECT 'BSIT', '2nd Year', id, 'Second Semester' FROM subjects WHERE subject_code IN ('NET201', 'ADB101', 'WSD101', 'UIUX101')
UNION ALL
SELECT 'BSIT', '3rd Year', id, 'First Semester' FROM subjects WHERE subject_code IN ('SEC201', 'PM201', 'MOB201', 'DSA201')
UNION ALL
SELECT 'BSIT', '3rd Year', id, 'Second Semester' FROM subjects WHERE subject_code IN ('OS201', 'IM101', 'PROG101', 'OOP201')
UNION ALL
SELECT 'BSIT', '4th Year', id, 'First Semester' FROM subjects WHERE subject_code IN ('PM201', 'SEC201', 'NET201', 'ADB101')
UNION ALL
SELECT 'BSIT', '4th Year', id, 'Second Semester' FROM subjects WHERE subject_code IN ('WSD101', 'UIUX101', 'MOB201', 'DSA201')

-- BSCS Program
UNION ALL
SELECT 'BSCS', '1st Year', id, 'First Semester' FROM subjects WHERE subject_code IN ('PROG101', 'IM101', 'WSD101', 'UIUX101')
UNION ALL
SELECT 'BSCS', '1st Year', id, 'Second Semester' FROM subjects WHERE subject_code IN ('OOP201', 'ADB101', 'NET201', 'MOB201')
UNION ALL
SELECT 'BSCS', '2nd Year', id, 'First Semester' FROM subjects WHERE subject_code IN ('DSA201', 'OS201', 'SEC201', 'PM201')
UNION ALL
SELECT 'BSCS', '2nd Year', id, 'Second Semester' FROM subjects WHERE subject_code IN ('NET201', 'ADB101', 'WSD101', 'UIUX101')
UNION ALL
SELECT 'BSCS', '3rd Year', id, 'First Semester' FROM subjects WHERE subject_code IN ('SEC201', 'PM201', 'MOB201', 'DSA201')
UNION ALL
SELECT 'BSCS', '3rd Year', id, 'Second Semester' FROM subjects WHERE subject_code IN ('OS201', 'IM101', 'PROG101', 'OOP201')
UNION ALL
SELECT 'BSCS', '4th Year', id, 'First Semester' FROM subjects WHERE subject_code IN ('PM201', 'SEC201', 'NET201', 'ADB101')
UNION ALL
SELECT 'BSCS', '4th Year', id, 'Second Semester' FROM subjects WHERE subject_code IN ('WSD101', 'UIUX101', 'MOB201', 'DSA201')

-- BSCE Program
UNION ALL
SELECT 'BSCE', '1st Year', id, 'First Semester' FROM subjects WHERE subject_code IN ('PROG101', 'IM101', 'WSD101', 'UIUX101')
UNION ALL
SELECT 'BSCE', '1st Year', id, 'Second Semester' FROM subjects WHERE subject_code IN ('OOP201', 'ADB101', 'NET201', 'MOB201')
UNION ALL
SELECT 'BSCE', '2nd Year', id, 'First Semester' FROM subjects WHERE subject_code IN ('DSA201', 'OS201', 'SEC201', 'PM201')
UNION ALL
SELECT 'BSCE', '2nd Year', id, 'Second Semester' FROM subjects WHERE subject_code IN ('NET201', 'ADB101', 'WSD101', 'UIUX101')
UNION ALL
SELECT 'BSCE', '3rd Year', id, 'First Semester' FROM subjects WHERE subject_code IN ('SEC201', 'PM201', 'MOB201', 'DSA201')
UNION ALL
SELECT 'BSCE', '3rd Year', id, 'Second Semester' FROM subjects WHERE subject_code IN ('OS201', 'IM101', 'PROG101', 'OOP201')
UNION ALL
SELECT 'BSCE', '4th Year', id, 'First Semester' FROM subjects WHERE subject_code IN ('PM201', 'SEC201', 'NET201', 'ADB101')
UNION ALL
SELECT 'BSCE', '4th Year', id, 'Second Semester' FROM subjects WHERE subject_code IN ('WSD101', 'UIUX101', 'MOB201', 'DSA201')";

// Insert default teacher accounts
$default_password = password_hash('password123', PASSWORD_DEFAULT);
$sql_insert_teachers = "INSERT IGNORE INTO users (username, password, role, full_name, email) VALUES 
    ('mramos', '$default_password', 'teacher', 'Marvin Ramos', 'marvin.ramos@school.edu'),
    ('sabina', '$default_password', 'teacher', 'Shane Abina', 'shane.abina@school.edu'),
    ('jagudo', '$default_password', 'teacher', 'Jovemer Agudo', 'jovemer.agudo@school.edu'),
    ('jsabalo', '$default_password', 'teacher', 'Jonathan Sabalo', 'jonathan.sabalo@school.edu')";

// Insert admin account
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql_insert_admin = "INSERT IGNORE INTO users (username, password, role, full_name, email) VALUES 
    ('admin', '$admin_password', 'admin', 'System Administrator', 'admin@school.edu')";

// Insert admin record in teachers table
$sql_insert_admin_record = "INSERT IGNORE INTO teachers (user_id, teacher_id, department) 
SELECT id, 'ADMIN-001', 'System Administration' 
FROM users 
WHERE username = 'admin'";

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

// Add term_period column to existing tables if it doesn't exist
$sql_alter_reports = "ALTER TABLE reports 
    ADD COLUMN IF NOT EXISTS term_period ENUM('preliminary', 'midterm', 'semi_final', 'final') NULL AFTER report_type,
    MODIFY COLUMN report_type ENUM('term', 'progress', 'comprehensive', 'special') NOT NULL";

$sql_alter_report_requests = "ALTER TABLE report_requests 
    ADD COLUMN IF NOT EXISTS term_period ENUM('preliminary', 'midterm', 'semi_final', 'final') NULL AFTER request_type,
    MODIFY COLUMN request_type ENUM('term', 'progress', 'comprehensive', 'special') NOT NULL";

// Insert sample grades for testing
$sql_insert_sample_grades = "INSERT INTO grades (student_id, subject_id, category, grade_type, score, graded_by)
SELECT 
    st.id as student_id,
    s.id as subject_id,
    CASE 
        WHEN g.grade_type IN ('assignment', 'activity', 'quiz') THEN 'written'
        WHEN g.grade_type = 'attendance' THEN 'performance'
        ELSE 'exams'
    END as category,
    g.grade_type,
    FLOOR(75 + RAND() * 25) as score,
    t.id as graded_by
FROM student_subjects ss
CROSS JOIN (
    SELECT 'assignment' as grade_type UNION ALL
    SELECT 'activity' UNION ALL
    SELECT 'quiz' UNION ALL
    SELECT 'attendance' UNION ALL
    SELECT 'prelim' UNION ALL
    SELECT 'midterm' UNION ALL
    SELECT 'semi_final' UNION ALL
    SELECT 'final'
) g
JOIN students st ON ss.student_id = st.id
JOIN subjects s ON ss.subject_id = s.id
JOIN teachers t ON s.teacher_id = t.id
WHERE ss.status = 'active'
ON DUPLICATE KEY UPDATE score = VALUES(score)";

// Execute the SQL statements
try {
    // Disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Drop tables in reverse dependency order
    $conn->query($sql_drop_reports);
    $conn->query($sql_drop_report_requests);
    $conn->query($sql_drop_student_subjects);
    $conn->query($sql_drop_curriculum);
    $conn->query($sql_drop_subjects);
    $conn->query($sql_drop_grades);
    
    // Create tables in dependency order
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
    $conn->query($sql_remember_tokens);
    $conn->query($sql_password_resets);
    
    // Insert default data
    $conn->query($sql_insert_teachers);
    $conn->query($sql_insert_admin);
    $conn->query($sql_insert_admin_record);
    $conn->query($sql_insert_teacher_records);
    $conn->query($sql_insert_subjects);
    $conn->query($sql_insert_curriculum);
    
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
    
    // Execute the ALTER TABLE statements
    if ($conn->query($sql_alter_reports) === FALSE) {
        die("Error altering reports table: " . $conn->error);
    }

    if ($conn->query($sql_alter_report_requests) === FALSE) {
        die("Error altering report_requests table: " . $conn->error);
    }
    
    // Insert sample grades
    $conn->query($sql_insert_sample_grades);
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "Database tables created and updated successfully!";
} catch (Exception $e) {
    // Re-enable foreign key checks even if there's an error
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    echo "Error creating/updating tables: " . $e->getMessage();
}

$conn->close();
?> 