<?php
require_once '../auth_check.php';
require_once '../db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get the teacher's ID
$stmt = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($teacher_id);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Teacher not found']);
    exit;
}
$stmt->close();

// Get all subject IDs for this teacher
$subject_ids = [];
$stmt = $conn->prepare("SELECT id FROM subjects WHERE teacher_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $subject_ids[] = $row['id'];
}
$stmt->close();

if (empty($subject_ids)) {
    echo json_encode([]);
    exit;
}

// Build the IN clause dynamically
$in = str_repeat('?,', count($subject_ids) - 1) . '?';
$types = str_repeat('i', count($subject_ids));
$sql = "SELECT r.*, s.subject_name, st.first_name, st.last_name
        FROM reports r
        JOIN subjects s ON r.subject_id = s.id
        JOIN students st ON r.student_id = st.id
        WHERE r.subject_id IN ($in)
        ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$subject_ids);
$stmt->execute();
$result = $stmt->get_result();

$reports = [];
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}
$stmt->close();

echo json_encode($reports); 