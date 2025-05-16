<?php
require_once '../auth_check.php';
require_once '../db.php';
header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing report ID']);
    exit;
}
$stmt = $conn->prepare("UPDATE reports SET status = 'approved' WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
echo json_encode(['success' => true, 'message' => 'Report approved']); 