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
                <div class="flex-none pt-8">
                    <button onclick="showAddGradeModal()" 
                            class="px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition-colors">
                        ➕ Add Grade
                    </button>
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
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Student Information</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Grade Breakdown</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Overall Grade</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="gradesTableBody" class="divide-y divide-gray-100">
                        <!-- Will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Edit Grade Modal -->
        <div id="editGradeModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm hidden">
            <div class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white rounded-2xl shadow-xl w-full max-w-lg">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-xl font-semibold text-gray-800">Edit Grade</h3>
                </div>
                <form id="editGradeForm" class="p-6 space-y-4">
                    <input type="hidden" id="editGradeId" name="grade_id">
                    
                    <div>
                        <label for="editStudentInfo" class="block text-sm font-medium text-gray-700 mb-1">Student</label>
                        <div id="editStudentInfo" class="px-4 py-2.5 bg-gray-50 rounded-lg text-gray-700"></div>
                    </div>

                    <div>
                        <label for="editGradeType" class="block text-sm font-medium text-gray-700 mb-1">Grade Type</label>
                        <div id="editGradeType" class="px-4 py-2.5 bg-gray-50 rounded-lg text-gray-700"></div>
                    </div>

                    <div>
                        <label for="editScore" class="block text-sm font-medium text-gray-700 mb-1">Score</label>
                        <input type="number" 
                               id="editScore" 
                               name="score" 
                               min="0" 
                               max="100" 
                               step="0.01" 
                               required
                               class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>

                    <div>
                        <label for="editRemarks" class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                        <textarea id="editRemarks" 
                                  name="remarks" 
                                  rows="3"
                                  class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"></textarea>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" 
                                onclick="closeEditModal()"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 transition-colors">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Grade Modal -->
        <div id="addGradeModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm hidden">
            <div class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
                <!-- Fixed Header -->
                <div class="p-6 border-b border-gray-100 flex-none">
                    <h3 class="text-xl font-semibold text-gray-800">Add Student Grades</h3>
                </div>
                
                <!-- Scrollable Form Content -->
                <div class="overflow-y-auto flex-1">
                    <form id="addGradeForm" class="p-6">
                        <input type="hidden" id="addSubjectId" name="subject_id">
                        
                        <div class="mb-6">
                            <label for="addStudentId" class="block text-sm font-medium text-gray-700 mb-1">Student</label>
                            <select id="addStudentId" 
                                    name="student_id" 
                                    required
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                                <option value="">Select student...</option>
                            </select>
                        </div>

                        <!-- Written Works Section -->
                        <div class="mb-8">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4">Written Works (30%)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Assignments -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Assignments (10%)</label>
                                    <input type="number" 
                                           name="written_assignment" 
                                           min="0" 
                                           max="100" 
                                           step="0.01"
                                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                           placeholder="Score (0-100)">
                                </div>
                                <!-- Activities -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Activities (10%)</label>
                                    <input type="number" 
                                           name="written_activity" 
                                           min="0" 
                                           max="100" 
                                           step="0.01"
                                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                           placeholder="Score (0-100)">
                                </div>
                                <!-- Quizzes -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Quizzes (10%)</label>
                                    <input type="number" 
                                           name="written_quiz" 
                                           min="0" 
                                           max="100" 
                                           step="0.01"
                                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                           placeholder="Score (0-100)">
                                </div>
                            </div>
                        </div>

                        <!-- Performance Section -->
                        <div class="mb-8">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4">Performance (20%)</h4>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Attendance</label>
                                <input type="number" 
                                       name="performance_attendance" 
                                       min="0" 
                                       max="100" 
                                       step="0.01"
                                       class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                       placeholder="Score (0-100)">
                            </div>
                        </div>

                        <!-- Examinations Section -->
                        <div class="mb-8">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4">Examinations (50%)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Prelim -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Preliminary Exam (10%)</label>
                                    <input type="number" 
                                           name="exam_prelim" 
                                           min="0" 
                                           max="100" 
                                           step="0.01"
                                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                           placeholder="Score (0-100)">
                                </div>
                                <!-- Midterm -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Midterm Exam (15%)</label>
                                    <input type="number" 
                                           name="exam_midterm" 
                                           min="0" 
                                           max="100" 
                                           step="0.01"
                                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                           placeholder="Score (0-100)">
                                </div>
                                <!-- Semi-Finals -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Semi-Final Exam (10%)</label>
                                    <input type="number" 
                                           name="exam_semi_final" 
                                           min="0" 
                                           max="100" 
                                           step="0.01"
                                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                           placeholder="Score (0-100)">
                                </div>
                                <!-- Finals -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Final Exam (15%)</label>
                                    <input type="number" 
                                           name="exam_final" 
                                           min="0" 
                                           max="100" 
                                           step="0.01"
                                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                           placeholder="Score (0-100)">
                                </div>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="mb-6">
                            <label for="addRemarks" class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                            <textarea id="addRemarks" 
                                      name="remarks" 
                                      rows="3"
                                      class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                      placeholder="Add any additional comments or remarks"></textarea>
                        </div>
                    </form>
                </div>

                <!-- Fixed Footer -->
                <div class="p-6 border-t border-gray-100 flex-none bg-white">
                    <div class="flex justify-between items-center">
                        <!-- Grade Summary -->
                        <div class="text-sm">
                            <p class="font-medium text-gray-700">Total Grade: <span id="totalGrade">0</span>%</p>
                            <p class="text-gray-500 mt-1">
                                Written (30%) + Performance (20%) + Exams (50%)
                            </p>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="space-x-3">
                            <button type="button" 
                                    onclick="closeAddModal()"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                    form="addGradeForm"
                                    class="px-4 py-2 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 transition-colors">
                                Save All Grades
                            </button>
                        </div>
                    </div>
                </div>
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
                    '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">Please select a subject</td></tr>';
                return;
            }

            fetch(`api/get_subject_grades.php?subject_id=${subjectId}&grade_type=${gradeType}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    const tableBody = document.getElementById('gradesTableBody');
                    
                    if (!Array.isArray(data)) {
                        throw new Error('Invalid data format received');
                    }
                    
                    if (data.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">No grades found</td></tr>';
                        return;
                    }

                    tableBody.innerHTML = data.map(student => {
                        const writtenGrades = Array.isArray(student.grades.written) ? student.grades.written.map(grade => `
                            <div class="mb-1">
                                <span class="font-medium">${grade.grade_type}:</span>
                                <span class="px-2 py-0.5 rounded-lg ${getGradeColorClass(grade.score)}">${grade.score}%</span>
                                <span class="text-gray-500 text-sm">(${grade.graded_at})</span>
                            </div>
                        `).join('') : '';

                        const performanceGrades = Array.isArray(student.grades.performance) ? student.grades.performance.map(grade => `
                            <div class="mb-1">
                                <span class="font-medium">${grade.grade_type}:</span>
                                <span class="px-2 py-0.5 rounded-lg ${getGradeColorClass(grade.score)}">${grade.score}%</span>
                                <span class="text-gray-500 text-sm">(${grade.graded_at})</span>
                            </div>
                        `).join('') : '';

                        const examGrades = Array.isArray(student.grades.exams) ? student.grades.exams.map(grade => `
                            <div class="mb-1">
                                <span class="font-medium">${grade.grade_type}:</span>
                                <span class="px-2 py-0.5 rounded-lg ${getGradeColorClass(grade.score)}">${grade.score}%</span>
                                <span class="text-gray-500 text-sm">(${grade.graded_at})</span>
                            </div>
                        `).join('') : '';

                        return `
                            <tr class="hover:bg-sky-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">${student.student_id}</div>
                                    <div class="text-gray-500">${student.student_name}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-2">
                                        ${writtenGrades ? `
                                            <div class="mb-2">
                                                <h4 class="text-sm font-semibold text-gray-700">Written Works (30%)</h4>
                                                ${writtenGrades}
                                            </div>
                                        ` : ''}
                                        ${performanceGrades ? `
                                            <div class="mb-2">
                                                <h4 class="text-sm font-semibold text-gray-700">Performance (20%)</h4>
                                                ${performanceGrades}
                                            </div>
                                        ` : ''}
                                        ${examGrades ? `
                                            <div>
                                                <h4 class="text-sm font-semibold text-gray-700">Examinations (50%)</h4>
                                                ${examGrades}
                                            </div>
                                        ` : ''}
                                        ${!writtenGrades && !performanceGrades && !examGrades ? `
                                            <div class="text-gray-500 italic">No grades recorded yet</div>
                                        ` : ''}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-2xl font-semibold ${getGradeColorClass(student.overall_grade)}">
                                        ${student.overall_grade}%
                                    </div>
                                    <div class="mt-1 text-sm">
                                        <span class="px-2 py-0.5 rounded-lg ${getStatusColorClass(student.status)}">
                                            ${student.status}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <button onclick="showAddGradeModal('${student.student_id}')" 
                                            class="px-3 py-1.5 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 transition-colors">
                                        Add Grade
                                    </button>
                                </td>
                            </tr>
                        `;
                    }).join('');

                    // Update grade summary
                    updateGradeSummary(data);
                })
                .catch(error => {
                    console.error('Error loading grades:', error);
                    document.getElementById('gradesTableBody').innerHTML = 
                        '<tr><td colspan="4" class="px-6 py-4 text-center text-red-500">Error loading grades</td></tr>';
                });
        }

        // Update grade summary function
        function updateGradeSummary(data) {
            if (!Array.isArray(data)) return;
            
            const totalStudents = data.length;
            const scores = data.map(student => parseFloat(student.overall_grade) || 0);
            const average = scores.length > 0 ? scores.reduce((a, b) => a + b, 0) / scores.length : 0;
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
            const row = document.querySelector(`tr[data-grade-id="${gradeId}"]`);
            if (!row) return;

            // Get grade data from the row
            const studentId = row.querySelector('td:nth-child(1)').textContent;
            const studentName = row.querySelector('td:nth-child(2)').textContent;
            const gradeType = row.querySelector('td:nth-child(3)').textContent;
            const score = parseFloat(row.querySelector('td:nth-child(4) span').textContent);
            const remarks = row.querySelector('td[data-remarks]')?.dataset.remarks || '';

            // Populate modal
            document.getElementById('editGradeId').value = gradeId;
            document.getElementById('editStudentInfo').textContent = `${studentId} - ${studentName}`;
            document.getElementById('editGradeType').textContent = gradeType;
            document.getElementById('editScore').value = score;
            document.getElementById('editRemarks').value = remarks;

            // Show modal
            document.getElementById('editGradeModal').classList.remove('hidden');
        }

        // Close edit modal
        function closeEditModal() {
            document.getElementById('editGradeModal').classList.add('hidden');
        }

        // Handle edit form submission
        document.getElementById('editGradeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form values
            const gradeId = document.getElementById('editGradeId').value;
            const score = parseFloat(document.getElementById('editScore').value);
            const remarks = document.getElementById('editRemarks').value;

            // Validate input
            if (!gradeId || isNaN(score) || score < 0 || score > 100) {
                alert('Please enter a valid score between 0 and 100');
                return;
            }

            // Create FormData object with validated data
            const formData = new FormData();
            formData.append('grade_id', gradeId);
            formData.append('score', score);
            formData.append('remarks', remarks);
            
            fetch('api/update_grade.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the table row
                    const row = document.querySelector(`tr[data-grade-id="${gradeId}"]`);
                    if (row) {
                        row.querySelector('td:nth-child(4) span').textContent = score + '%';
                        row.querySelector('td:nth-child(4) span').className = `px-3 py-1.5 rounded-lg ${getGradeColorClass(score)}`;
                        if (row.querySelector('td[data-remarks]')) {
                            row.querySelector('td[data-remarks]').dataset.remarks = remarks;
                        }
                    }
                    
                    // Close modal
                    closeEditModal();
                    
                    // Show success message
                    alert('Grade updated successfully!');
                    
                    // Refresh grade summary
                    loadStudentGrades();
                } else {
                    alert(data.error || 'Failed to update grade');
                }
            })
            .catch(error => {
                console.error('Error updating grade:', error);
                alert('An error occurred while updating the grade');
            });
        });

        // View details function
        function viewDetails(gradeId) {
            // Implementation for viewing grade details
            console.log('Viewing details for grade:', gradeId);
            // Add your view details logic here
        }

        // Show add grade modal
        function showAddGradeModal() {
            const subjectId = document.getElementById('subjectSelect').value;
            if (!subjectId) {
                alert('Please select a subject first');
                return;
            }

            document.getElementById('addSubjectId').value = subjectId;
            loadStudentsForSubject(subjectId);
            document.getElementById('addGradeModal').classList.remove('hidden');
        }

        // Close add grade modal
        function closeAddModal() {
            document.getElementById('addGradeModal').classList.add('hidden');
            document.getElementById('addGradeForm').reset();
        }

        // Load students for the selected subject
        function loadStudentsForSubject(subjectId) {
            fetch(`api/get_subject_students.php?subject_id=${subjectId}`)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('addStudentId');
                    select.innerHTML = '<option value="">Select student...</option>';
                    
                    data.forEach(student => {
                        const option = document.createElement('option');
                        option.value = student.id;
                        option.textContent = `${student.student_id} - ${student.name}`;
                        select.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error loading students:', error);
                    alert('Failed to load students');
                });
        }

        // Calculate total grade based on component weights
        function calculateTotalGrade() {
            const weights = {
                written_assignment: 0.10,
                written_activity: 0.10,
                written_quiz: 0.10,
                performance_attendance: 0.20,
                exam_prelim: 0.10,
                exam_midterm: 0.15,
                exam_semi_final: 0.10,
                exam_final: 0.15
            };

            let total = 0;
            let form = document.getElementById('addGradeForm');

            for (const [field, weight] of Object.entries(weights)) {
                const input = form[field];
                if (input && input.value) {
                    total += parseFloat(input.value) * weight;
                }
            }

            document.getElementById('totalGrade').textContent = total.toFixed(1);
        }

        // Add input event listeners for grade calculation
        document.getElementById('addGradeForm').querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('input', calculateTotalGrade);
        });

        // Handle add grade form submission
        document.getElementById('addGradeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('api/add_grade.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal
                    closeAddModal();
                    
                    // Show success message
                    alert('Grade added successfully!');
                    
                    // Refresh grade list
                    loadStudentGrades();
                } else {
                    alert(data.error || 'Failed to add grade');
                }
            })
            .catch(error => {
                console.error('Error adding grade:', error);
                alert('An error occurred while adding the grade');
            });
        });
    </script>
</body>
</html> 