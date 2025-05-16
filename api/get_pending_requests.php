<?php
require_once '../auth_check.php';
requireTeacher();
header('Content-Type: application/json');

// Get teacher's ID
$user = getCurrentUser();
$stmt = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

if (!$teacher) {
    http_response_code(404);
    echo json_encode(['error' => 'Teacher record not found']);
    exit;
}

// Get pending requests for teacher's subjects
$stmt = $conn->prepare("
    SELECT 
        rr.id as request_id,
        rr.request_type,
        rr.request_reason,
        rr.request_date,
        rr.status,
        s.subject_code,
        s.subject_name,
        st.student_id,
        CONCAT(u.first_name, ' ', u.last_name) as student_name,
        u.email as student_email
    FROM report_requests rr
    JOIN subjects s ON rr.subject_id = s.id
    JOIN students st ON rr.student_id = st.id
    JOIN users u ON st.user_id = u.id
    WHERE s.teacher_id = ? AND rr.status = 'pending'
    ORDER BY rr.request_date DESC
");
$stmt->bind_param("i", $teacher['id']);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = [
        'request_id' => $row['request_id'],
        'request_type' => $row['request_type'],
        'request_reason' => $row['request_reason'],
        'request_date' => date('Y-m-d H:i:s', strtotime($row['request_date'])),
        'subject_code' => $row['subject_code'],
        'subject_name' => $row['subject_name'],
        'student_id' => $row['student_id'],
        'student_name' => $row['student_name'],
        'student_email' => $row['student_email']
    ];
}

echo json_encode($requests);
?> 