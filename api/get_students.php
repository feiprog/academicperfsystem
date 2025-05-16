<?php
require_once '../auth_check.php';
requireTeacher();
header('Content-Type: application/json');

$sql = "SELECT 
            s.id,
            s.student_id,
            s.class,
            s.year_level,
            u.full_name as name
        FROM students s
        JOIN users u ON s.user_id = u.id
        ORDER BY u.full_name";

$result = $conn->query($sql);
$students = [];

while ($row = $result->fetch_assoc()) {
    $students[] = [
        'id' => $row['id'],
        'student_id' => $row['student_id'],
        'name' => $row['name'],
        'class' => $row['class'],
        'year_level' => $row['year_level']
    ];
}

echo json_encode($students);
?> 