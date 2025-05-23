<?php
require_once '../auth_check.php';
require_once '../includes/GradeService.php';
requireTeacher();

// Set content type to JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

try {
    // Initialize grade service
    $gradeService = new GradeService($conn, getCurrentUser());
    
    // Get grade ID
    $grade_id = $data['grade_id'] ?? null;
    if (!$grade_id) {
        throw new Exception('Grade ID is required');
    }
    
    // Prepare update data
    $updateData = [
        'student_id' => $data['student_id'] ?? null,
        'subject_id' => $data['subject_id'] ?? null,
        'category' => $data['category'] ?? null,
        'grade_type' => $data['grade_type'] ?? null,
        'score' => $data['score'] ?? null,
        'academic_year' => $data['academic_year'] ?? null,
        'term' => $data['term'] ?? null,
        'remarks' => $data['remarks'] ?? '',
        'reason' => $data['reason'] ?? 'Grade update'
    ];
    
    // Update grade
    $result = $gradeService->updateGrade($grade_id, $updateData);
    
    if ($result['success']) {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?> 