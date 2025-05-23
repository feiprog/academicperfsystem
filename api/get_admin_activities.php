<?php
require_once '../auth_check.php';
require_once '../db.php';

// Ensure user is admin
requireAdmin();

header('Content-Type: application/json');

try {
    // Get recent activities (student registrations, grade submissions, report requests)
    $stmt = $conn->prepare("
        (SELECT 
            'New Student Registration' as activity_type,
            CONCAT(first_name, ' ', last_name) as description,
            created_at as timestamp,
            '👨‍🎓' as icon
        FROM students
        ORDER BY created_at DESC
        LIMIT 5)
        
        UNION ALL
        
        (SELECT 
            'Grade Submission' as activity_type,
            CONCAT('Grade submitted for ', s.subject_name) as description,
            g.graded_at as timestamp,
            '📝' as icon
        FROM grades g
        JOIN subjects s ON g.subject_id = s.id
        ORDER BY g.graded_at DESC
        LIMIT 5)
        
        UNION ALL
        
        (SELECT 
            'Report Request' as activity_type,
            CONCAT('New report request for ', s.subject_name) as description,
            rr.request_date as timestamp,
            '📋' as icon
        FROM report_requests rr
        JOIN subjects s ON rr.subject_id = s.id
        ORDER BY rr.request_date DESC
        LIMIT 5)
        
        ORDER BY timestamp DESC
        LIMIT 10
    ");
    
    $stmt->execute();
    $activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Format timestamps
    foreach ($activities as &$activity) {
        $timestamp = new DateTime($activity['timestamp']);
        $activity['timestamp'] = $timestamp->format('M d, Y h:i A');
    }

    echo json_encode($activities);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error fetching activities: ' . $e->getMessage()]);
}

$conn->close(); 