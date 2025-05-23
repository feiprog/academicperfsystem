<?php
require_once '../auth_check.php';
require_once '../db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get teacher's ID
$user = getCurrentUser();
$stmt = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

if (!$teacher) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get and validate input
$subject_id = $_GET['subject_id'] ?? null;
$grade_type = $_GET['grade_type'] ?? 'all';

if (!$subject_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Subject ID is required']);
    exit;
}

try {
    // First verify teacher owns this subject
    $stmt = $conn->prepare("SELECT 1 FROM subjects WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $subject_id, $teacher['id']);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        throw new Exception('Subject not found or unauthorized');
    }

    // Get students and their grades in separate queries for better error handling
    $sql = "
        SELECT DISTINCT
            s.id as student_id,
            s.student_id as student_number,
            CONCAT(s.first_name, ' ', s.last_name) as student_name
        FROM students s
        JOIN student_subjects ss ON s.id = ss.student_id
        WHERE ss.subject_id = ? AND ss.status = 'active'
        ORDER BY s.last_name, s.first_name
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $subject_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $students = [];

    while ($student = $result->fetch_assoc()) {
        // Get grades for this student
        $grades_sql = "
            SELECT 
                id,
                category,
                grade_type,
                score,
                remarks,
                DATE_FORMAT(graded_at, '%Y-%m-%d') as graded_at
            FROM grades 
            WHERE student_id = ? AND subject_id = ?
        ";

        if ($grade_type !== 'all') {
            $grades_sql .= " AND grade_type = ?";
        }

        $grades_sql .= " ORDER BY graded_at DESC";

        $grades_stmt = $conn->prepare($grades_sql);
        if (!$grades_stmt) {
            throw new Exception("Prepare grades failed: " . $conn->error);
        }

        if ($grade_type !== 'all') {
            $grades_stmt->bind_param("iis", $student['student_id'], $subject_id, $grade_type);
        } else {
            $grades_stmt->bind_param("ii", $student['student_id'], $subject_id);
        }

        if (!$grades_stmt->execute()) {
            throw new Exception("Execute grades failed: " . $grades_stmt->error);
        }

        $grades_result = $grades_stmt->get_result();
        $grades = [];
        
        while ($grade = $grades_result->fetch_assoc()) {
            $grades[] = $grade;
        }

        // Calculate overall grade
        $overall_sql = "
            SELECT 
                ROUND(
                    SUM(CASE 
                        WHEN category = 'written' THEN score * 0.30
                        WHEN category = 'performance' THEN score * 0.20
                        WHEN category = 'exams' THEN score * 0.50
                    END) / COUNT(DISTINCT category), 1
                ) as overall_grade
            FROM grades 
            WHERE student_id = ? AND subject_id = ?
        ";

        $overall_stmt = $conn->prepare($overall_sql);
        if (!$overall_stmt) {
            throw new Exception("Prepare overall failed: " . $conn->error);
        }

        $overall_stmt->bind_param("ii", $student['student_id'], $subject_id);
        if (!$overall_stmt->execute()) {
            throw new Exception("Execute overall failed: " . $overall_stmt->error);
        }

        $overall_result = $overall_stmt->get_result()->fetch_assoc();
        $overall_grade = floatval($overall_result['overall_grade'] ?? 0);

        // Organize grades by category
        $gradesByCategory = [
            'written' => [],
            'performance' => [],
            'exams' => []
        ];

        foreach ($grades as $grade) {
            $category = $grade['category'];
            if (isset($gradesByCategory[$category])) {
                $gradesByCategory[$category][] = [
                    'id' => $grade['id'],
                    'grade_type' => ucfirst(str_replace('_', ' ', $grade['grade_type'])),
                    'score' => round($grade['score'], 1),
                    'remarks' => $grade['remarks'] ?? '',
                    'graded_at' => $grade['graded_at']
                ];
            }
        }

        // Determine status based on overall grade
        $status = 'No Grades';
        if ($overall_grade > 0) {
            if ($overall_grade >= 90) $status = 'Excellent';
            elseif ($overall_grade >= 80) $status = 'Good';
            elseif ($overall_grade >= 70) $status = 'Fair';
            else $status = 'Needs Improvement';
        }

        $students[] = [
            'student_id' => $student['student_number'],
            'student_name' => $student['student_name'],
            'grades' => [
                'written' => $gradesByCategory['written'],
                'performance' => $gradesByCategory['performance'],
                'exams' => $gradesByCategory['exams']
            ],
            'overall_grade' => $overall_grade,
            'status' => $status
        ];
    }

    echo json_encode($students);

} catch (Exception $e) {
    error_log("Error in get_subject_grades.php: " . $e->getMessage());
    error_log("SQL State: " . $conn->sqlstate);
    error_log("Error Code: " . $conn->errno);
    error_log("Error String: " . $conn->error);
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?> 