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
    <title>Manage Subjects - Academic Performance Monitoring</title>
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
                    <a href="manage_subjects.php" class="flex items-center px-4 py-3 text-white bg-white/10 rounded-lg backdrop-blur-sm">
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
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-2xl p-8 text-white shadow-lg mb-8">
            <h1 class="text-3xl font-semibold mb-2">Manage Subjects</h1>
            <p class="text-indigo-100">Add, edit, or remove subjects from the system</p>
        </div>

        <!-- Action Buttons -->
        <div class="mb-8">
            <button onclick="showAddSubjectModal()" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                Add New Subject
            </button>
        </div>

        <!-- Subjects List -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-semibold text-gray-800">Subjects List</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Teacher</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Students</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200" id="subjectsList">
                        <!-- Will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Subject Modal -->
    <div id="subjectModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-2xl p-8 max-w-2xl w-full mx-4">
            <h3 id="modalTitle" class="text-2xl font-semibold text-gray-800 mb-6">Add New Subject</h3>
            <form id="subjectForm" onsubmit="handleSubjectSubmit(event)">
                <input type="hidden" id="subjectId" name="id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subject Code</label>
                        <input type="text" id="subjectCode" name="subject_code" required
                               class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subject Name</label>
                        <input type="text" id="subjectName" name="subject_name" required
                               class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea id="description" name="description" rows="3"
                                 class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Assigned Teacher</label>
                        <select id="teacherId" name="teacher_id"
                                class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Select Teacher</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="status" name="status" required
                                class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 mt-6">
                    <button type="button" onclick="closeSubjectModal()"
                            class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                        Save Subject
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Load subjects list
        function loadSubjects() {
            fetch('api/get_subjects.php')
                .then(response => response.json())
                .then(subjects => {
                    const tbody = document.getElementById('subjectsList');
                    tbody.innerHTML = subjects.map(subject => `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">${subject.subject_code}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${subject.subject_name}</td>
                            <td class="px-6 py-4">${subject.description || 'No description'}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${subject.teacher_name || 'Not assigned'}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${subject.student_count} students</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    ${subject.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${subject.status}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button onclick="editSubject(${subject.id})" 
                                        class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                <button onclick="deleteSubject(${subject.id})"
                                        class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    `).join('');
                })
                .catch(error => console.error('Error loading subjects:', error));
        }

        // Load teachers for subject assignment
        function loadTeachers() {
            fetch('api/get_teachers.php')
                .then(response => response.json())
                .then(teachers => {
                    const teacherSelect = document.getElementById('teacherId');
                    teacherSelect.innerHTML = '<option value="">Select Teacher</option>' +
                        teachers.map(teacher => `<option value="${teacher.id}">${teacher.full_name}</option>`).join('');
                })
                .catch(error => console.error('Error loading teachers:', error));
        }

        // Show add subject modal
        function showAddSubjectModal() {
            document.getElementById('modalTitle').textContent = 'Add New Subject';
            document.getElementById('subjectId').value = '';
            document.getElementById('subjectForm').reset();
            loadTeachers();
            
            const modal = document.getElementById('subjectModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // Close subject modal
        function closeSubjectModal() {
            const modal = document.getElementById('subjectModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        // Edit subject
        function editSubject(id) {
            fetch(`api/get_subject.php?id=${id}`)
                .then(response => response.json())
                .then(subject => {
                    document.getElementById('modalTitle').textContent = 'Edit Subject';
                    document.getElementById('subjectId').value = subject.id;
                    document.getElementById('subjectCode').value = subject.subject_code;
                    document.getElementById('subjectName').value = subject.subject_name;
                    document.getElementById('description').value = subject.description;
                    document.getElementById('teacherId').value = subject.teacher_id || '';
                    document.getElementById('status').value = subject.status;
                    
                    loadTeachers();
                    
                    const modal = document.getElementById('subjectModal');
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                })
                .catch(error => console.error('Error loading subject:', error));
        }

        // Delete subject
        function deleteSubject(id) {
            if (confirm('Are you sure you want to delete this subject? This action cannot be undone.')) {
                fetch('api/delete_subject.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ subject_id: id })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        loadSubjects();
                    } else {
                        alert(result.error || 'Failed to delete subject');
                    }
                })
                .catch(error => console.error('Error deleting subject:', error));
            }
        }

        // Handle subject form submission
        function handleSubjectSubmit(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const data = Object.fromEntries(formData.entries());
            
            const endpoint = data.id ? 'api/update_subject.php' : 'api/add_subject.php';
            
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    closeSubjectModal();
                    loadSubjects();
                } else {
                    alert(result.error || 'Failed to save subject');
                }
            })
            .catch(error => console.error('Error saving subject:', error));
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', () => {
            loadSubjects();
        });
    </script>
</body>
</html> 