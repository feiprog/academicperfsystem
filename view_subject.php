<?php
require_once 'auth_check.php';
require_once 'db.php';
requireStudent();

if (!isset($_GET['id'])) {
    die('No subject selected.');
}

$subject_id = intval($_GET['id']);

// Get subject details
$stmt = $conn->prepare("
    SELECT s.subject_code, s.subject_name, s.description, tu.full_name as teacher_name
    FROM subjects s
    LEFT JOIN teachers t ON s.teacher_id = t.id
    LEFT JOIN users tu ON t.user_id = tu.id
    WHERE s.id = ?
");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();

if (!$subject) {
    die('Subject not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Details - Academic Performance Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="bg-sky-50 font-sans min-h-screen">
    <div class="max-w-2xl mx-auto mt-12 bg-white rounded-2xl shadow-xl p-8">
        <h1 class="text-3xl font-bold text-sky-700 mb-4"><?php echo htmlspecialchars($subject['subject_name']); ?></h1>
        <div class="mb-4">
            <span class="inline-block bg-sky-100 text-sky-800 px-3 py-1 rounded-lg text-sm font-medium mr-2">
                Subject Code: <?php echo htmlspecialchars($subject['subject_code']); ?>
            </span>
            <span class="inline-block bg-green-100 text-green-800 px-3 py-1 rounded-lg text-sm font-medium">
                Instructor: <?php echo htmlspecialchars($subject['teacher_name']); ?>
            </span>
        </div>
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-2">Description</h2>
            <p class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($subject['description'])); ?></p>
        </div>
        <a href="student_dashboard.php" class="inline-block bg-sky-600 text-white px-6 py-2 rounded-lg hover:bg-sky-700 transition-colors font-medium">Back to Dashboard</a>
    </div>
</body>
</html> 