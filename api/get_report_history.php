<?php
require_once '../auth_check.php';
require_once '../db.php';
header('Content-Type: application/json');

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;
$subject_name = isset($_GET['subject_name']) ? $_GET['subject_name'] : null;

if (!$student_id || !$subject_name) {
    http_response_code(400);
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT r.report_type, r.status, r.submission_date, r.content
    FROM reports r
    JOIN subjects s ON r.subject_id = s.id
    WHERE r.student_id = ? AND s.subject_name = ?
    ORDER BY r.submission_date DESC
");
$stmt->bind_param("is", $student_id, $subject_name);
$stmt->execute();
$result = $stmt->get_result();
$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = [
        'report_type' => ucfirst($row['report_type']),
        'status' => ucfirst($row['status']),
        'submission_date' => date('Y-m-d', strtotime($row['submission_date'])),
        'content' => $row['content']
    ];
}
echo json_encode($history); 