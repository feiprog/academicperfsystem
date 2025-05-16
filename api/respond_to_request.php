<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../auth_check.php';
requireTeacher();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

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

// Validate input
$request_id = $_POST['request_id'] ?? null;
$action = $_POST['action'] ?? null; // 'approve' or 'reject'
$response_notes = $_POST['response_notes'] ?? '';
$report_content = $_POST['report_content'] ?? null;

if (!$request_id || !in_array($action, ['approve', 'reject'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Verify the request exists and belongs to teacher's subject
    $stmt = $conn->prepare("
        SELECT rr.*, s.teacher_id 
        FROM report_requests rr
        JOIN subjects s ON rr.subject_id = s.id
        WHERE rr.id = ? AND rr.status = 'pending'
    ");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();

    if (!$request) {
        throw new Exception('Request not found or already processed');
    }

    if ($request['teacher_id'] != $teacher['id']) {
        throw new Exception('Unauthorized to respond to this request');
    }

    if ($action === 'approve') {
        if (!$report_content) {
            throw new Exception('Report content is required for approval');
        }

        // Extract grade from report_content (look for 'Current Grade:')
        $grade = null;
        if (preg_match('/Current Grade:\s*([0-9]{1,3})%?/', $report_content, $matches)) {
            $grade = floatval($matches[1]);
        }

        // Get attendance and activity completion from POST
        $attendance = isset($_POST['attendance']) ? floatval($_POST['attendance']) : null;
        $activity_completion = isset($_POST['activity_completion']) ? floatval($_POST['activity_completion']) : null;

        // Create a new report
        $stmt = $conn->prepare("
            INSERT INTO reports (
                student_id,
                subject_id,
                report_type,
                content,
                status,
                reviewed_by,
                reviewed_at
            ) VALUES (?, ?, ?, ?, 'approved', ?, NOW())
        ");
        $stmt->bind_param("iisss", 
            $request['student_id'],
            $request['subject_id'],
            $request['request_type'],
            $report_content,
            $teacher['id']
        );
        $stmt->execute();
        $report_id = $conn->insert_id;

        // Save grade if found
        if ($grade !== null) {
            $stmt = $conn->prepare("
                INSERT INTO grades (student_id, subject_id, grade_type, score, graded_by, graded_at)
                VALUES (?, ?, 'final', ?, ?, NOW())
                ON DUPLICATE KEY UPDATE score = VALUES(score), graded_by = VALUES(graded_by), graded_at = NOW()
            ");
            $stmt->bind_param("iidi", $request['student_id'], $request['subject_id'], $grade, $teacher['id']);
            $stmt->execute();
        }
        // Save attendance if found
        if ($attendance !== null) {
            $stmt = $conn->prepare("
                INSERT INTO grades (student_id, subject_id, grade_type, score, graded_by, graded_at)
                VALUES (?, ?, 'attendance', ?, ?, NOW())
                ON DUPLICATE KEY UPDATE score = VALUES(score), graded_by = VALUES(graded_by), graded_at = NOW()
            ");
            $stmt->bind_param("iidi", $request['student_id'], $request['subject_id'], $attendance, $teacher['id']);
            $stmt->execute();
        }
        // Save activity completion if found
        if ($activity_completion !== null) {
            $stmt = $conn->prepare("
                INSERT INTO grades (student_id, subject_id, grade_type, score, graded_by, graded_at)
                VALUES (?, ?, 'activity_completion', ?, ?, NOW())
                ON DUPLICATE KEY UPDATE score = VALUES(score), graded_by = VALUES(graded_by), graded_at = NOW()
            ");
            $stmt->bind_param("iidi", $request['student_id'], $request['subject_id'], $activity_completion, $teacher['id']);
            $stmt->execute();
        }

        // Update request status
        $stmt = $conn->prepare("
            UPDATE report_requests 
            SET status = 'completed',
                response_by = ?,
                response_date = CURRENT_TIMESTAMP,
                response_notes = ?,
                report_id = ?
            WHERE id = ?
        ");
        $stmt->bind_param("isii", $teacher['id'], $response_notes, $report_id, $request_id);
    } else {
        // Update request status to rejected
        $stmt = $conn->prepare("
            UPDATE report_requests 
            SET status = 'rejected',
                response_by = ?,
                response_date = CURRENT_TIMESTAMP,
                response_notes = ?
            WHERE id = ?
        ");
        $stmt->bind_param("isi", $teacher['id'], $response_notes, $request_id);
    }
    
    $stmt->execute();
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $action === 'approve' ? 'Request approved and report created' : 'Request rejected'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 