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
    <title>Manage Teachers - Academic Performance Monitoring</title>
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
                    <a href="manage_teachers.php" class="flex items-center px-4 py-3 text-white bg-white/10 rounded-lg backdrop-blur-sm">
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
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-2xl p-8 text-white shadow-lg mb-8">
            <h1 class="text-3xl font-semibold mb-2">Manage Teachers</h1>
            <p class="text-indigo-100">Add, edit, or remove teachers from the system</p>
        </div>

        <!-- Action Buttons -->
        <div class="mb-8">
            <button onclick="showAddTeacherModal()" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                Add New Teacher
            </button>
        </div>

        <!-- Teachers List -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-semibold text-gray-800">Teachers List</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teacher ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subjects</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200" id="teachersList">
                        <!-- Will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Teacher Modal -->
    <div id="teacherModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-2xl p-8 max-w-2xl w-full mx-4">
            <h3 id="modalTitle" class="text-2xl font-semibold text-gray-800 mb-6">Add New Teacher</h3>
            <form id="teacherForm" onsubmit="handleTeacherSubmit(event)">
                <input type="hidden" id="teacherId" name="id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Teacher ID</label>
                        <input type="text" id="teacherIdInput" name="teacher_id" required
                               class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <input type="text" id="fullName" name="full_name" required
                               class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                        <input type="text" id="department" name="department" required
                               class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
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
                    <button type="button" onclick="closeTeacherModal()"
                            class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                        Save Teacher
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Load teachers list
        function loadTeachers() {
            fetch('api/get_teachers.php')
                .then(response => response.json())
                .then(teachers => {
                    const tbody = document.getElementById('teachersList');
                    tbody.innerHTML = teachers.map(teacher => `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">${teacher.teacher_id}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${teacher.full_name}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${teacher.email}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${teacher.department}</td>
                            <td class="px-6 py-4">${teacher.subjects || 'No subjects assigned'}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    ${teacher.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${teacher.status}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button onclick="editTeacher(${teacher.id})" 
                                        class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                <button onclick="deleteTeacher(${teacher.id})"
                                        class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    `).join('');
                })
                .catch(error => console.error('Error loading teachers:', error));
        }

        // Show add teacher modal
        function showAddTeacherModal() {
            document.getElementById('modalTitle').textContent = 'Add New Teacher';
            document.getElementById('teacherId').value = '';
            document.getElementById('teacherForm').reset();
            
            const modal = document.getElementById('teacherModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // Close teacher modal
        function closeTeacherModal() {
            const modal = document.getElementById('teacherModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        // Edit teacher
        function editTeacher(id) {
            fetch(`api/get_teacher.php?id=${id}`)
                .then(response => response.json())
                .then(teacher => {
                    document.getElementById('modalTitle').textContent = 'Edit Teacher';
                    document.getElementById('teacherId').value = teacher.id;
                    document.getElementById('teacherIdInput').value = teacher.teacher_id;
                    document.getElementById('fullName').value = teacher.full_name;
                    document.getElementById('email').value = teacher.email;
                    document.getElementById('department').value = teacher.department;
                    document.getElementById('status').value = teacher.status;
                    
                    const modal = document.getElementById('teacherModal');
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                })
                .catch(error => console.error('Error loading teacher:', error));
        }

        // Delete teacher
        function deleteTeacher(id) {
            if (confirm('Are you sure you want to delete this teacher? This action cannot be undone.')) {
                fetch('api/delete_teacher.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ teacher_id: id })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        loadTeachers();
                    } else {
                        alert(result.error || 'Failed to delete teacher');
                    }
                })
                .catch(error => console.error('Error deleting teacher:', error));
            }
        }

        // Handle teacher form submission
        function handleTeacherSubmit(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const data = Object.fromEntries(formData.entries());
            
            const endpoint = data.id ? 'api/update_teacher.php' : 'api/add_teacher.php';
            
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
                    closeTeacherModal();
                    loadTeachers();
                } else {
                    alert(result.error || 'Failed to save teacher');
                }
            })
            .catch(error => console.error('Error saving teacher:', error));
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', () => {
            loadTeachers();
        });
    </script>
</body>
</html> 