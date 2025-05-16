<?php
require_once '../auth_check.php';
require_once '../db.php';

header('Content-Type: application/json');

try {
    $teacher_id = $_SESSION['user_id'];
    
    // Get notifications for teacher's subjects
    $stmt = $conn->prepare("
        WITH subject_alerts AS (
            -- Low attendance alerts
            SELECT 
                'warning' as type,
                CONCAT(COUNT(DISTINCT s.id), ' students have attendance below 75% in ', sub.subject_name) as message
            FROM students s
            JOIN student_subjects ss ON s.id = ss.student_id
            JOIN subjects sub ON ss.subject_id = sub.id
            LEFT JOIN attendance a ON s.id = a.student_id AND sub.id = a.subject_id
            WHERE sub.teacher_id = ?
            GROUP BY sub.id, sub.subject_name
            HAVING AVG(a.status = 'present') * 100 < 75
        ),
        pending_reports AS (
            -- Pending report alerts
            SELECT 
                'alert' as type,
                CONCAT(COUNT(DISTINCT s.id), ' pending report requests in ', sub.subject_name) as message
            FROM students s
            JOIN student_subjects ss ON s.id = ss.student_id
            JOIN subjects sub ON ss.subject_id = sub.id
            LEFT JOIN reports r ON s.id = r.student_id AND sub.id = r.subject_id
            WHERE sub.teacher_id = ?
            AND (r.id IS NULL OR r.status = 'pending')
            GROUP BY sub.id, sub.subject_name
            HAVING COUNT(DISTINCT s.id) > 0
        ),
        upcoming_activities AS (
            -- Upcoming activity alerts
            SELECT 
                'info' as type,
                CONCAT('New assessment scheduled for ', sub.subject_name, ' on ', 
                    DATE_FORMAT(a.scheduled_date, '%M %d, %Y')) as message
            FROM activities a
            JOIN subjects sub ON a.subject_id = sub.id
            WHERE sub.teacher_id = ?
            AND a.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        )
        SELECT * FROM subject_alerts
        UNION ALL
        SELECT * FROM pending_reports
        UNION ALL
        SELECT * FROM upcoming_activities
        ORDER BY type = 'alert' DESC, type = 'warning' DESC, type = 'info' DESC
        LIMIT 5
    ");
    
    $stmt->bind_param("iii", $teacher_id, $teacher_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'type' => $row['type'],
            'message' => $row['message']
        ];
    }

    echo json_encode($notifications);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 