<?php
require_once '../auth_check.php';
requireStudent();
header('Content-Type: application/json');

$user = getCurrentUser();
$student_id = $user['role_id'];

// Get student's current performance
$sql = "SELECT 
            s.class,
            s.year_level,
            COALESCE(AVG(CASE 
                WHEN pr.grade = 'A' THEN 95
                WHEN pr.grade = 'A-' THEN 90
                WHEN pr.grade = 'B+' THEN 85
                WHEN pr.grade = 'B' THEN 80
                WHEN pr.grade = 'B-' THEN 75
                WHEN pr.grade = 'C+' THEN 70
                WHEN pr.grade = 'C' THEN 65
                WHEN pr.grade = 'C-' THEN 60
                WHEN pr.grade = 'D+' THEN 55
                WHEN pr.grade = 'D' THEN 50
                WHEN pr.grade = 'F' THEN 0
            END), 0) as average_score,
            MAX(pr.report_date) as last_updated
        FROM students s
        LEFT JOIN performance_reports pr ON s.id = pr.student_id
        WHERE s.student_id = ?
        GROUP BY s.id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$performance = $result->fetch_assoc();

// Calculate overall grade based on average score
$overall_grade = 'N/A';
if ($performance['average_score'] > 0) {
    if ($performance['average_score'] >= 90) $overall_grade = 'A';
    elseif ($performance['average_score'] >= 80) $overall_grade = 'B';
    elseif ($performance['average_score'] >= 70) $overall_grade = 'C';
    elseif ($performance['average_score'] >= 60) $overall_grade = 'D';
    else $overall_grade = 'F';
}

// Get recent reports
$sql = "SELECT 
            pr.subject,
            pr.grade,
            pr.comments,
            pr.report_date,
            DATE_FORMAT(pr.report_date, '%M %d, %Y') as formatted_date
        FROM performance_reports pr
        JOIN students s ON pr.student_id = s.id
        WHERE s.student_id = ?
        ORDER BY pr.report_date DESC
        LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$recent_reports = [];
while ($row = $result->fetch_assoc()) {
    $recent_reports[] = [
        'subject' => $row['subject'],
        'grade' => $row['grade'],
        'comments' => $row['comments'],
        'report_date' => $row['formatted_date']
    ];
}

// Get performance trend (last 4 terms)
$sql = "SELECT 
            DATE_FORMAT(pr.report_date, '%Y-%m') as month,
            AVG(CASE 
                WHEN pr.grade = 'A' THEN 95
                WHEN pr.grade = 'A-' THEN 90
                WHEN pr.grade = 'B+' THEN 85
                WHEN pr.grade = 'B' THEN 80
                WHEN pr.grade = 'B-' THEN 75
                WHEN pr.grade = 'C+' THEN 70
                WHEN pr.grade = 'C' THEN 65
                WHEN pr.grade = 'C-' THEN 60
                WHEN pr.grade = 'D+' THEN 55
                WHEN pr.grade = 'D' THEN 50
                WHEN pr.grade = 'F' THEN 0
            END) as average_score
        FROM performance_reports pr
        JOIN students s ON pr.student_id = s.id
        WHERE s.student_id = ?
        GROUP BY DATE_FORMAT(pr.report_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 4";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$performance_trend = [
    'labels' => [],
    'scores' => []
];

while ($row = $result->fetch_assoc()) {
    array_unshift($performance_trend['labels'], date('F Y', strtotime($row['month'] . '-01')));
    array_unshift($performance_trend['scores'], round($row['average_score'], 1));
}

// Prepare response
$response = [
    'overall_grade' => $overall_grade,
    'last_updated' => $performance['last_updated'] ? date('F d, Y', strtotime($performance['last_updated'])) : 'No reports yet',
    'class' => $performance['class'],
    'year_level' => $performance['year_level'],
    'recent_reports' => $recent_reports,
    'performance_trend' => $performance_trend
];

echo json_encode($response);
?> 