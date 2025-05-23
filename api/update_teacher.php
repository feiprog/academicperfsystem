<?php
require_once '../auth_check.php';
require_once '../db.php';

// Ensure user is admin
requireAdmin();

// Get and validate input
$teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$department = isset($_POST['department']) ? trim($_POST['department']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : 'active';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

// Validate required fields
if (!$teacher_id || !$full_name || !$email || !$department) {
    http_response_code(400);
    echo json_encode(['error' => 'Required fields are missing']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format']);
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

    // Check if email exists for other users
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Email already exists for another user');
    }

    // Update user information
    if ($password) {
        // Update with new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET email = ?, full_name = ?, password = ? WHERE id = ?");
        $stmt->bind_param("sssi", $email, $full_name, $hashed_password, $user_id);
    } else {
        // Update without changing password
        $stmt = $conn->prepare("UPDATE users SET email = ?, full_name = ? WHERE id = ?");
        $stmt->bind_param("ssi", $email, $full_name, $user_id);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update user information');
    }

    // Update teacher information
    $stmt = $conn->prepare("UPDATE teachers SET department = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssi", $department, $status, $teacher_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update teacher information');
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Teacher updated successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close(); 