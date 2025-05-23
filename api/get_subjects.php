<?php
require_once '../auth_check.php';
require_once '../db.php';

// Ensure user is admin
requireAdmin();

try {
    // Get all subjects with teacher names and student counts
    $stmt = $conn->prepare("
        SELECT 
            s.id,
            s.subject_code,
            s.subject_name,
            s.description,
            s.status,
            s.teacher_id,
            u.full_name as teacher_name,
            (SELECT COUNT(*) FROM student_subjects ss WHERE ss.subject_id = s.id) as student_count
        FROM subjects s
        LEFT JOIN teachers t ON s.teacher_id = t.id
        LEFT JOIN users u ON t.user_id = u.id
        ORDER BY s.created_at DESC
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = [
            'id' => $row['id'],
            'subject_code' => $row['subject_code'],
            'subject_name' => $row['subject_name'],
            'description' => $row['description'],
            'status' => $row['status'],
            'teacher_id' => $row['teacher_id'],
            'teacher_name' => $row['teacher_name'],
            'student_count' => $row['student_count']
        ];
    }

    echo json_encode($subjects);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch subjects', 'message' => $e->getMessage()]);
}

$conn->close();
?> 