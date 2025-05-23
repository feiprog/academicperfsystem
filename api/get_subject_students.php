<?php
require_once '../auth_check.php';
require_once '../db.php';

header('Content-Type: application/json');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get teacher's ID
$user = getCurrentUser();
$stmt = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

if (!$teacher) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get subject ID from query parameters
$subject_id = $_GET['subject_id'] ?? null;

if (!$subject_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Subject ID is required']);
    exit;
}

try {
    // Verify that the teacher owns this subject
    $stmt = $conn->prepare("SELECT 1 FROM subjects WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $subject_id, $teacher['id']);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        throw new Exception('Subject not found or unauthorized');
    }

    // Get enrolled students
    $stmt = $conn->prepare("
        SELECT 
            s.id,
            s.student_id,
            CONCAT(s.first_name, ' ', s.last_name) as name
        FROM students s
        JOIN student_subjects ss ON s.id = ss.student_id
        WHERE ss.subject_id = ? 
        AND ss.status = 'active'
        ORDER BY s.last_name, s.first_name
    ");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    echo json_encode($students);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?> 