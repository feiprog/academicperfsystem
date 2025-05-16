<?php
require_once '../auth_check.php';
require_once '../db.php';

header('Content-Type: application/json');

try {
    $teacher_id = $_SESSION['user_id'];
    
    // Get teacher information
    $stmt = $conn->prepare("
        SELECT t.*, GROUP_CONCAT(s.subject_name) as subject_names
        FROM teachers t
        LEFT JOIN subjects s ON t.id = s.teacher_id
        WHERE t.id = ?
        GROUP BY t.id
    ");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacher = $result->fetch_assoc();

    if (!$teacher) {
        throw new Exception("Teacher not found");
    }

    // Format the response
    $response = [
        'teacher_id' => $teacher['teacher_id'],
        'full_name' => $teacher['full_name'],
        'email' => $teacher['email'],
        'subjects' => array_map(function($subject) {
            return ['subject_name' => $subject];
        }, explode(',', $teacher['subject_names'] ?? ''))
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 