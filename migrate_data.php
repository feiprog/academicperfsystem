<?php
// Connect to both databases
$gcst_conn = new mysqli("localhost", "root", "", "gcst");
$academic_conn = new mysqli("localhost", "root", "", "academic_performance");

if ($gcst_conn->connect_error || $academic_conn->connect_error) {
    die("Connection failed: " . $gcst_conn->connect_error . " or " . $academic_conn->connect_error);
}

try {
    // Start transaction in both databases
    $gcst_conn->begin_transaction();
    $academic_conn->begin_transaction();

    // First, clear existing data in academic_performance
    $academic_conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $tables = ['student_subjects', 'students', 'teachers', 'subjects', 'users'];
    foreach ($tables as $table) {
        $academic_conn->query("TRUNCATE TABLE $table");
    }
    $academic_conn->query("SET FOREIGN_KEY_CHECKS = 1");

    // 1. Migrate users and teachers (since we have teacher data)
    $result = $gcst_conn->query("SELECT t.*, u.* FROM teachers t JOIN users u ON t.user_id = u.id");
    while ($row = $result->fetch_assoc()) {
        // Insert into academic_performance.users
        $stmt = $academic_conn->prepare("INSERT INTO users (username, password, role, full_name, email, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", 
            $row['username'],
            $row['password'],
            $row['role'],
            $row['full_name'],
            $row['email'],
            $row['created_at']
        );
        $stmt->execute();
        $new_user_id = $academic_conn->insert_id;

        // Insert into academic_performance.teachers
        $stmt = $academic_conn->prepare("INSERT INTO teachers (user_id, teacher_id, department, created_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", 
            $new_user_id,
            $row['teacher_id'],
            $row['department'],
            $row['created_at']
        );
        $stmt->execute();
    }

    // 2. Migrate subjects
    $result = $gcst_conn->query("SELECT * FROM subjects");
    while ($row = $result->fetch_assoc()) {
        // Get the new teacher_id if there is one
        $new_teacher_id = null;
        if ($row['teacher_id']) {
            $teacher_result = $gcst_conn->query("SELECT teacher_id FROM teachers WHERE id = " . $row['teacher_id']);
            $teacher_data = $teacher_result->fetch_assoc();
            if ($teacher_data) {
                $new_teacher_result = $academic_conn->query("SELECT id FROM teachers WHERE teacher_id = '" . $teacher_data['teacher_id'] . "'");
                $new_teacher_data = $new_teacher_result->fetch_assoc();
                if ($new_teacher_data) {
                    $new_teacher_id = $new_teacher_data['id'];
                }
            }
        }

        // Insert into academic_performance.subjects
        $stmt = $academic_conn->prepare("INSERT INTO subjects (subject_code, subject_name, description, teacher_id, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", 
            $row['subject_code'],
            $row['subject_name'],
            $row['description'],
            $new_teacher_id,
            $row['created_at']
        );
        $stmt->execute();
    }

    // 3. Migrate students (with default values for required fields)
    $result = $gcst_conn->query("SELECT s.*, u.* FROM students s JOIN users u ON s.user_id = u.id");
    while ($row = $result->fetch_assoc()) {
        // Insert into academic_performance.users
        $stmt = $academic_conn->prepare("INSERT INTO users (username, password, role, full_name, email, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", 
            $row['username'],
            $row['password'],
            'student', // Ensure role is student
            $row['full_name'],
            $row['email'],
            $row['created_at']
        );
        $stmt->execute();
        $new_user_id = $academic_conn->insert_id;

        // Split full_name into first_name and last_name
        $name_parts = explode(' ', $row['full_name'], 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';

        // Insert into academic_performance.students with default values for required fields
        $stmt = $academic_conn->prepare("INSERT INTO students (user_id, student_id, first_name, last_name, year_level, degree_program, semester, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $student_id = 'STU' . date('Y') . str_pad($new_user_id, 4, '0', STR_PAD_LEFT);
        $year_level = '1st Year'; // Default for new students
        $degree_program = 'BSIT'; // Default program
        $semester = '1st Sem'; // Default semester
        $stmt->bind_param("isssssss", 
            $new_user_id,
            $student_id,
            $first_name,
            $last_name,
            $year_level,
            $degree_program,
            $semester,
            $row['created_at']
        );
        $stmt->execute();
    }

    // 4. Migrate student_subjects (enrollments)
    $result = $gcst_conn->query("SELECT * FROM student_subjects");
    while ($row = $result->fetch_assoc()) {
        // Get the new student_id and subject_id
        $student_result = $gcst_conn->query("SELECT student_id FROM students WHERE id = " . $row['student_id']);
        $student_data = $student_result->fetch_assoc();
        if ($student_data) {
            $new_student_result = $academic_conn->query("SELECT id FROM students WHERE student_id = '" . $student_data['student_id'] . "'");
            $new_student_data = $new_student_result->fetch_assoc();
            if ($new_student_data) {
                $subject_result = $gcst_conn->query("SELECT subject_code FROM subjects WHERE id = " . $row['subject_id']);
                $subject_data = $subject_result->fetch_assoc();
                if ($subject_data) {
                    $new_subject_result = $academic_conn->query("SELECT id FROM subjects WHERE subject_code = '" . $subject_data['subject_code'] . "'");
                    $new_subject_data = $new_subject_result->fetch_assoc();
                    if ($new_subject_data) {
                        // Insert into academic_performance.student_subjects
                        $stmt = $academic_conn->prepare("INSERT INTO student_subjects (student_id, subject_id, enrollment_date, status, created_at) VALUES (?, ?, ?, ?, ?)");
                        $enrollment_date = date('Y-m-d'); // Default to today if not set
                        $status = 'active'; // Default status
                        $stmt->bind_param("iisss", 
                            $new_student_data['id'],
                            $new_subject_data['id'],
                            $enrollment_date,
                            $status,
                            $row['created_at'] ?? date('Y-m-d H:i:s')
                        );
                        $stmt->execute();
                    }
                }
            }
        }
    }

    // Commit transactions
    $gcst_conn->commit();
    $academic_conn->commit();
    
    echo "Data migration completed successfully!\n";
} catch (Exception $e) {
    // Rollback transactions on error
    $gcst_conn->rollback();
    $academic_conn->rollback();
    echo "Error during migration: " . $e->getMessage() . "\n";
}

$gcst_conn->close();
$academic_conn->close();
?> 