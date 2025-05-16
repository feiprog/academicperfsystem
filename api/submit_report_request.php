<?php
require_once '../auth_check.php';
requireStudent();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get current user's student ID
$user = getCurrentUser();
$stmt = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    http_response_code(404);
    echo json_encode(['error' => 'Student record not found']);
    exit;
}

// Validate input
$subject_id = $_POST['subject_id'] ?? null;
$request_type = $_POST['request_type'] ?? null;
$request_reason = $_POST['request_reason'] ?? null;

if (!$subject_id || !$request_type || !$request_reason) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Verify student is enrolled in the subject
$stmt = $conn->prepare("
    SELECT 1 FROM student_subjects 
    WHERE student_id = ? AND subject_id = ? AND status = 'active'
");
$stmt->bind_param("ii", $student['id'], $subject_id);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    http_response_code(403);
    echo json_encode(['error' => 'You are not enrolled in this subject']);
    exit;
}

// Check for existing pending request
$stmt = $conn->prepare("
    SELECT 1 FROM report_requests 
    WHERE student_id = ? AND subject_id = ? AND status = 'pending'
");
$stmt->bind_param("ii", $student['id'], $subject_id);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    http_response_code(400);
    echo json_encode(['error' => 'You already have a pending request for this subject']);
    exit;
}

// Insert the request
$stmt = $conn->prepare("
    INSERT INTO report_requests (
        student_id, 
        subject_id, 
        request_type, 
        request_reason, 
        status
    ) VALUES (?, ?, ?, ?, 'pending')
");
$stmt->bind_param("iiss", $student['id'], $subject_id, $request_type, $request_reason);

try {
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => 'Report request submitted successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to submit request: ' . $e->getMessage()]);
}
?> 