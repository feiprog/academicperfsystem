<?php
require_once 'auth_check.php';
require_once 'db.php';
requireTeacher();

if (!isset($_GET['student_id']) || !isset($_GET['subject_id'])) {
    die('Missing student or subject.');
}
$student_id = intval($_GET['student_id']);
$subject_id = intval($_GET['subject_id']);

// Get student info
$stmt = $conn->prepare("SELECT s.student_id, CONCAT(s.first_name, ' ', s.last_name) as name, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Get subject info
$stmt = $conn->prepare("SELECT subject_code, subject_name, description FROM subjects WHERE id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();

if (!$subject) {
    die('Subject not found.');
}

// Get grades for this student in this subject
$stmt = $conn->prepare("SELECT grade_type, score, remarks, graded_at FROM grades WHERE student_id = ? AND subject_id = ? ORDER BY graded_at DESC");
$stmt->bind_param("ii", $student_id, $subject_id);
$stmt->execute();
$grades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get attendance for this student in this subject
$stmt = $conn->prepare("SELECT status, date FROM attendance WHERE student_id = ? AND subject_id = ? ORDER BY date DESC");
$stmt->bind_param("ii", $student_id, $subject_id);
$stmt->execute();
$attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get latest report for this student in this subject
$stmt = $conn->prepare("SELECT content, submission_date, status FROM reports WHERE student_id = ? AND subject_id = ? ORDER BY submission_date DESC LIMIT 1");
$stmt->bind_param("ii", $student_id, $subject_id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Performance Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="bg-sky-50 font-sans min-h-screen">
    <div class="max-w-3xl mx-auto mt-12 bg-white rounded-2xl shadow-xl p-8">
        <h1 class="text-2xl font-bold text-sky-700 mb-4">Student Performance Details</h1>
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-2">Student Information</h2>
            <div class="mb-1">ID: <span class="font-mono text-sky-700"><?php echo htmlspecialchars($student['student_id']); ?></span></div>
            <div class="mb-1">Name: <span class="font-semibold"><?php echo htmlspecialchars($student['name']); ?></span></div>
            <div class="mb-1">Email: <span class="text-gray-700"><?php echo htmlspecialchars($student['email']); ?></span></div>
        </div>
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-2">Subject Information</h2>
            <div class="mb-1">Subject: <span class="font-semibold"><?php echo htmlspecialchars($subject['subject_name']); ?></span></div>
            <div class="mb-1">Code: <span class="font-mono text-sky-700"><?php echo htmlspecialchars($subject['subject_code']); ?></span></div>
            <div class="mb-1">Description: <span class="text-gray-700"><?php echo nl2br(htmlspecialchars($subject['description'])); ?></span></div>
        </div>
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-2">Grades</h2>
            <?php if (empty($grades)): ?>
                <div class="text-gray-500">No grades recorded.</div>
            <?php else: ?>
                <table class="w-full mb-4">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-600">Type</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-600">Score</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-600">Remarks</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-600">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($grades as $grade): ?>
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900"><?php echo htmlspecialchars($grade['grade_type']); ?></td>
                            <td class="px-4 py-2 text-sm text-gray-900"><?php echo htmlspecialchars($grade['score']); ?></td>
                            <td class="px-4 py-2 text-sm text-gray-600"><?php echo htmlspecialchars($grade['remarks']); ?></td>
                            <td class="px-4 py-2 text-sm text-gray-600"><?php echo htmlspecialchars($grade['graded_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-2">Attendance</h2>
            <?php if (empty($attendance)): ?>
                <div class="text-gray-500">No attendance records.</div>
            <?php else: ?>
                <table class="w-full mb-4">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-600">Date</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-600">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($attendance as $att): ?>
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900"><?php echo htmlspecialchars($att['date']); ?></td>
                            <td class="px-4 py-2 text-sm text-gray-900"><?php echo htmlspecialchars($att['status']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-2">Latest Report</h2>
            <?php if (!$report): ?>
                <div class="text-gray-500">No report submitted yet.</div>
            <?php else: ?>
                <div class="bg-sky-50 p-4 rounded-lg mb-2">
                    <div class="mb-1 text-gray-700 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($report['content'])); ?></div>
                    <div class="text-xs text-gray-500">Submitted: <?php echo htmlspecialchars($report['submission_date']); ?> | Status: <?php echo htmlspecialchars($report['status']); ?></div>
                </div>
            <?php endif; ?>
        </div>
        <a href="teacher_dashboard.php" class="inline-block bg-sky-600 text-white px-6 py-2 rounded-lg hover:bg-sky-700 transition-colors font-medium">Back to Dashboard</a>
    </div>
</body>
</html> 