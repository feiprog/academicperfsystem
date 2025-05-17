<?php
require_once 'auth_check.php';
require_once 'db.php';

// Get teacher's subjects
$stmt = $conn->prepare("
    SELECT DISTINCT s.id, s.subject_code, s.subject_name 
    FROM subjects s 
    INNER JOIN teachers t ON s.teacher_id = t.id
    INNER JOIN users u ON t.user_id = u.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Grades - Academic Performance Monitoring</title>
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
                    <a href="teacher_dashboard.php" class="flex items-center px-4 py-3 text-sky-100 hover:bg-white/10 rounded-lg transition-colors">
                        <span class="mr-3">📊</span>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="manage_grades.php" class="flex items-center px-4 py-3 text-white bg-white/10 rounded-lg backdrop-blur-sm">
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

    <!-- Main Content -->
    <div class="ml-64 p-8">
        <!-- Header -->
        <div class="bg-gradient-to-r from-sky-500 to-sky-600 rounded-2xl p-8 text-white shadow-lg mb-8">
            <h1 class="text-3xl font-semibold mb-2">Manage Grades</h1>
            <p class="text-sky-100">Update and manage student grades for your subjects</p>
        </div>

        <!-- Subject Selector -->
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-8">
            <div class="flex flex-col md:flex-row gap-4 items-start md:items-center">
                <div class="flex-1">
                    <label for="subjectSelect" class="block text-sm font-medium text-gray-700 mb-2">Select Subject</label>
                    <select id="subjectSelect" 
                            onchange="loadStudentGrades()"
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <option value="">Choose a subject...</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['id']; ?>">
                                <?php echo $subject['subject_code'] . ' - ' . $subject['subject_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex-1">
                    <label for="gradeType" class="block text-sm font-medium text-gray-700 mb-2">Grade Type</label>
                    <select id="gradeType" 
                            onchange="loadStudentGrades()"
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <option value="all">All Grade Types</option>
                        <option value="quiz">Quizzes</option>
                        <option value="assignment">Assignments</option>
                        <option value="exam">Exams</option>
                        <option value="project">Projects</option>
                        <option value="final">Final</option>
                    </select>
                </div>
                <div class="flex-1">
                    <label for="studentSearch" class="block text-sm font-medium text-gray-700 mb-2">Search Students</label>
                    <input type="text" 
                           id="studentSearch" 
                           placeholder="Search by name or ID..." 
                           onkeyup="filterStudents()"
                           class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>
            </div>
        </div>

        <!-- Grade Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="text-3xl mb-3 text-sky-500">👥</div>
                <div class="text-2xl font-semibold text-sky-600 mb-1" id="totalStudents">0</div>
                <div class="text-gray-600">Total Students</div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="text-3xl mb-3 text-sky-500">📊</div>
                <div class="text-2xl font-semibold text-sky-600 mb-1" id="averageGrade">0%</div>
                <div class="text-gray-600">Average Grade</div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="text-3xl mb-3 text-sky-500">📈</div>
                <div class="text-2xl font-semibold text-sky-600 mb-1" id="highestGrade">0%</div>
                <div class="text-gray-600">Highest Grade</div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="text-3xl mb-3 text-sky-500">📉</div>
                <div class="text-2xl font-semibold text-sky-600 mb-1" id="lowestGrade">0%</div>
                <div class="text-gray-600">Lowest Grade</div>
            </div>
        </div>

        <!-- Grades Table -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-sky-50 to-white">
                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                    <span class="mr-2 text-sky-500">📝</span>
                    Student Grades
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Student ID</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Name</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Grade Type</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Score</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Date</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Status</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="gradesTableBody" class="divide-y divide-gray-100">
                        <!-- Will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Load student grades for selected subject
        function loadStudentGrades() {
            const subjectId = document.getElementById('subjectSelect').value;
            const gradeType = document.getElementById('gradeType').value;
            
            if (!subjectId) {
                document.getElementById('gradesTableBody').innerHTML = 
                    '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">Please select a subject</td></tr>';
                return;
            }

            fetch(`api/get_subject_grades.php?subject_id=${subjectId}&grade_type=${gradeType}`)
                .then(response => response.json())
                .then(data => {
                    const tableBody = document.getElementById('gradesTableBody');
                    
                    if (data.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No grades found</td></tr>';
                        return;
                    }

                    tableBody.innerHTML = data.map(grade => `
                        <tr class="hover:bg-sky-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-900">${grade.student_id}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">${grade.student_name}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">${grade.grade_type}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-3 py-1.5 rounded-lg ${getGradeColorClass(grade.score)}">
                                    ${grade.score}%
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">${grade.graded_at}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1.5 rounded-lg ${getStatusColorClass(grade.status)}">
                                    ${grade.status}
                                </span>
                            </td>
                            <td class="px-6 py-4 space-x-2">
                                <button onclick="editGrade(${grade.id})" 
                                        class="px-3 py-1.5 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 transition-colors">
                                    Edit
                                </button>
                                <button onclick="viewDetails(${grade.id})" 
                                        class="px-3 py-1.5 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 transition-colors">
                                    View
                                </button>
                            </td>
                        </tr>
                    `).join('');

                    // Update grade summary
                    updateGradeSummary(data);
                })
                .catch(error => {
                    console.error('Error loading grades:', error);
                    document.getElementById('gradesTableBody').innerHTML = 
                        '<tr><td colspan="7" class="px-6 py-4 text-center text-red-500">Error loading grades</td></tr>';
                });
        }

        // Update grade summary statistics
        function updateGradeSummary(grades) {
            // Count unique students by student_id
            const uniqueStudentIds = new Set(grades.map(g => g.student_id));
            const totalStudents = uniqueStudentIds.size;
            const scores = grades.filter(g => g.score !== null && !isNaN(g.score)).map(g => parseFloat(g.score));
            const average = scores.length > 0 ? (scores.reduce((a, b) => a + b, 0) / scores.length) : 0;
            const highest = scores.length > 0 ? Math.max(...scores) : 0;
            const lowest = scores.length > 0 ? Math.min(...scores) : 0;

            document.getElementById('totalStudents').textContent = totalStudents;
            document.getElementById('averageGrade').textContent = average.toFixed(1) + '%';
            document.getElementById('highestGrade').textContent = highest.toFixed(1) + '%';
            document.getElementById('lowestGrade').textContent = lowest.toFixed(1) + '%';
        }

        // Filter students based on search input
        function filterStudents() {
            const searchTerm = document.getElementById('studentSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#gradesTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
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

        // Helper function for status color classes
        function getStatusColorClass(status) {
            switch (status.toLowerCase()) {
                case 'completed':
                    return 'bg-green-100 text-green-800';
                case 'pending':
                    return 'bg-yellow-100 text-yellow-800';
                case 'in progress':
                    return 'bg-sky-100 text-sky-800';
                default:
                    return 'bg-gray-100 text-gray-800';
            }
        }

        // Edit grade function
        function editGrade(gradeId) {
            // Implementation for editing grade
            console.log('Editing grade:', gradeId);
            // Add your edit grade logic here
        }

        // View details function
        function viewDetails(gradeId) {
            // Implementation for viewing grade details
            console.log('Viewing details for grade:', gradeId);
            // Add your view details logic here
        }

        function showAddGradeModal() {
            // Implementation for showing add grade modal
        }

        function showEditGradeModal() {
            // Implementation for showing edit grade modal
        }

        function showDeleteGradeModal() {
            // Implementation for showing delete grade modal
        }

        function exportGrades() {
            // Implementation for exporting grades
        }

        function printGrades() {
            // Implementation for printing grades
        }
    </script>
</body>
</html> 