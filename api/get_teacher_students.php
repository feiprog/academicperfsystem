<?php
require_once '../auth_check.php';
require_once '../db.php';

header('Content-Type: application/json');

try {
    // Get teacher's ID
    $user = getCurrentUser();
    $stmt = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacher = $result->fetch_assoc();

    if (!$teacher) {
        throw new Exception('Teacher record not found');
    }

    $teacher_id = $teacher['id'];
    
    // Get students enrolled in teacher's subjects
    $stmt = $conn->prepare("
        SELECT DISTINCT
            s.id,
            s.student_id,
            CONCAT(s.first_name, ' ', s.last_name) as name,
            sub.subject_name,
            COALESCE(AVG(CASE WHEN g.grade_type = 'final' THEN g.score END), 0) as average_score,
            COALESCE(AVG(CASE WHEN g.grade_type = 'attendance' THEN g.score END), 0) as attendance,
            COALESCE(AVG(CASE WHEN g.grade_type = 'activity_completion' THEN g.score END), 0) as activity_completion,
            MAX(r.submission_date) as last_report
        FROM subjects sub
        JOIN student_subjects ss ON sub.id = ss.subject_id
        JOIN students s ON ss.student_id = s.id
        LEFT JOIN grades g ON s.id = g.student_id AND sub.id = g.subject_id
        LEFT JOIN reports r ON s.id = r.student_id AND sub.id = r.subject_id
        WHERE sub.teacher_id = ? AND ss.status = 'active'
        GROUP BY s.id, s.student_id, s.first_name, s.last_name, sub.subject_name
        ORDER BY s.last_name, s.first_name
    ");
    
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = [
            'id' => $row['id'],
            'student_id' => $row['student_id'],
            'name' => $row['name'],
            'subject_name' => $row['subject_name'],
            'average_score' => round($row['average_score'], 1),
            'attendance' => round($row['attendance'], 1),
            'activity_completion' => round($row['activity_completion'], 1),
            'last_report' => $row['last_report'] ? date('Y-m-d', strtotime($row['last_report'])) : null
        ];
    }

    echo json_encode($students);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 