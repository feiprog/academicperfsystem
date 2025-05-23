<?php
require_once '../auth_check.php';
require_once '../db.php';

// Ensure user is admin
requireAdmin();

// Get and validate input
$data = json_decode(file_get_contents('php://input'), true);
$teacher_id = isset($data['teacher_id']) ? intval($data['teacher_id']) : 0;

if (!$teacher_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Teacher ID is required']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Get user ID for this teacher
    $stmt = $conn->prepare("SELECT user_id FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Teacher not found');
    }
    
    $user_id = $result->fetch_assoc()['user_id'];

    // Check if teacher has assigned subjects
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM subjects WHERE teacher_id = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $subject_count = $stmt->get_result()->fetch_assoc()['count'];

    if ($subject_count > 0) {
        throw new Exception('Cannot delete teacher with assigned subjects. Please reassign or remove subjects first.');
    }

    // Delete teacher record
    $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $teacher_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete teacher record');
    }

    // Delete user account
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete user account');
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Teacher deleted successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close(); 