<?php
require_once '../auth_check.php';
require_once '../db.php';

// Ensure user is admin
requireAdmin();

// Get and validate input
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$department = isset($_POST['department']) ? trim($_POST['department']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : 'active';

// Validate required fields
if (!$full_name || !$email || !$department || !$password || !$confirm_password) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}

// Check if passwords match
if ($password !== $confirm_password) {
    http_response_code(400);
    echo json_encode(['error' => 'Passwords do not match']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Email already exists');
    }

    // Generate teacher ID (e.g., TCH001)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM teachers");
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'];
    $teacher_id = 'TCH' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);

    // Insert user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (email, password, full_name, role) VALUES (?, ?, ?, 'teacher')");
    $stmt->bind_param("sss", $email, $hashed_password, $full_name);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create user account');
    }
    
    $user_id = $stmt->insert_id;

    // Insert teacher
    $stmt = $conn->prepare("INSERT INTO teachers (user_id, teacher_id, department, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $teacher_id, $department, $status);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create teacher record');
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Teacher added successfully',
        'teacher_id' => $teacher_id
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close(); 