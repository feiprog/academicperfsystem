<?php
require_once '../auth_check.php';
require_once '../db.php';

header('Content-Type: application/json');

try {
    $teacher_id = $_SESSION['user_id'];
    
    // Get students enrolled in teacher's subjects
    $stmt = $conn->prepare("
        SELECT 
            s.id,
            s.student_id,
            CONCAT(s.first_name, ' ', s.last_name) as name,
            sub.subject_name,
            COALESCE(AVG(g.score), 0) as average_score,
            COALESCE(AVG(a.status = 'present') * 100, 0) as attendance,
            MAX(r.submission_date) as last_report
        FROM students s
        JOIN student_subjects ss ON s.id = ss.student_id
        JOIN subjects sub ON ss.subject_id = sub.id
        LEFT JOIN grades g ON s.id = g.student_id AND sub.id = g.subject_id
        LEFT JOIN attendance a ON s.id = a.student_id AND sub.id = a.subject_id
        LEFT JOIN reports r ON s.id = r.student_id AND sub.id = r.subject_id
        WHERE sub.teacher_id = ?
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
            'last_report' => $row['last_report'] ? date('Y-m-d', strtotime($row['last_report'])) : null
        ];
    }

    echo json_encode($students);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 