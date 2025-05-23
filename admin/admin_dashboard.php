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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Academic Performance Monitoring</title>
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
                    <a href="admin_dashboard.php" class="flex items-center px-4 py-3 text-white bg-white/10 rounded-lg backdrop-blur-sm">
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
                    <a href="system_logs.php" class="flex items-center px-4 py-3 text-indigo-100 hover:bg-white/10 rounded-lg transition-colors">
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
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-2xl p-8 text-white shadow-lg mb-8">
            <h1 class="text-3xl font-semibold mb-2">Welcome, <?php echo htmlspecialchars($user['full_name']); ?>! 👋</h1>
            <div class="text-indigo-100">
                <span>👨‍💼 Admin ID: <?php echo htmlspecialchars($user['admin_id']); ?></span>
                <span class="mx-2">|</span>
                <span>🏢 <?php echo htmlspecialchars($user['department']); ?></span>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl p-6 shadow-sm">
                <div class="text-3xl mb-3 text-indigo-500">👨‍🏫</div>
                <div class="text-2xl font-semibold text-gray-700 mb-1" id="totalTeachers">0</div>
                <div class="text-gray-600">Total Teachers</div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm">
                <div class="text-3xl mb-3 text-indigo-500">📚</div>
                <div class="text-2xl font-semibold text-gray-700 mb-1" id="totalSubjects">0</div>
                <div class="text-gray-600">Active Subjects</div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm">
                <div class="text-3xl mb-3 text-indigo-500">👥</div>
                <div class="text-2xl font-semibold text-gray-700 mb-1" id="totalStudents">0</div>
                <div class="text-gray-600">Total Students</div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm">
                <div class="text-3xl mb-3 text-indigo-500">⚠️</div>
                <div class="text-2xl font-semibold text-gray-700 mb-1" id="pendingIssues">0</div>
                <div class="text-gray-600">Pending Issues</div>
            </div>
        </div>

        <!-- Recent Activities & System Status -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent Activities -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-xl font-semibold text-gray-800">Recent Activities</h2>
                </div>
                <div class="divide-y divide-gray-100" id="recentActivities">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>

            <!-- System Status -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-xl font-semibold text-gray-800">System Status</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700">System Load</span>
                                <span class="text-sm text-gray-600" id="systemLoad">0%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-indigo-500 h-2 rounded-full" style="width: 0%" id="systemLoadBar"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700">Database Status</span>
                                <span class="text-sm text-green-600" id="dbStatus">Connected</span>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700">Last Backup</span>
                                <span class="text-sm text-gray-600" id="lastBackup">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load dashboard data
        function loadDashboardData() {
            fetch('api/get_admin_dashboard_data.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('totalTeachers').textContent = data.totalTeachers;
                    document.getElementById('totalSubjects').textContent = data.totalSubjects;
                    document.getElementById('totalStudents').textContent = data.totalStudents;
                    document.getElementById('pendingIssues').textContent = data.pendingIssues;
                    
                    // Update system status
                    document.getElementById('systemLoad').textContent = data.systemLoad + '%';
                    document.getElementById('systemLoadBar').style.width = data.systemLoad + '%';
                    document.getElementById('dbStatus').textContent = data.dbStatus;
                    document.getElementById('lastBackup').textContent = data.lastBackup;
                })
                .catch(error => console.error('Error loading dashboard data:', error));
        }

        // Load recent activities
        function loadRecentActivities() {
            fetch('api/get_admin_activities.php')
                .then(response => response.json())
                .then(activities => {
                    const container = document.getElementById('recentActivities');
                    container.innerHTML = activities.map(activity => `
                        <div class="p-4 hover:bg-gray-50">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 text-indigo-500 flex items-center justify-center">
                                    ${activity.icon}
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-900">${activity.description}</p>
                                    <p class="text-sm text-gray-500">${activity.timestamp}</p>
                                </div>
                            </div>
                        </div>
                    `).join('');
                })
                .catch(error => console.error('Error loading activities:', error));
        }

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', () => {
            loadDashboardData();
            loadRecentActivities();
            // Refresh data every 30 seconds
            setInterval(loadDashboardData, 30000);
            setInterval(loadRecentActivities, 30000);
        });
    </script>
</body>
</html> 