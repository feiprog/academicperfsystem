<?php
require_once '../auth_check.php';
require_once '../db.php';
header('Content-Type: application/json');

// Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Verify teacher authorization
requireTeacher();
$user = getCurrentUser();

// Get teacher's ID
$stmt = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

if (!$teacher) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get and validate input
$request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
$rejection_reason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : '';

if (!$request_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Request ID is required']);
    exit;
}

if (!$rejection_reason) {
    http_response_code(400);
    echo json_encode(['error' => 'Rejection reason is required']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Verify the request exists and belongs to a subject taught by this teacher
    $stmt = $conn->prepare("
        SELECT rr.* 
        FROM report_requests rr
        JOIN subjects s ON rr.subject_id = s.id
        WHERE rr.id = ? AND s.teacher_id = ? AND rr.status = 'pending'
    ");
    $stmt->bind_param("ii", $request_id, $teacher['id']);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();

    if (!$request) {
        throw new Exception('Report request not found or already processed');
    }

    // Update the request status to rejected
    $stmt = $conn->prepare("
        UPDATE report_requests 
        SET 
            status = 'rejected',
            response_notes = ?,
            response_by = ?,
            response_date = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("sii", $rejection_reason, $teacher['id'], $request_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update request status');
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Report request rejected successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to reject request',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?> 