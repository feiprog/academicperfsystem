<?php
require_once '../auth_check.php';
require_once '../db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get parameters
$subjectId = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
$gradeType = isset($_GET['grade_type']) ? $_GET['grade_type'] : 'all';

try {
    // First, verify that the subject belongs to the logged-in teacher
    $stmt = $conn->prepare("
        SELECT 1 
        FROM subjects s 
        JOIN teachers t ON s.teacher_id = t.id 
        WHERE s.id = ? AND t.user_id = ?
    ");
    $stmt->bind_param("ii", $subjectId, $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized access to subject']);
        exit;
    }

    // Build the query based on grade type filter
    $gradeTypeCondition = $gradeType !== 'all' ? "AND g.grade_type = ?" : "";
    
    $query = "
        SELECT 
            s.id AS student_id,
            s.student_id AS student_number,
            CONCAT(s.first_name, ' ', s.last_name) AS student_name,
            g.id AS grade_id,
            g.grade_type,
            g.score,
            DATE_FORMAT(g.graded_at, '%Y-%m-%d %h:%i %p') AS graded_at,
            CASE 
                WHEN g.score >= 90 THEN 'Excellent'
                WHEN g.score >= 80 THEN 'Good'
                WHEN g.score >= 70 THEN 'Fair'
                WHEN g.score >= 60 THEN 'Poor'
                WHEN g.score IS NULL THEN 'No Grade'
                ELSE 'Failed'
            END AS status
        FROM student_subjects ss
        JOIN students s ON ss.student_id = s.id
        LEFT JOIN grades g ON g.student_id = s.id AND g.subject_id = ss.subject_id
        WHERE ss.subject_id = ?
          AND ss.status = 'active'
        ORDER BY s.last_name, s.first_name, g.graded_at DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $subjectId);
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $grades = [];
    while ($row = $result->fetch_assoc()) {
        $grades[] = $row;
    }
    
    echo json_encode($grades);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?> 