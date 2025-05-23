<?php
require_once '../auth_check.php';
require_once '../includes/GradeService.php';
requireTeacher();

header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

try {
    // Initialize grade service
    $gradeService = new GradeService($conn, getCurrentUser());
    
    // Prepare grade data
    $gradeData = [
        'student_id' => $data['student_id'] ?? null,
        'subject_id' => $data['subject_id'] ?? null,
        'category' => $data['category'] ?? null,
        'grade_type' => $data['grade_type'] ?? null,
        'score' => $data['score'] ?? null,
        'academic_year' => $data['academic_year'] ?? getCurrentAcademicYear(),
        'term' => $data['term'] ?? getCurrentTerm(),
        'remarks' => $data['remarks'] ?? ''
    ];
    
    // Submit grade
    $result = $gradeService->submitGrade($gradeData);
    
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

/**
 * Helper function to get current academic year
 */
function getCurrentAcademicYear() {
    $year = date('Y');
    $month = date('n');
    
    // If we're in the latter part of the year (August onwards),
    // the academic year is current year to next year
    if ($month >= 8) {
        return $year . '-' . ($year + 1);
    }
    
    // Otherwise, it's previous year to current year
    return ($year - 1) . '-' . $year;
}

/**
 * Helper function to get current term
 */
function getCurrentTerm() {
    $month = date('n');
    
    // First semester: August to December
    if ($month >= 8 && $month <= 12) {
        return 'First Semester';
    }
    
    // Second semester: January to May
    if ($month >= 1 && $month <= 5) {
        return 'Second Semester';
    }
    
    // Summer term: June to July
    return 'Summer';
}
?> 