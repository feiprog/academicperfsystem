<?php
require_once '../auth_check.php';
require_once '../db.php';

header('Content-Type: application/json');

// Only allow POST requests
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
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get and validate input
$student_id = $_POST['student_id'] ?? null;
$subject_id = $_POST['subject_id'] ?? null;
$category = $_POST['category'] ?? null;
$grade_type = $_POST['grade_type'] ?? null;
$score = $_POST['score'] ?? null;
$remarks = $_POST['remarks'] ?? '';

if (!$student_id || !$subject_id || !$category || !$grade_type || !$score || !is_numeric($score)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

// Validate score range (0-100)
if ($score < 0 || $score > 100) {
    http_response_code(400);
    echo json_encode(['error' => 'Score must be between 0 and 100']);
    exit;
}

// Validate category and grade type combination
$valid_categories = ['written', 'performance', 'exams'];
if (!in_array($category, $valid_categories)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid category']);
    exit;
}

$valid_grade_types = [
    'written' => ['assignment', 'activity', 'quiz'],
    'performance' => ['attendance'],
    'exams' => ['prelim', 'midterm', 'semi_final', 'final']
];

if (!isset($valid_grade_types[$category]) || !in_array($grade_type, $valid_grade_types[$category])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid grade type for selected category']);
    exit;
}

try {
    // Verify that the teacher owns this subject
    $stmt = $conn->prepare("SELECT 1 FROM subjects WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $subject_id, $teacher['id']);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        throw new Exception('Subject not found or unauthorized');
    }

    // Verify that the student is enrolled in this subject
    $stmt = $conn->prepare("
        SELECT 1 FROM student_subjects 
        WHERE student_id = ? AND subject_id = ? AND status = 'active'
    ");
    $stmt->bind_param("ii", $student_id, $subject_id);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        throw new Exception('Student is not enrolled in this subject');
    }

    // For exams, check if an entry already exists
    if ($category === 'exams') {
        $stmt = $conn->prepare("
            SELECT 1 FROM grades 
            WHERE student_id = ? AND subject_id = ? AND category = 'exams' AND grade_type = ?
        ");
        $stmt->bind_param("iis", $student_id, $subject_id, $grade_type);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            throw new Exception('An exam grade of this type already exists for this student');
        }
    }

    // Insert the grade
    $stmt = $conn->prepare("
        INSERT INTO grades (student_id, subject_id, category, grade_type, score, remarks, graded_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iissdsi", $student_id, $subject_id, $category, $grade_type, $score, $remarks, $teacher['id']);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Grade added successfully',
            'data' => [
                'grade_id' => $stmt->insert_id,
                'student_id' => $student_id,
                'subject_id' => $subject_id,
                'category' => $category,
                'grade_type' => $grade_type,
                'score' => $score,
                'remarks' => $remarks,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        throw new Exception('Failed to add grade');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?> 