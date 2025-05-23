<?php
require_once '../auth_check.php';
require_once '../db.php';

// Ensure user is admin
requireAdmin();

// Get and validate input
$data = json_decode(file_get_contents('php://input'), true);
$subject_id = isset($data['subject_id']) ? intval($data['subject_id']) : 0;

if (!$subject_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Subject ID is required']);
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

    // Check if subject has enrolled students
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM student_subjects WHERE subject_id = ?");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $student_count = $stmt->get_result()->fetch_assoc()['count'];

    if ($student_count > 0) {
        throw new Exception('Cannot delete subject with enrolled students. Please remove students first.');
    }

    // Delete subject
    $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
    $stmt->bind_param("i", $subject_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete subject');
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Subject deleted successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close(); 