<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Academic Performance Monitoring</title>
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
            <h2 class="text-xl font-bold text-white">TEACHER DASHBOARD</h2>
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
                    <a href="teacher_dashboard.php" class="flex items-center px-4 py-3 text-white bg-white/10 rounded-lg backdrop-blur-sm">
                        <span class="mr-3">📊</span>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="manage_grades.php" class="flex items-center px-4 py-3 text-sky-100 hover:bg-white/10 rounded-lg transition-colors">
                        <span class="mr-3">📝</span>
                        Manage Grades
                    </a>
                </li>
                <li>
                    <a href="student_reports.php" class="flex items-center px-4 py-3 text-sky-100 hover:bg-white/10 rounded-lg transition-colors">
                        <span class="mr-3">📄</span>
                        Student Reports
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
            <h1 class="text-3xl font-semibold mb-2">Welcome, <span id="teacherName">Teacher Name</span>! 👋</h1>
            <div class="text-sky-100 space-x-4">
                <span>👨‍🏫 ID: <span id="teacherId">Teacher ID</span></span>
                <span>|</span>
                <span>📚 Subjects: <span id="teacherSubjects">Loading...</span></span>
            </div>
        </div>

        <!-- Quick Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="text-3xl mb-3 text-sky-500">👥</div>
                <div class="text-2xl font-semibold text-sky-600 mb-1" id="totalStudents">0</div>
                <div class="text-gray-600">Total Students</div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="text-3xl mb-3 text-sky-500">📝</div>
                <div class="text-2xl font-semibold text-sky-600 mb-1" id="pendingReports">0</div>
                <div class="text-gray-600">Pending Reports</div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="text-3xl mb-3 text-sky-500">📊</div>
                <div class="text-2xl font-semibold text-sky-600 mb-1" id="monthlyReports">0</div>
                <div class="text-gray-600">Monthly Reports</div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="text-3xl mb-3 text-sky-500">📈</div>
                <div class="text-2xl font-semibold text-sky-600 mb-1" id="averagePerformance">0%</div>
                <div class="text-gray-600">Average Performance</div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Notifications Section -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-sky-50 to-white">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                        <span class="mr-2 text-sky-500">🔔</span>
                        Notifications & Alerts
                    </h2>
                </div>
                <div id="notificationsContainer" class="divide-y divide-gray-100">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>

            <!-- Pending Report Requests -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-sky-50 to-white">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                        <span class="mr-2 text-sky-500">📝</span>
                        Pending Report Requests
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Student</th>
                                <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Subject</th>
                                <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Type</th>
                                <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Date</th>
                                <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="pendingRequestsTable" class="divide-y divide-gray-100">
                            <!-- Will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Student Performance Overview -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-sky-50 to-white">
                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                    <span class="mr-2 text-sky-500">👥</span>
                    Student Performance Overview
                </h2>
            </div>
            <div class="p-6">
                <!-- Filters -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <input type="text" 
                           id="studentSearch" 
                           placeholder="Search students..." 
                           class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    <select id="classFilter" 
                            class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <option value="">All Classes</option>
                    </select>
                    <select id="performanceFilter" 
                            class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <option value="">All Performance Levels</option>
                        <option value="high">High Performance</option>
                        <option value="average">Average Performance</option>
                        <option value="low">Low Performance</option>
                    </select>
                    <select id="reportStatusFilter" 
                            class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <option value="">All Report Status</option>
                        <option value="pending">Pending Reports</option>
                        <option value="submitted">Submitted Reports</option>
                    </select>
                </div>

                <!-- Student Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Student ID</th>
                                <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Name</th>
                                <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Class</th>
                                <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Average Score</th>
                                <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Attendance</th>
                                <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Status</th>
                                <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="studentTableBody" class="divide-y divide-gray-100">
                            <!-- Will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Response Modal -->
        <div id="responseModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
            <div class="bg-white rounded-2xl p-8 max-w-2xl w-full mx-4">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-800">Respond to Report Request</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form id="responseForm" class="space-y-6">
                    <input type="hidden" id="requestId" name="request_id">
                    <div>
                        <label for="responseNotes" class="block text-sm font-medium text-gray-700 mb-2">Response Notes:</label>
                        <textarea id="responseNotes" 
                                  name="response_notes" 
                                  required
                                  class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 min-h-[100px]"></textarea>
                    </div>
                    <div id="reportContentGroup" class="hidden">
                        <label for="reportContent" class="block text-sm font-medium text-gray-700 mb-2">Report Content:</label>
                        <textarea id="reportContent" 
                                  name="report_content" 
                                  required
                                  class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 min-h-[150px]"></textarea>
                    </div>
                    <div class="flex justify-end space-x-4">
                        <button type="button" 
                                onclick="handleResponse('approve')"
                                class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            Approve
                        </button>
                        <button type="button" 
                                onclick="handleResponse('reject')"
                                class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Load teacher data
        function loadTeacherData() {
            // Fetch teacher data including assigned subjects
            fetch('api/get_teacher_data.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('teacherName').textContent = data.full_name;
                    document.getElementById('teacherId').textContent = data.teacher_id;
                    
                    // Update dashboard title with subject
                    if (data.subjects && data.subjects.length > 0) {
                        const subjectNames = data.subjects.map(s => s.subject_name).join(', ');
                        document.querySelector('.dashboard-header h2').textContent = 
                            `📊 Teacher Dashboard - ${subjectNames}`;
                    }
                })
                .catch(error => console.error('Error loading teacher data:', error));
        }

        // Load student data
        function loadStudentData() {
            // Fetch students enrolled in teacher's subjects
            fetch('api/get_teacher_students.php')
                .then(response => response.json())
                .then(students => {
                    const tableBody = document.getElementById('studentTableBody');
                    
                    tableBody.innerHTML = students.map(student => createStudentRow(student)).join('');

                    // Update statistics
                    updateStatistics(students);
                })
                .catch(error => console.error('Error loading student data:', error));
        }

        // Helper function to determine status class
        function getStatusClass(score) {
            if (!score) return 'pending';
            if (score >= 85) return 'high';
            if (score >= 75) return 'average';
            return 'low';
        }

        // Helper function to determine status text
        function getStatusText(score) {
            if (!score) return 'Pending';
            if (score >= 85) return 'High';
            if (score >= 75) return 'Average';
            return 'Low';
        }

        // Update statistics based on student data
        function updateStatistics(students) {
            const totalStudents = students.length;
            const averageScore = students.reduce((acc, student) => 
                acc + (student.average_score || 0), 0) / totalStudents || 0;
            const pendingReports = students.filter(s => !s.last_report).length;

            document.getElementById('totalStudents').textContent = totalStudents;
            document.getElementById('averagePerformance').textContent = 
                `${averageScore.toFixed(1)}%`;
            document.getElementById('pendingReports').textContent = pendingReports;
        }

        // Load notifications
        function loadNotifications() {
            fetch('api/get_teacher_notifications.php')
                .then(response => response.json())
                .then(notifications => {
                    const container = document.getElementById('notificationsContainer');
                    container.innerHTML = notifications.map(notification => createNotificationCard(notification)).join('');
                })
                .catch(error => console.error('Error loading notifications:', error));
        }

        // Search functionality
        document.getElementById('studentSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#studentTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Class filter functionality
        document.getElementById('classFilter').addEventListener('change', function(e) {
            const selectedClass = e.target.value;
            const rows = document.querySelectorAll('#studentTableBody tr');
            
            rows.forEach(row => {
                const studentClass = row.children[2].textContent;
                row.style.display = !selectedClass || studentClass === selectedClass ? '' : 'none';
            });
        });

        // View student details
        function viewStudent(studentId) {
            // This will be replaced with actual implementation
            console.log(`Viewing student: ${studentId}`);
            window.location.href = `student_details.php?id=${studentId}`;
        }

        // Edit grade
        function editGrade(studentId) {
            // This will be replaced with actual implementation
            console.log(`Editing grade for student: ${studentId}`);
            window.location.href = `edit_grade.php?id=${studentId}`;
        }

        // Load pending requests
        function loadPendingRequests() {
            fetch('api/get_pending_requests.php')
                .then(response => response.json())
                .then(requests => {
                    const tableBody = document.getElementById('pendingRequestsTable');
                    
                    if (requests.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No pending requests</td></tr>';
                        return;
                    }

                    tableBody.innerHTML = requests.map(request => `
                        <tr>
                            <td>
                                ${request.student_name}<br>
                                <small>${request.student_id}</small>
                            </td>
                            <td>${request.subject_code} - ${request.subject_name}</td>
                            <td>${request.request_type.charAt(0).toUpperCase() + request.request_type.slice(1)}</td>
                            <td>${new Date(request.request_date).toLocaleDateString()}</td>
                            <td>${request.request_reason}</td>
                            <td>
                                <button onclick="showResponseModal(${request.request_id})" class="action-button">
                                    Respond
                                </button>
                            </td>
                        </tr>
                    `).join('');
                })
                .catch(error => console.error('Error loading pending requests:', error));
        }

        // Show response modal
        function showResponseModal(requestId) {
            const modal = document.getElementById('responseModal');
            const reportContentGroup = document.getElementById('reportContentGroup');
            document.getElementById('requestId').value = requestId;
            document.getElementById('responseNotes').value = '';
            document.getElementById('reportContent').value = '';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // Close modal
        function closeModal() {
            const modal = document.getElementById('responseModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Handle response submission
        function handleResponse(action) {
            const form = document.getElementById('responseForm');
            const formData = new FormData(form);
            formData.append('action', action);

            // Show/hide report content field based on action
            const reportContentGroup = document.getElementById('reportContentGroup');
            const reportContent = document.getElementById('reportContent');
            if (action === 'approve') {
                reportContentGroup.style.display = 'block';
                reportContent.required = true;
            } else {
                reportContentGroup.style.display = 'none';
                reportContent.required = false;
            }

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            fetch('api/respond_to_request.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeModal();
                    loadPendingRequests();
                    loadNotifications();
                } else {
                    alert(data.error || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your response');
            });
        }

        // Update notification card styling
        function createNotificationCard(notification) {
            const bgColor = notification.type === 'alert' ? 'bg-red-50 border-red-200' : 'bg-yellow-50 border-yellow-200';
            const textColor = notification.type === 'alert' ? 'text-red-800' : 'text-yellow-800';
            const borderColor = notification.type === 'alert' ? 'border-red-500' : 'border-yellow-500';
            
            return `
                <div class="p-4 ${bgColor} border-l-4 ${borderColor}">
                    <div class="${textColor}">${notification.message}</div>
                </div>
            `;
        }

        // Update student table row styling
        function createStudentRow(student) {
            const statusClass = getStatusClass(student.average_score);
            const statusText = getStatusText(student.average_score);
            
            return `
                <tr class="hover:bg-sky-50/50 transition-colors">
                    <td class="px-6 py-4 text-sm text-gray-900">${student.student_id}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">${student.name}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">${student.subject_name}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="px-3 py-1.5 rounded-lg ${getGradeColorClass(student.average_score)}">
                            ${student.average_score || 'N/A'}%
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">${student.attendance || 'N/A'}%</td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1.5 rounded-lg ${statusClass}">
                            ${statusText}
                        </span>
                    </td>
                    <td class="px-6 py-4 space-x-2">
                        <button onclick="viewStudent('${student.id}')" 
                                class="px-3 py-1.5 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 transition-colors">
                            View
                        </button>
                        <button onclick="editGrade('${student.id}')" 
                                class="px-3 py-1.5 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 transition-colors">
                            Update
                        </button>
                    </td>
                </tr>
            `;
        }

        // Helper function for grade color classes
        function getGradeColorClass(grade) {
            if (!grade) return 'bg-gray-100 text-gray-800';
            if (grade >= 90) return 'bg-green-100 text-green-800';
            if (grade >= 80) return 'bg-sky-100 text-sky-800';
            if (grade >= 70) return 'bg-yellow-100 text-yellow-800';
            if (grade >= 60) return 'bg-orange-100 text-orange-800';
            return 'bg-red-100 text-red-800';
        }

        // Enhanced initialization
        document.addEventListener('DOMContentLoaded', () => {
            loadTeacherData();
            loadStudentData();
            loadNotifications();
            loadPendingRequests();
        });

        // Enhanced filter functionality
        document.getElementById('performanceFilter').addEventListener('change', function(e) {
            const selectedStatus = e.target.value;
            const rows = document.querySelectorAll('#studentTableBody tr');
            
            rows.forEach(row => {
                const status = row.querySelector('.status-badge').textContent;
                row.style.display = !selectedStatus || status === selectedStatus ? '' : 'none';
            });
        });

        document.getElementById('reportStatusFilter').addEventListener('change', function(e) {
            const selectedStatus = e.target.value;
            // Implementation for filtering by report status
        });
    </script>
</body>
</html> 