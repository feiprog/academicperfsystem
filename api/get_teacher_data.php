<?php
require_once '../auth_check.php';
require_once '../db.php';

header('Content-Type: application/json');

try {
    $user_id = $_SESSION['user_id'];
    // Get teacher info by joining users and teachers
    $stmt = $conn->prepare("
        SELECT t.teacher_id, u.full_name, u.email, t.id as teacher_table_id
        FROM teachers t
        JOIN users u ON t.user_id = u.id
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $teacher = $stmt->get_result()->fetch_assoc();

    if (!$teacher) {
        throw new Exception("Teacher not found");
    }

    // Get subjects taught by this teacher
    $stmt = $conn->prepare("
        SELECT subject_name FROM subjects WHERE teacher_id = ?
    ");
    $stmt->bind_param("i", $teacher['teacher_table_id']);
    $stmt->execute();
    $subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $response = [
        'teacher_id' => $teacher['teacher_id'],
        'full_name' => $teacher['full_name'],
        'email' => $teacher['email'],
        'subjects' => $subjects
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 