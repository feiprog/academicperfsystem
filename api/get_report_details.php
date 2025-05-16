<?php
require_once '../auth_check.php';
require_once '../db.php';
header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing report ID']);
    exit;
}
$stmt = $conn->prepare("
    SELECT r.id, r.report_type, r.status, r.submission_date, r.content,
           s.subject_name, st.first_name, st.last_name
    FROM reports r
    JOIN subjects s ON r.subject_id = s.id
    JOIN students st ON r.student_id = st.id
    WHERE r.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();
if (!$report) {
    http_response_code(404);
    echo json_encode(['error' => 'Report not found']);
    exit;
}
echo json_encode([
    'id' => $report['id'],
    'report_type' => ucfirst($report['report_type']),
    'status' => $report['status'],
    'submission_date' => date('Y-m-d', strtotime($report['submission_date'])),
    'content' => $report['content'],
    'student_name' => $report['first_name'] . ' ' . $report['last_name'],
    'subject_name' => $report['subject_name']
]); 