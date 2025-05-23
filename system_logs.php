<?php
require_once 'auth_check.php';
requireAdmin();

// Get admin data from teachers table
$stmt = $conn->prepare("
    SELECT t.teacher_id as admin_id, t.department 
    FROM users u 
    LEFT JOIN teachers t ON u.id = t.user_id 
    WHERE u.id = ? AND u.role = 'admin'
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$admin_data = $stmt->get_result()->fetch_assoc();

// Merge admin data with user data
$user = array_merge(getCurrentUser(), $admin_data ?? []);

// Get system logs (last 100 entries)
$stmt = $conn->prepare("
    SELECT * FROM (
        SELECT 
            'User Login' as activity_type,
            CONCAT(u.full_name, ' logged in') as description,
            u.role as user_role,
            l.created_at as timestamp
        FROM login_history l
        JOIN users u ON l.user_id = u.id
        ORDER BY l.created_at DESC
        LIMIT 20
    ) as login_logs
    
    UNION ALL
    
    SELECT * FROM (
        SELECT 
            'Grade Submission' as activity_type,
            CONCAT(tu.full_name, ' submitted grade for ', s.subject_name, ' - ', st.first_name, ' ', st.last_name) as description,
            tu.role as user_role,
            g.graded_at as timestamp
        FROM grades g
        JOIN students st ON g.student_id = st.id
        JOIN subjects s ON g.subject_id = s.id
        JOIN teachers t ON g.graded_by = t.id
        JOIN users tu ON t.user_id = tu.id
        ORDER BY g.graded_at DESC
        LIMIT 20
    ) as grade_logs
    
    UNION ALL
    
    SELECT * FROM (
        SELECT 
            'Student Registration' as activity_type,
            CONCAT(first_name, ' ', last_name, ' registered as new student') as description,
            'student' as user_role,
            created_at as timestamp
        FROM students
        ORDER BY created_at DESC
        LIMIT 20
    ) as registration_logs
    
    UNION ALL
    
    SELECT * FROM (
        SELECT 
            'Subject Assignment' as activity_type,
            CONCAT(s.subject_name, ' assigned to ', u.full_name) as description,
            u.role as user_role,
            s.created_at as timestamp
        FROM subjects s
        JOIN teachers t ON s.teacher_id = t.id
        JOIN users u ON t.user_id = u.id
        ORDER BY s.created_at DESC
        LIMIT 20
    ) as assignment_logs
    
    UNION ALL
    
    SELECT * FROM (
        SELECT 
            'Report Submission' as activity_type,
            CONCAT('Report submitted for ', s.subject_name, ' by ', st.first_name, ' ', st.last_name) as description,
            'student' as user_role,
            r.submission_date as timestamp
        FROM reports r
        JOIN students st ON r.student_id = st.id
        JOIN subjects s ON r.subject_id = s.id
        ORDER BY r.submission_date DESC
        LIMIT 20
    ) as report_logs
    
    ORDER BY timestamp DESC
    LIMIT 100
");
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - Academic Performance Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans min-h-screen">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-indigo-700 text-white shadow-xl">
        <div class="p-6">
            <h2 class="text-xl font-bold text-white">ADMIN DASHBOARD</h2>
            <div class="mt-4 flex justify-center">
                <div class="bg-white/10 p-4 rounded-xl backdrop-blur-sm">
                    <img src="images/logo.png" alt="School Logo" class="w-24 h-24 mx-auto">
                </div>
            </div>
            <h3 class="text-sm text-indigo-200 text-center mt-4">SYSTEM ADMINISTRATION</h3>
        </div>
        <nav class="mt-8">
            <ul class="space-y-1 px-3">
                <li>
                    <a href="admin_dashboard.php" class="flex items-center px-4 py-3 text-indigo-100 hover:bg-white/10 rounded-lg transition-colors">
                        <span class="mr-3">📊</span>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="manage_teachers.php" class="flex items-center px-4 py-3 text-indigo-100 hover:bg-white/10 rounded-lg transition-colors">
                        <span class="mr-3">👨‍🏫</span>
                        Manage Teachers
                    </a>
                </li>
                <li>
                    <a href="manage_subjects.php" class="flex items-center px-4 py-3 text-indigo-100 hover:bg-white/10 rounded-lg transition-colors">
                        <span class="mr-3">📚</span>
                        Manage Subjects
                    </a>
                </li>
                <li>
                    <a href="assign_subjects.php" class="flex items-center px-4 py-3 text-indigo-100 hover:bg-white/10 rounded-lg transition-colors">
                        <span class="mr-3">🔄</span>
                        Assign Subjects
                    </a>
                </li>
                <li>
                    <a href="system_logs.php" class="flex items-center px-4 py-3 text-white bg-white/10 rounded-lg backdrop-blur-sm">
                        <span class="mr-3">📋</span>
                        System Logs
                    </a>
                </li>
                <li>
                    <a href="logout.php" class="flex items-center px-4 py-3 text-indigo-100 hover:bg-white/10 rounded-lg transition-colors">
                        <span class="mr-3">🚪</span>
                        Logout
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <div class="ml-64 p-8">
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-2xl p-8 text-white shadow-lg mb-8">
            <h1 class="text-3xl font-semibold mb-2">System Logs</h1>
            <p class="text-indigo-100">Monitor system activities and user actions</p>
        </div>

        <!-- Activity Filters -->
        <div class="mb-8">
            <div class="flex gap-4">
                <select id="activityFilter" onchange="filterLogs()" 
                        class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Activities</option>
                    <option value="User Login">User Logins</option>
                    <option value="Grade Submission">Grade Submissions</option>
                    <option value="Student Registration">Student Registrations</option>
                    <option value="Subject Assignment">Subject Assignments</option>
                    <option value="Report Submission">Report Submissions</option>
                </select>
                
                <select id="roleFilter" onchange="filterLogs()"
                        class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="teacher">Teacher</option>
                    <option value="student">Student</option>
                </select>
            </div>
        </div>

        <!-- Activity Log -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-semibold text-gray-800">Recent Activities</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Role</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200" id="logsList">
                        <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-gray-50" data-activity="<?php echo htmlspecialchars($log['activity_type']); ?>" data-role="<?php echo htmlspecialchars($log['user_role']); ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M d, Y h:i A', strtotime($log['timestamp'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php
                                    switch ($log['activity_type']) {
                                        case 'User Login':
                                            echo 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'Grade Submission':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'Student Registration':
                                            echo 'bg-purple-100 text-purple-800';
                                            break;
                                        case 'Subject Assignment':
                                            echo 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'Report Submission':
                                            echo 'bg-pink-100 text-pink-800';
                                            break;
                                        default:
                                            echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo htmlspecialchars($log['activity_type']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo htmlspecialchars($log['description']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php
                                    switch ($log['user_role']) {
                                        case 'admin':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                        case 'teacher':
                                            echo 'bg-indigo-100 text-indigo-800';
                                            break;
                                        case 'student':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        default:
                                            echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst(htmlspecialchars($log['user_role'])); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function filterLogs() {
            const activityFilter = document.getElementById('activityFilter').value;
            const roleFilter = document.getElementById('roleFilter').value;
            const rows = document.querySelectorAll('#logsList tr');
            
            rows.forEach(row => {
                const activity = row.getAttribute('data-activity');
                const role = row.getAttribute('data-role');
                const showActivity = !activityFilter || activity === activityFilter;
                const showRole = !roleFilter || role === roleFilter;
                
                row.style.display = showActivity && showRole ? '' : 'none';
            });
        }

        // Auto-refresh logs every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html> 