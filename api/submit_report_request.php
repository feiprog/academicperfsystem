<?php
// Error handling setup
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Custom error handler to convert PHP errors to JSON responses
function handleError($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server Error',
        'message' => $errstr,
        'file' => basename($errfile),
        'line' => $errline
    ]);
    exit;
}
set_error_handler('handleError');

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Fatal Error',
            'message' => $error['message'],
            'file' => basename($error['file']),
            'line' => $error['line']
        ]);
        exit;
    }
});

require_once '../auth_check.php';
require_once '../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user = getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get student ID
$stmt = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) {
    http_response_code(400);
    echo json_encode(['error' => 'Student not found']);
    exit;
}

// Validate input
$subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
$request_type = isset($_POST['request_type']) ? $_POST['request_type'] : '';
$request_reason = isset($_POST['request_reason']) ? $_POST['request_reason'] : '';
$term_period = isset($_POST['term_period']) ? $_POST['term_period'] : null;

if (!$subject_id || !$request_type || !$request_reason) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Validate term_period if request_type is 'term'
if ($request_type === 'term' && !in_array($term_period, ['preliminary', 'midterm', 'semi_final', 'final'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid term period']);
    exit;
}

// Insert report request
$stmt = $conn->prepare("
    INSERT INTO report_requests (
        student_id, 
        subject_id, 
        request_type, 
        term_period,
        request_reason, 
        status, 
        request_date
    ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())
");
$stmt->bind_param("iisss", 
    $student['id'],
    $subject_id,
    $request_type,
    $term_period,
    $request_reason
);

if ($stmt->execute()) {
    // Generate initial report content based on request type
    $report_id = $conn->insert_id;
    
    // If it's a term report, fetch the specific term's grades
    if ($request_type === 'term') {
        // Get grades for all terms for comparison
        $stmt = $conn->prepare("
            SELECT 
                -- Written Works (30%)
                AVG(CASE WHEN grade_type = 'assignment' THEN score END) as assignment_score,
                AVG(CASE WHEN grade_type = 'activity' THEN score END) as activity_score,
                AVG(CASE WHEN grade_type = 'quiz' THEN score END) as quiz_score,
                -- Performance (20%)
                AVG(CASE WHEN grade_type = 'attendance' THEN score END) as attendance_score,
                -- All Term Exams
                AVG(CASE WHEN grade_type = 'preliminary' THEN score END) as preliminary_score,
                AVG(CASE WHEN grade_type = 'midterm' THEN score END) as midterm_score,
                AVG(CASE WHEN grade_type = 'semifinal' THEN score END) as semifinal_score,
                AVG(CASE WHEN grade_type = 'final' THEN score END) as final_score
            FROM grades
            WHERE student_id = ? AND subject_id = ?
        ");
        $stmt->bind_param("ii", $student['id'], $subject_id);
        $stmt->execute();
        $grades = $stmt->get_result()->fetch_assoc();

        // Calculate component grades for the requested term
        $written_works = (
            (floatval($grades['assignment_score'] ?? 0) * 0.10) +
            (floatval($grades['activity_score'] ?? 0) * 0.10) +
            (floatval($grades['quiz_score'] ?? 0) * 0.10)
        );
        $performance = floatval($grades['attendance_score'] ?? 0) * 0.20;

        // Calculate term exam scores
        $term_scores = [
            'preliminary' => floatval($grades['preliminary_score'] ?? 0) * 0.50,
            'midterm' => floatval($grades['midterm_score'] ?? 0) * 0.50,
            'semifinal' => floatval($grades['semifinal_score'] ?? 0) * 0.50,
            'final' => floatval($grades['final_score'] ?? 0) * 0.50
        ];

        // Calculate total grades for each term
        $term_totals = [];
        foreach ($term_scores as $term => $exam_score) {
            $term_totals[$term] = $written_works + $performance + $exam_score;
        }

        // Generate report content
        $report_content = "COMPREHENSIVE TERM PERFORMANCE REPORT\n";
        $report_content .= "Requested Term: " . ucwords(str_replace('_', ' ', $term_period)) . "\n";
        $report_content .= "Date Generated: " . date('F d, Y') . "\n\n";

        $report_content .= "DETAILED ANALYSIS OF REQUESTED TERM\n";
        $report_content .= "================================\n";
        $report_content .= "1. Written Works (30%)\n";
        $report_content .= "   - Assignments: " . round(floatval($grades['assignment_score'] ?? 0), 1) . "/100 (" . round(floatval($grades['assignment_score'] ?? 0) * 0.10, 1) . "%)\n";
        $report_content .= "   - Activities: " . round(floatval($grades['activity_score'] ?? 0), 1) . "/100 (" . round(floatval($grades['activity_score'] ?? 0) * 0.10, 1) . "%)\n";
        $report_content .= "   - Quizzes: " . round(floatval($grades['quiz_score'] ?? 0), 1) . "/100 (" . round(floatval($grades['quiz_score'] ?? 0) * 0.10, 1) . "%)\n";
        $report_content .= "   Total Written Works: " . round($written_works, 1) . "%\n\n";

        $report_content .= "2. Performance (20%)\n";
        $report_content .= "   - Attendance: " . round(floatval($grades['attendance_score'] ?? 0), 1) . "/100 (" . round($performance, 1) . "%)\n";
        $report_content .= "   Total Performance: " . round($performance, 1) . "%\n\n";

        // Map term_period to the correct score key
        $score_key = $term_period . '_score';
        if ($term_period === 'semi_final') {
            $score_key = 'semifinal_score';
        }

        $report_content .= "3. Term Examination (50%)\n";
        $report_content .= "   - " . ucwords(str_replace('_', ' ', $term_period)) . " Exam: " . 
            round(floatval($grades[$score_key] ?? 0), 1) . "/100 (" . 
            round($term_scores[str_replace('semi_final', 'semifinal', $term_period)], 1) . "%)\n";
        $report_content .= "   Total Examination: " . round($term_scores[str_replace('semi_final', 'semifinal', $term_period)], 1) . "%\n\n";

        $report_content .= "REQUESTED TERM GRADE: " . round($term_totals[str_replace('semi_final', 'semifinal', $term_period)], 1) . "%\n";
        $report_content .= "Status: " . ($term_totals[str_replace('semi_final', 'semifinal', $term_period)] >= 75 ? "PASSED" : "NEEDS IMPROVEMENT") . "\n\n";

        $report_content .= "TERM COMPARISON ANALYSIS\n";
        $report_content .= "=======================\n";
        $report_content .= "Performance Across All Terms:\n";
        foreach ($term_totals as $term => $grade) {
            $display_term = str_replace('semifinal', 'Semi-Final', ucwords($term));
            $indicator = ($term === str_replace('semi_final', 'semifinal', $term_period)) ? "➡️ " : "   ";
            $report_content .= $indicator . $display_term . ": " . round($grade, 1) . "% - " . 
                ($grade >= 75 ? "PASSED" : "NEEDS IMPROVEMENT") . "\n";
        }

        $report_content .= "\nPROGRESS ANALYSIS\n";
        $report_content .= "=================\n";
        // Calculate progress indicators
        $terms_order = ['preliminary', 'midterm', 'semifinal', 'final'];
        $current_term_index = array_search(str_replace('semi_final', 'semifinal', $term_period), $terms_order);
        if ($current_term_index > 0) {
            $previous_term = $terms_order[$current_term_index - 1];
            $grade_change = $term_totals[str_replace('semi_final', 'semifinal', $term_period)] - $term_totals[$previous_term];
            $report_content .= sprintf(
                "Change from %s: %+.1f%% (%s)\n",
                str_replace('semifinal', 'Semi-Final', ucwords($previous_term)),
                $grade_change,
                $grade_change > 0 ? "Improvement" : ($grade_change < 0 ? "Decline" : "No Change")
            );
        }

        $report_content .= "\nRECOMMENDATIONS\n";
        $report_content .= "===============\n";
        if ($term_totals[str_replace('semi_final', 'semifinal', $term_period)] < 75) {
            $report_content .= "• Student needs additional support to improve performance\n";
            $report_content .= "• Recommended for academic intervention\n";
            $report_content .= "• Consider scheduling a consultation with the subject teacher\n";
        } else if ($term_totals[str_replace('semi_final', 'semifinal', $term_period)] >= 90) {
            $report_content .= "• Excellent performance - consider additional enrichment activities\n";
            $report_content .= "• Potential candidate for academic awards\n";
            $report_content .= "• Consider peer tutoring opportunities\n";
        } else {
            $report_content .= "• Maintaining satisfactory progress\n";
            $report_content .= "• Continue with current study habits\n";
            $report_content .= "• Regular attendance and participation recommended\n";
        }

        $report_content .= "\nNote: This is an automatically generated comprehensive term report.";
        $report_content .= "\nTeacher's additional comments will be added upon review.";

        // Insert the generated report
        $stmt = $conn->prepare("
            INSERT INTO reports (
                student_id,
                subject_id,
                report_type,
                term_period,
                content,
                status,
                submission_date
            ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->bind_param("iisss", 
            $student['id'],
            $subject_id,
            $request_type,
            $term_period,
            $report_content
        );
    $stmt->execute();
    }

    echo json_encode(['success' => true, 'message' => 'Report request submitted successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to submit request']);
}
?> 