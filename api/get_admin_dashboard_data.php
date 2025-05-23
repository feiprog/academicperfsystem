<?php
require_once '../auth_check.php';
require_once '../db.php';

// Ensure user is admin
requireAdmin();

header('Content-Type: application/json');

try {
    // Get total number of teachers
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'");
    $stmt->execute();
    $totalTeachers = $stmt->get_result()->fetch_assoc()['count'];

    // Get total number of active subjects
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM subjects");
    $stmt->execute();
    $totalSubjects = $stmt->get_result()->fetch_assoc()['count'];

    // Get total number of students
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $stmt->execute();
    $totalStudents = $stmt->get_result()->fetch_assoc()['count'];

    // Get number of pending issues (e.g., pending grade reports, attendance issues)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM report_requests 
        WHERE status = 'pending'
    ");
    $stmt->execute();
    $pendingIssues = $stmt->get_result()->fetch_assoc()['count'];

    // Get system status
    $systemLoad = rand(20, 80); // This would be replaced with actual server load metrics
    $dbStatus = $conn->ping() ? 'Connected' : 'Disconnected';
    
    // Get last backup time (mock data - would be replaced with actual backup tracking)
    $lastBackup = date('Y-m-d H:i:s', strtotime('-1 day'));

    $response = [
        'totalTeachers' => $totalTeachers,
        'totalSubjects' => $totalSubjects,
        'totalStudents' => $totalStudents,
        'pendingIssues' => $pendingIssues,
        'systemLoad' => $systemLoad,
        'dbStatus' => $dbStatus,
        'lastBackup' => $lastBackup
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error fetching dashboard data: ' . $e->getMessage()]);
}

$conn->close(); 