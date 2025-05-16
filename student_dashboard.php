<?php
require_once 'auth_check.php';
require_once 'components/buttons.php';
requireStudent();
$user = getCurrentUser();

// Get current academic term
$currentMonth = date('n');
$currentYear = date('Y');
$academicTerm = ($currentMonth >= 6 && $currentMonth <= 10) ? '1st Sem' : 
                (($currentMonth >= 11 && $currentMonth <= 3) ? '2nd Sem' : 'Summer');
$academicYear = ($currentMonth >= 6) ? $currentYear . '-' . ($currentYear + 1) : 
                ($currentYear - 1) . '-' . $currentYear;

// Get student's enrolled subjects with grades and teacher info
$stmt = $conn->prepare("
    SELECT 
        s.id as subject_id,
        s.subject_code,
        s.subject_name,
        tu.full_name as teacher_name,
        COALESCE(AVG(CASE WHEN g.grade_type = 'final' THEN g.score END), 0) as average_grade,
        COALESCE(AVG(CASE WHEN g.grade_type = 'attendance' THEN g.score END), 0) as attendance_rate,
        COALESCE(AVG(CASE WHEN g.grade_type = 'activity_completion' THEN g.score END), 0) as activity_completion,
        COUNT(DISTINCT CASE WHEN g.grade_type = 'final' THEN g.id END) as total_grades
    FROM subjects s
    JOIN student_subjects ss ON s.id = ss.subject_id
    JOIN students st ON ss.student_id = st.id
    JOIN users u ON st.user_id = u.id
    LEFT JOIN teachers t ON s.teacher_id = t.id
    LEFT JOIN users tu ON t.user_id = tu.id
    LEFT JOIN grades g ON s.id = g.subject_id AND st.id = g.student_id
    WHERE u.id = ? AND ss.status = 'active'
    GROUP BY s.id, s.subject_code, s.subject_name, tu.full_name
    LIMIT 10
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get latest reports
$stmt = $conn->prepare("
    SELECT 
        r.id,
        r.report_type,
        r.status,
        r.submission_date,
        s.subject_code,
        s.subject_name,
        tu.full_name as teacher_name
    FROM reports r
    JOIN subjects s ON r.subject_id = s.id
    JOIN students st ON r.student_id = st.id
    JOIN users u ON st.user_id = u.id
    LEFT JOIN teachers t ON r.reviewed_by = t.id
    LEFT JOIN users tu ON t.user_id = tu.id
    WHERE u.id = ?
    ORDER BY r.submission_date DESC
    LIMIT 5
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get pending report requests
$stmt = $conn->prepare("
    SELECT 
        rr.id,
        rr.request_type,
        rr.status,
        rr.request_date,
        s.subject_code,
        s.subject_name
    FROM report_requests rr
    JOIN subjects s ON rr.subject_id = s.id
    JOIN students st ON rr.student_id = st.id
    JOIN users u ON st.user_id = u.id
    WHERE u.id = ?
    ORDER BY rr.request_date DESC
    LIMIT 5
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate overall statistics
$totalGrade = 0;
$totalAttendance = 0;
$totalActivityCompletion = 0;
$subjectsWithGrades = 0;
foreach ($subjects as $subject) {
    if ($subject['total_grades'] > 0) {
        $totalGrade += $subject['average_grade'];
        $totalAttendance += $subject['attendance_rate'];
        $totalActivityCompletion += $subject['activity_completion'];
        $subjectsWithGrades++;
    }
}
$overallAverage = $subjectsWithGrades > 0 ? round($totalGrade / $subjectsWithGrades, 2) : 0;
$overallAttendance = $subjectsWithGrades > 0 ? round($totalAttendance / $subjectsWithGrades, 2) : 0;
$overallActivityCompletion = $subjectsWithGrades > 0 ? round($totalActivityCompletion / $subjectsWithGrades, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Academic Performance Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        'sky': {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-sky-50 font-sans min-h-screen">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-gradient-to-b from-sky-600 to-sky-700 text-white shadow-xl">
        <div class="p-6">
            <h2 class="text-xl font-bold text-white">STUDENT DASHBOARD</h2>
            <div class="mt-4 flex justify-center">
                <div class="bg-white/10 p-4 rounded-xl backdrop-blur-sm">
                    <img src="images/logo.png" alt="School Logo" class="w-24 h-24 mx-auto">
                </div>
            </div>
            <h3 class="text-sm text-sky-100 text-center mt-4">PERFORMANCE MONITORING</h3>
        </div>
        <nav class="mt-8">
            <ul class="space-y-1 px-3">
                <li>
                    <a href="student_dashboard.php" class="flex items-center px-4 py-3 text-white bg-white/10 rounded-lg backdrop-blur-sm">
                        <span class="mr-3">📊</span>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="request_report.php" class="flex items-center px-4 py-3 text-sky-100 hover:bg-white/10 rounded-lg transition-colors">
                        <span class="mr-3">📝</span>
                        Request Report
                    </a>
                </li>
                <li>
                    <a href="view_performance.php" class="flex items-center px-4 py-3 text-sky-100 hover:bg-white/10 rounded-lg transition-colors">
                        <span class="mr-3">📈</span>
                        View Performance
                    </a>
                </li>
                <li>
                    <a href="logout.php" class="flex items-center px-4 py-3 text-sky-100 hover:bg-white/10 rounded-lg transition-colors">
                        <span class="mr-3">🚪</span>
                        Logout
                    </a>
                </li>
        </ul>
        </nav>
    </div>

    <div class="ml-64 p-8">
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-sky-500 to-sky-600 rounded-2xl p-8 text-white shadow-lg mb-8">
            <h1 class="text-3xl font-semibold mb-2">Welcome, <?php echo htmlspecialchars($user['full_name']); ?>! 👋</h1>
            <div class="text-sky-100 space-x-4">
                <span>📘 Username: <?php echo htmlspecialchars($user['username']); ?></span>
                <span>|</span>
                <span>🎓 Academic Term: <?php echo $academicTerm . ' ' . $academicYear; ?></span>
        </div>
        </div>

        <!-- Quick Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="text-3xl mb-3 text-sky-500">📚</div>
                <div class="text-2xl font-semibold text-sky-600 mb-1"><?php echo count($subjects); ?></div>
                <div class="text-gray-600">Subjects Enrolled</div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="text-3xl mb-3 text-sky-500">🧮</div>
                <div class="text-2xl font-semibold mb-1">
                    <span class="px-3 py-1.5 rounded-lg <?php echo getGradeColorClass($overallAverage); ?>">
                        <?php echo $overallAverage; ?>%
                    </span>
                </div>
                <div class="text-gray-600">Current Average</div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="text-3xl mb-3 text-sky-500">📄</div>
                <div class="text-2xl font-semibold text-sky-600 mb-1"><?php echo count($reports); ?></div>
                <div class="text-gray-600">Reports Available</div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="text-3xl mb-3 text-sky-500">📌</div>
                <div class="text-2xl font-semibold text-sky-600 mb-1">
                    <?php echo count(array_filter($requests, function($r) { return $r['status'] === 'pending'; })); ?>
                </div>
                <div class="text-gray-600">Pending Requests</div>
            </div>
        </div>

        <!-- My Subjects -->
        <div class="bg-white rounded-2xl shadow-sm mb-8 overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-sky-50 to-white">
                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                    <span class="mr-2 text-sky-500">📚</span>
                    My Subjects
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Subject</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Instructor</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Grade</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($subjects as $subject): ?>
                        <tr class="hover:bg-sky-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                                <div class="text-xs text-gray-500">ID: <?php echo $subject['subject_id']; ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($subject['teacher_name']); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1.5 text-sm rounded-lg <?php echo getGradeColorClass($subject['average_grade']); ?>">
                                    <?php echo round($subject['average_grade']); ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Report Requests -->
        <div class="bg-white rounded-2xl shadow-sm mb-8 overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-sky-50 to-white">
                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                    <span class="mr-2 text-sky-500">📥</span>
                    Report Requests
                </h2>
            </div>
            <div class="p-6 text-center">
                <a href="request_report.php" 
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 transition-colors shadow-sm hover:shadow">
                    📨 Request Performance Report
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Subject</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($requests as $request): ?>
                        <tr class="hover:bg-sky-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($request['subject_name']); ?></td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium <?php echo getStatusColorClass($request['status']); ?>">
                                    <?php if ($request['status'] === 'completed'): ?>
                                        ✅ Done
                                    <?php elseif ($request['status'] === 'pending'): ?>
                                        ⏳ Pending
                                    <?php else: ?>
                                        <?php echo ucfirst($request['status']); ?>
                                    <?php endif; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Notifications -->
        <div class="bg-white rounded-2xl shadow-sm mb-8 overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-sky-50 to-white">
                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                    <span class="mr-2 text-sky-500">🔔</span>
                    Notifications
                </h2>
            </div>
            <div class="divide-y divide-gray-100">
                <?php
                // New reports notification
                $newReports = array_filter($reports, function($r) { 
                    return strtotime($r['submission_date']) > strtotime('-7 days'); 
                });
                foreach ($newReports as $report) {
                    echo '<div class="p-4 flex items-start space-x-3 hover:bg-sky-50/50 transition-colors">
                        <span class="text-xl text-sky-500">✅</span>
                        <div class="text-sm text-gray-600">Report from ' . htmlspecialchars($report['teacher_name']) . ' is now available</div>
                    </div>';
                }

                // Upcoming activities notification
                echo '<div class="p-4 flex items-start space-x-3 hover:bg-sky-50/50 transition-colors">
                    <span class="text-xl text-sky-500">🛠</span>
                    <div class="text-sm text-gray-600">Upcoming exam in Web Development next week</div>
                </div>';
                ?>
                </div>
            </div>

        <!-- Help Section -->
        <div class="bg-gradient-to-r from-sky-50 to-white rounded-2xl p-8 text-center shadow-sm">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Need Help?</h3>
            <div class="space-x-4">
                <a href="contact_admin.php" 
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 transition-colors shadow-sm hover:shadow">
                    💬 Contact Admin
                </a>
                <a href="faq.php" 
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 transition-colors shadow-sm hover:shadow">
                    ❓ View FAQ
                </a>
            </div>
        </div>
    </div>

    <?php
    function getGradeColorClass($grade) {
        if ($grade >= 90) return 'bg-green-100 text-green-800';
        if ($grade >= 80) return 'bg-sky-100 text-sky-800';
        if ($grade >= 70) return 'bg-yellow-100 text-yellow-800';
        if ($grade >= 60) return 'bg-orange-100 text-orange-800';
        return 'bg-red-100 text-red-800';
    }

    function getStatusColorClass($status) {
        switch ($status) {
            case 'completed':
                return 'bg-green-100 text-green-800';
            case 'pending':
                return 'bg-sky-100 text-sky-800';
            case 'rejected':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }
    ?>
</body>
</html> 