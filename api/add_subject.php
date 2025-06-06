<?php
require_once '../auth_check.php';
require_once '../db.php';

// Ensure user is admin
requireAdmin();

// Get and validate input
$subject_code = isset($_POST['subject_code']) ? trim($_POST['subject_code']) : '';
$subject_name = isset($_POST['subject_name']) ? trim($_POST['subject_name']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;
$status = isset($_POST['status']) ? trim($_POST['status']) : 'active';

// Validate required fields
if (!$subject_code || !$subject_name) {
    http_response_code(400);
    echo json_encode(['error' => 'Subject code and name are required']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if subject code already exists
    $stmt = $conn->prepare("SELECT id FROM subjects WHERE subject_code = ?");
    $stmt->bind_param("s", $subject_code);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Subject code already exists');
    }

    // If teacher_id is provided, verify it exists
    if ($teacher_id) {
        $stmt = $conn->prepare("SELECT id FROM teachers WHERE id = ?");
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            throw new Exception('Selected teacher does not exist');
        }
    }

    // Insert subject
    $stmt = $conn->prepare("
        INSERT INTO subjects (
            subject_code, 
            subject_name, 
            description, 
            teacher_id,
            status
        ) VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssis", $subject_code, $subject_name, $description, $teacher_id, $status);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create subject');
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Subject added successfully',
        'subject_id' => $stmt->insert_id
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close(); 