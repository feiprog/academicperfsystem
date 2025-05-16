<?php
require_once '../auth_check.php';
requireTeacher();
header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['student_id']) || !isset($data['subject_id']) || !isset($data['grade'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Validate grade value
$grade = floatval($data['grade']);
if ($grade < 0 || $grade > 100) {
    http_response_code(400);
    echo json_encode(['error' => 'Grade must be between 0 and 100']);
    exit;
}

// Get teacher ID
$teacher_id = $_SESSION['user_id'];

// Insert grade
$sql = "INSERT INTO grades (
            student_id,
            subject_id,
            teacher_id,
            grade,
            comments,
            created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "iiids",
    $data['student_id'],
    $data['subject_id'],
    $teacher_id,
    $grade,
    $data['comments']
);

if ($stmt->execute()) {
    // Get the inserted grade with student and subject details
    $grade_id = $conn->insert_id;
    $sql = "SELECT 
                g.id,
                g.grade,
                g.comments,
                g.created_at,
                s.student_id as student_number,
                u.full_name as student_name,
                sub.subject_code,
                sub.subject_name
            FROM grades g
            JOIN students s ON g.student_id = s.id
            JOIN users u ON s.user_id = u.id
            JOIN subjects sub ON g.subject_id = sub.id
            WHERE g.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $grade_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $grade_data = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => 'Grade submitted successfully',
        'grade' => $grade_data
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to submit grade']);
}
?> 