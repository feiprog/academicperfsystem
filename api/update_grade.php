<?php
require_once '../auth_check.php';
require_once '../db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get POST data
$gradeId = isset($_POST['grade_id']) ? intval($_POST['grade_id']) : 0;
$gradeType = isset($_POST['grade_type']) ? $_POST['grade_type'] : '';
$score = isset($_POST['score']) ? floatval($_POST['score']) : 0;
$comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';

// Validate input
if (!$gradeId || !$gradeType || $score < 0 || $score > 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // First, verify that the grade belongs to a subject assigned to the logged-in teacher
    $stmt = $conn->prepare("
        SELECT 1 
        FROM grades g
        JOIN students s ON g.student_id = s.id
        JOIN student_subjects ss ON s.id = ss.student_id
        JOIN subjects sub ON ss.subject_id = sub.id
        JOIN teachers t ON sub.teacher_id = t.id
        WHERE g.id = ? AND t.user_id = ?
    ");
    $stmt->bind_param("ii", $gradeId, $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('Unauthorized access to grade');
    }

    // Update the grade
    $stmt = $conn->prepare("
        UPDATE grades 
        SET grade_type = ?, 
            score = ?, 
            comments = ?, 
            graded_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->bind_param("sdsi", $gradeType, $score, $comments, $gradeId);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update grade');
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Grade updated successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?> 