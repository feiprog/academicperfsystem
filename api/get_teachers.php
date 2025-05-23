<?php
require_once '../auth_check.php';
require_once '../db.php';

// Ensure user is admin
requireAdmin();

try {
    // Get all teachers with their subjects
    $stmt = $conn->prepare("
        SELECT 
            t.id,
            t.teacher_id,
            u.full_name,
            u.email,
            t.department,
            t.status,
            GROUP_CONCAT(DISTINCT CONCAT(s.subject_code, ' - ', s.subject_name) SEPARATOR ', ') as subjects
        FROM teachers t
        JOIN users u ON t.user_id = u.id
        LEFT JOIN subjects s ON s.teacher_id = t.id
        GROUP BY t.id
        ORDER BY t.created_at DESC
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $teachers = [];
    while ($row = $result->fetch_assoc()) {
        $teachers[] = [
            'id' => $row['id'],
            'teacher_id' => $row['teacher_id'],
            'full_name' => $row['full_name'],
            'email' => $row['email'],
            'department' => $row['department'],
            'status' => $row['status'],
            'subjects' => $row['subjects']
        ];
    }

    echo json_encode($teachers);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch teachers', 'message' => $e->getMessage()]);
}

$conn->close(); 