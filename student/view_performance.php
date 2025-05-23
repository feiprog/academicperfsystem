<?php
require_once 'auth_check.php';
requireStudent();
$user = getCurrentUser();

// Get student's enrolled subjects with grades and attendance
$stmt = $conn->prepare("
    SELECT 
        s.id as subject_id,
        s.subject_code,
        s.subject_name,
        tu.full_name as teacher_name,
        tu.email as teacher_email,
        -- Written Works (30%)
        MAX(CASE WHEN g.grade_type = 'written_assignment' THEN g.score END) as assignment_score,
        MAX(CASE WHEN g.grade_type = 'written_activity' THEN g.score END) as activity_score,
        MAX(CASE WHEN g.grade_type = 'written_quiz' THEN g.score END) as quiz_score,
        -- Performance (20%)
        MAX(CASE WHEN g.grade_type = 'performance_attendance' THEN g.score END) as attendance_score,
        -- Examinations (50%)
        MAX(CASE WHEN g.grade_type = 'exam_prelim' THEN g.score END) as prelim_score,
        MAX(CASE WHEN g.grade_type = 'exam_midterm' THEN g.score END) as midterm_score,
        MAX(CASE WHEN g.grade_type = 'exam_semi_final' THEN g.score END) as semi_final_score,
        MAX(CASE WHEN g.grade_type = 'exam_final' THEN g.score END) as final_score,
        -- Attendance tracking
        COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.id END) as attendance_present,
        COUNT(DISTINCT a.id) as total_attendance,
        -- Activity tracking
        COUNT(DISTINCT act.id) as total_activities,
        COUNT(DISTINCT CASE WHEN act.status = 'completed' THEN act.id END) as completed_activities,
        -- Total grades
        COUNT(DISTINCT g.id) as total_grades
    FROM subjects s
    JOIN student_subjects ss ON s.id = ss.subject_id
    JOIN students st ON ss.student_id = st.id
    JOIN users u ON st.user_id = u.id
    LEFT JOIN teachers t ON s.teacher_id = t.id
    LEFT JOIN users tu ON t.user_id = tu.id
    LEFT JOIN grades g ON s.id = g.subject_id AND st.id = g.student_id
    LEFT JOIN attendance a ON s.id = a.subject_id AND st.id = a.student_id
    LEFT JOIN activities act ON s.id = act.subject_id
    WHERE u.id = ? AND ss.status = 'active'
    GROUP BY s.id, s.subject_code, s.subject_name, tu.full_name, tu.email
    ORDER BY s.subject_name
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get latest reports
$stmt = $conn->prepare("
    SELECT 
        r.id,
        r.report_type,
        r.content,
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

// Calculate overall statistics
$totalSubjects = count($subjects);
$totalGrade = 0;
$totalAttendance = 0;
$totalActivities = 0;
$completedActivities = 0;
$subjectsWithGrades = 0;

foreach ($subjects as $subject) {
    // Calculate final grade for each subject
    $writtenWorks = (
        ($subject['assignment_score'] * 0.10) +
        ($subject['activity_score'] * 0.10) +
        ($subject['quiz_score'] * 0.10)
    );
    $performance = $subject['attendance_score'] * 0.20;
    $examinations = (
        ($subject['prelim_score'] * 0.10) +
        ($subject['midterm_score'] * 0.15) +
        ($subject['semi_final_score'] * 0.10) +
        ($subject['final_score'] * 0.15)
    );
    $finalGrade = $writtenWorks + $performance + $examinations;

    if ($subject['total_grades'] > 0) {
        $totalGrade += $finalGrade;
        $subjectsWithGrades++;
    }
    $totalAttendance += $subject['attendance_present'];
    $totalActivities += $subject['total_activities'];
    $completedActivities += $subject['completed_activities'];
}

$overallAverage = $subjectsWithGrades > 0 ? round($totalGrade / $subjectsWithGrades, 2) : 0;
$attendanceRate = $totalAttendance > 0 ? round(($totalAttendance / count($subjects)) * 100, 2) : 0;
$activityCompletionRate = $totalActivities > 0 ? round(($completedActivities / $totalActivities) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Performance - Academic Performance Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <a href="student_dashboard.php" class="flex items-center px-4 py-3 text-sky-100 hover:bg-white/10 rounded-lg transition-colors">
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
                    <a href="view_performance.php" class="flex items-center px-4 py-3 text-white bg-white/10 rounded-lg backdrop-blur-sm">
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
        <!-- Header -->
        <div class="bg-gradient-to-r from-sky-500 to-sky-600 rounded-2xl p-8 text-white shadow-lg mb-8">
            <h1 class="text-3xl font-semibold mb-2">Performance Overview</h1>
            <p class="text-sky-100">Track your academic progress across all subjects</p>
        </div>

        <!-- Performance Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-2xl p-6 shadow-sm">
                <div class="text-3xl mb-3 text-sky-500">📊</div>
                <div class="text-2xl font-semibold mb-1">
                    <span class="px-3 py-1.5 rounded-lg <?php echo getGradeColorClass($overallAverage); ?>">
                        <?php echo $overallAverage; ?>%
                    </span>
                </div>
                <div class="text-gray-600">Overall Average</div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm">
                <div class="text-3xl mb-3 text-sky-500">📅</div>
                <div class="text-2xl font-semibold mb-1">
                    <span class="px-3 py-1.5 rounded-lg <?php echo getGradeColorClass($attendanceRate); ?>">
                        <?php echo $attendanceRate; ?>%
                    </span>
                </div>
                <div class="text-gray-600">Attendance Rate</div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm">
                <div class="text-3xl mb-3 text-sky-500">✅</div>
                <div class="text-2xl font-semibold mb-1">
                    <span class="px-3 py-1.5 rounded-lg <?php echo getGradeColorClass($activityCompletionRate); ?>">
                        <?php echo $activityCompletionRate; ?>%
                    </span>
                </div>
                <div class="text-gray-600">Activity Completion</div>
            </div>
        </div>

        <!-- Performance Chart -->
        <div class="bg-white rounded-2xl shadow-sm mb-8 overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-sky-50 to-white flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                    <span class="mr-2 text-sky-500">📈</span>
                    Performance Overview
                </h2>
                <div class="flex space-x-2">
                    <button onclick="switchChart('term')" id="termBtn" class="px-3 py-1.5 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 transition-colors">
                        Term Progress
                    </button>
                    <button onclick="switchChart('component')" id="componentBtn" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Component Analysis
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div id="termChart" class="h-[400px]">
                    <canvas id="performanceChart"></canvas>
                </div>
                <div id="componentChart" class="h-[400px] hidden">
                    <canvas id="componentAnalysisChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Subject Performance -->
        <div class="bg-white rounded-2xl shadow-sm mb-8 overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-sky-50 to-white">
                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                    <span class="mr-2 text-sky-500">📚</span>
                    Subject Performance
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Subject</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Written Works (30%)</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Performance (20%)</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Examinations (50%)</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Final Grade</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($subjects as $subject): 
                            // Calculate Written Works (30%)
                            $writtenWorks = (
                                ($subject['assignment_score'] * 0.10) +
                                ($subject['activity_score'] * 0.10) +
                                ($subject['quiz_score'] * 0.10)
                            );

                            // Calculate Performance (20%)
                            $performance = $subject['attendance_score'] * 0.20;

                            // Calculate Examinations (50%)
                            $examinations = (
                                ($subject['prelim_score'] * 0.10) +
                                ($subject['midterm_score'] * 0.15) +
                                ($subject['semi_final_score'] * 0.10) +
                                ($subject['final_score'] * 0.15)
                            );

                            // Calculate Final Grade
                            $finalGrade = $writtenWorks + $performance + $examinations;
                        ?>
                        <tr class="hover:bg-sky-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($subject['subject_code']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($subject['teacher_name']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <div class="text-xs text-gray-500">Assignments: <?php echo round($subject['assignment_score']); ?>%</div>
                                    <div class="text-xs text-gray-500">Activities: <?php echo round($subject['activity_score']); ?>%</div>
                                    <div class="text-xs text-gray-500">Quizzes: <?php echo round($subject['quiz_score']); ?>%</div>
                                    <div class="font-medium text-sm mt-1">Total: <?php echo round($writtenWorks, 1); ?>%</div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <div class="text-xs text-gray-500">Attendance: <?php echo round($subject['attendance_score']); ?>%</div>
                                    <div class="font-medium text-sm mt-1">Total: <?php echo round($performance, 1); ?>%</div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <div class="text-xs text-gray-500">Prelim: <?php echo round($subject['prelim_score']); ?>%</div>
                                    <div class="text-xs text-gray-500">Midterm: <?php echo round($subject['midterm_score']); ?>%</div>
                                    <div class="text-xs text-gray-500">Semi-Final: <?php echo round($subject['semi_final_score']); ?>%</div>
                                    <div class="text-xs text-gray-500">Final: <?php echo round($subject['final_score']); ?>%</div>
                                    <div class="font-medium text-sm mt-1">Total: <?php echo round($examinations, 1); ?>%</div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1.5 text-sm rounded-lg <?php echo getGradeColorClass($finalGrade); ?>">
                                    <?php echo round($finalGrade, 1); ?>%
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="view_subject.php?id=<?php echo $subject['subject_id']; ?>" 
                                   class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 transition-colors">
                                    🔍 Details
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Latest Reports -->
        <div class="bg-white rounded-2xl shadow-sm mb-8 overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-sky-50 to-white">
                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                    <span class="mr-2 text-sky-500">📄</span>
                    Latest Reports
                </h2>
            </div>
            <div class="divide-y divide-gray-100">
                <?php if (empty($reports)): ?>
                    <div class="p-6 text-center text-gray-500">
                        No reports available
                    </div>
                <?php else: ?>
                    <?php foreach ($reports as $report): ?>
                        <div class="p-6 hover:bg-sky-50/50 transition-colors">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <div class="font-medium text-gray-900">
                                        <?php echo htmlspecialchars($report['subject_name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        <?php echo ucfirst($report['report_type']); ?> Report
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium <?php echo getStatusColorClass($report['status']); ?>">
                                    <?php echo ucfirst($report['status']); ?>
                                </span>
                            </div>
                            <div class="text-sm text-gray-600 mt-2">
                                <?php echo nl2br(htmlspecialchars($report['content'])); ?>
                            </div>
                            <div class="text-xs text-gray-500 mt-2">
                                Submitted: <?php echo date('M d, Y', strtotime($report['submission_date'])); ?>
                                <?php if ($report['teacher_name']): ?>
                                    by <?php echo htmlspecialchars($report['teacher_name']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Initialize performance chart
        const ctx = document.getElementById('performanceChart').getContext('2d');
        const subjects = <?php echo json_encode(array_column($subjects, 'subject_name')); ?>;
        const subjectIds = <?php echo json_encode(array_column($subjects, 'subject_id')); ?>;
        const subjectCodes = <?php echo json_encode(array_column($subjects, 'subject_code')); ?>;
        
        // Calculate grades using the teacher's grading system
        const grades = <?php echo json_encode(array_map(function($s) {
            // Written Works (30%)
            $writtenWorks = 0;
            if ($s['assignment_score'] !== null) $writtenWorks += $s['assignment_score'] * 0.10; // 10%
            if ($s['activity_score'] !== null) $writtenWorks += $s['activity_score'] * 0.10;    // 10%
            if ($s['quiz_score'] !== null) $writtenWorks += $s['quiz_score'] * 0.10;           // 10%
            
            // Performance (20%)
            $performance = 0;
            if ($s['attendance_score'] !== null) $performance = $s['attendance_score'] * 0.20;
            
            // Examinations (50%)
            $examinations = 0;
            if ($s['prelim_score'] !== null) $examinations += $s['prelim_score'] * 0.10;       // 10%
            if ($s['midterm_score'] !== null) $examinations += $s['midterm_score'] * 0.15;     // 15%
            if ($s['semi_final_score'] !== null) $examinations += $s['semi_final_score'] * 0.10; // 10%
            if ($s['final_score'] !== null) $examinations += $s['final_score'] * 0.15;         // 15%
            
            // Return null if no grades are recorded yet
            $hasAnyGrade = $s['assignment_score'] !== null || $s['activity_score'] !== null || 
                          $s['quiz_score'] !== null || $s['attendance_score'] !== null ||
                          $s['prelim_score'] !== null || $s['midterm_score'] !== null ||
                          $s['semi_final_score'] !== null || $s['final_score'] !== null;
            
            return $hasAnyGrade ? round($writtenWorks + $performance + $examinations, 1) : null;
        }, $subjects)); ?>;
        
        const attendance = <?php echo json_encode(array_map(function($s) { 
            return $s['total_attendance'] > 0 ? round(($s['attendance_present'] / $s['total_attendance']) * 100, 2) : null; 
        }, $subjects)); ?>;
        
        const activities = <?php echo json_encode(array_map(function($s) { 
            return $s['total_activities'] > 0 ? round(($s['completed_activities'] / $s['total_activities']) * 100, 2) : null; 
        }, $subjects)); ?>;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: subjectCodes,
                datasets: [{
                    label: 'Overall Grade',
                    data: grades,
                    backgroundColor: 'rgba(14, 165, 233, 0.5)',
                    borderColor: 'rgb(14, 165, 233)',
                    borderWidth: 1
                }, {
                    label: 'Attendance',
                    data: attendance,
                    backgroundColor: 'rgba(34, 197, 94, 0.5)',
                    borderColor: 'rgb(34, 197, 94)',
                    borderWidth: 1
                }, {
                    label: 'Activities',
                    data: activities,
                    backgroundColor: 'rgba(234, 179, 8, 0.5)',
                    borderColor: 'rgb(234, 179, 8)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Percentage'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                const index = context[0].dataIndex;
                                return `${subjectCodes[index]}\n${subjects[index]}`;
                            },
                            label: function(context) {
                                const value = context.raw;
                                const datasetLabel = context.dataset.label;
                                if (value === null) {
                                    return `${datasetLabel}: No grades yet`;
                                }
                                return `${datasetLabel}: ${value}%`;
                            },
                            afterLabel: function(context) {
                                const index = context.dataIndex;
                                if (context.datasetIndex === 0) { // Only for grades dataset
                                    const subject = window.gradeDetails?.[index];
                                    if (subject) {
                                        if (subject.hasGrades) {
                                            return [
                                                '',
                                                `Written Works (30%): ${subject.writtenWorks}%`,
                                                `Performance (20%): ${subject.performance}%`,
                                                `Examinations (50%): ${subject.examinations}%`
                                            ];
                                        } else {
                                            return ['', 'No grades recorded yet'];
                                        }
                                    }
                                }
                                return null;
                            }
                        }
                    }
                }
            }
        });

        // Store grade details for tooltip
        window.gradeDetails = <?php echo json_encode(array_map(function($s) {
            $hasGrades = $s['assignment_score'] !== null || $s['activity_score'] !== null || 
                        $s['quiz_score'] !== null || $s['attendance_score'] !== null ||
                        $s['prelim_score'] !== null || $s['midterm_score'] !== null ||
                        $s['semi_final_score'] !== null || $s['final_score'] !== null;

            if (!$hasGrades) {
                return [
                    'hasGrades' => false,
                    'writtenWorks' => 0,
                    'performance' => 0,
                    'examinations' => 0
                ];
            }

            $writtenWorks = round(
                ($s['assignment_score'] ?? 0) * 0.10 +
                ($s['activity_score'] ?? 0) * 0.10 +
                ($s['quiz_score'] ?? 0) * 0.10,
                1
            );
            
            $performance = round(($s['attendance_score'] ?? 0) * 0.20, 1);
            
            $examinations = round(
                ($s['prelim_score'] ?? 0) * 0.10 +
                ($s['midterm_score'] ?? 0) * 0.15 +
                ($s['semi_final_score'] ?? 0) * 0.10 +
                ($s['final_score'] ?? 0) * 0.15,
                1
            );
            
            return [
                'hasGrades' => true,
                'writtenWorks' => $writtenWorks,
                'performance' => $performance,
                'examinations' => $examinations
            ];
        }, $subjects)); ?>;
    </script>

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
            case 'approved':
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