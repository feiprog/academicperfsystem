<?php
require_once '../auth_check.php';
require_once '../db.php';

// Ensure user is admin
requireAdmin();

// Get and validate input
$teacher_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$teacher_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Teacher ID is required']);
    exit;
}

try {
    // Get teacher details
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
        WHERE t.id = ?
        GROUP BY t.id
    ");
    
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Teacher not found');
    }
    
    $teacher = $result->fetch_assoc();
    
    echo json_encode([
        'id' => $teacher['id'],
        'teacher_id' => $teacher['teacher_id'],
        'full_name' => $teacher['full_name'],
        'email' => $teacher['email'],
        'department' => $teacher['department'],
        'status' => $teacher['status'],
        'subjects' => $teacher['subjects']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close(); 