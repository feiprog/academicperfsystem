<?php
require_once '../auth_check.php';
requireTeacher();
header('Content-Type: application/json');

$sql = "SELECT 
            id,
            subject_code,
            subject_name,
            description
        FROM subjects
        ORDER BY subject_name";

$result = $conn->query($sql);
$subjects = [];

while ($row = $result->fetch_assoc()) {
    $subjects[] = [
        'id' => $row['id'],
        'subject_code' => $row['subject_code'],
        'subject_name' => $row['subject_name'],
        'description' => $row['description']
    ];
}

echo json_encode($subjects);
?> 