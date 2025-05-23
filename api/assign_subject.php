<?php
require_once '../auth_check.php';
require_once '../db.php';

// Ensure user is admin
requireAdmin();

// Get and validate input
$data = json_decode(file_get_contents('php://input'), true);
$subject_id = isset($data['subject_id']) ? intval($data['subject_id']) : 0;
$teacher_id = isset($data['teacher_id']) ? intval($data['teacher_id']) : 0;

if (!$subject_id || !$teacher_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Subject ID and teacher ID are required']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if subject exists
    $stmt = $conn->prepare("SELECT id FROM subjects WHERE id = ?");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('Subject not found');
    }

    // Check if teacher exists
    $stmt = $conn->prepare("SELECT id FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('Teacher not found');
    }

    // Update subject's teacher assignment
    $stmt = $conn->prepare("UPDATE subjects SET teacher_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $teacher_id, $subject_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to assign subject');
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Subject assigned successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close(); 