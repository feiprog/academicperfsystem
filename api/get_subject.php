<?php
require_once '../auth_check.php';
require_once '../db.php';

// Ensure user is admin
requireAdmin();

// Get and validate input
$subject_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$subject_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Subject ID is required']);
    exit;
}

try {
    // Get subject details
    $stmt = $conn->prepare("
        SELECT 
            s.id,
            s.subject_code,
            s.subject_name,
            s.description,
            s.teacher_id,
            s.status,
            u.full_name as teacher_name,
            (SELECT COUNT(*) FROM student_subjects ss WHERE ss.subject_id = s.id) as student_count
        FROM subjects s
        LEFT JOIN teachers t ON s.teacher_id = t.id
        LEFT JOIN users u ON t.user_id = u.id
        WHERE s.id = ?
    ");
    
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Subject not found');
    }
    
    $subject = $result->fetch_assoc();
    
    echo json_encode([
        'id' => $subject['id'],
        'subject_code' => $subject['subject_code'],
        'subject_name' => $subject['subject_name'],
        'description' => $subject['description'],
        'teacher_id' => $subject['teacher_id'],
        'teacher_name' => $subject['teacher_name'],
        'student_count' => $subject['student_count'],
        'status' => $subject['status']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close(); 