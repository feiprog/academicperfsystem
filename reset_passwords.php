<?php
require_once 'db.php';

echo "Resetting user passwords...\n";

// Reset teacher passwords
$teacher_password = password_hash('password123', PASSWORD_DEFAULT);
$sql = "UPDATE users SET password = ? WHERE role = 'teacher'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $teacher_password);
$stmt->execute();
echo "Reset teacher passwords\n";

// Reset admin password
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "UPDATE users SET password = ? WHERE role = 'admin'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $admin_password);
$stmt->execute();
echo "Reset admin password\n";

// Reset student passwords
$student_password = password_hash('password123', PASSWORD_DEFAULT);
$sql = "UPDATE users SET password = ? WHERE role = 'student'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_password);
$stmt->execute();
echo "Reset student passwords\n";

$conn->close();
echo "All passwords have been reset\n";
?> 