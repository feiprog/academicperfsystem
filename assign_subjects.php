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

// Get all teachers
$stmt = $conn->prepare("
    SELECT 
        t.id,
        t.teacher_id,
        u.full_name,
        t.department
    FROM teachers t
    JOIN users u ON t.user_id = u.id
    ORDER BY u.full_name
");
$stmt->execute();
$teachers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all subjects
$stmt = $conn->prepare("
    SELECT 
        id,
        subject_code,
        subject_name,
        teacher_id
    FROM subjects
    ORDER BY subject_code
");
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Subjects - Academic Performance Monitoring</title>
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
                    <a href="assign_subjects.php" class="flex items-center px-4 py-3 text-white bg-white/10 rounded-lg backdrop-blur-sm">
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
            <h1 class="text-3xl font-semibold mb-2">Assign Subjects</h1>
            <p class="text-indigo-100">Assign or reassign subjects to teachers</p>
        </div>

        <!-- Teachers List -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Teachers Panel -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-xl font-semibold text-gray-800">Teachers</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach ($teachers as $teacher): ?>
                        <div class="p-4 border border-gray-200 rounded-lg hover:border-indigo-500 cursor-pointer transition-colors"
                             onclick="selectTeacher(<?php echo $teacher['id']; ?>)"
                             id="teacher-<?php echo $teacher['id']; ?>">
                            <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($teacher['full_name']); ?></h3>
                            <div class="text-sm text-gray-500 mt-1">
                                <p>Teacher ID: <?php echo htmlspecialchars($teacher['teacher_id']); ?></p>
                                <p>Department: <?php echo htmlspecialchars($teacher['department']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Subjects Panel -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-xl font-semibold text-gray-800">Subjects</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach ($subjects as $subject): ?>
                        <div class="p-4 border border-gray-200 rounded-lg <?php echo $subject['teacher_id'] ? 'bg-gray-50' : ''; ?>"
                             id="subject-<?php echo $subject['id']; ?>">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-medium text-gray-900">
                                        <?php echo htmlspecialchars($subject['subject_code']); ?> - 
                                        <?php echo htmlspecialchars($subject['subject_name']); ?>
                                    </h3>
                                    <p class="text-sm text-gray-500 mt-1" id="subject-teacher-<?php echo $subject['id']; ?>">
                                        <?php 
                                        if ($subject['teacher_id']) {
                                            foreach ($teachers as $teacher) {
                                                if ($teacher['id'] == $subject['teacher_id']) {
                                                    echo 'Assigned to: ' . htmlspecialchars($teacher['full_name']);
                                                    break;
                                                }
                                            }
                                        } else {
                                            echo 'Not assigned';
                                        }
                                        ?>
                                    </p>
                                </div>
                                <button onclick="assignSubject(<?php echo $subject['id']; ?>)"
                                        class="px-3 py-1 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700 transition-colors"
                                        id="assign-btn-<?php echo $subject['id']; ?>">
                                    Assign
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let selectedTeacherId = null;

        function selectTeacher(teacherId) {
            // Remove previous selection
            if (selectedTeacherId) {
                document.getElementById(`teacher-${selectedTeacherId}`).classList.remove('border-indigo-500', 'bg-indigo-50');
            }
            
            // Add new selection
            selectedTeacherId = teacherId;
            document.getElementById(`teacher-${teacherId}`).classList.add('border-indigo-500', 'bg-indigo-50');
        }

        function assignSubject(subjectId) {
            if (!selectedTeacherId) {
                alert('Please select a teacher first');
                return;
            }

            fetch('api/assign_subject.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    subject_id: subjectId,
                    teacher_id: selectedTeacherId
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Update the UI to show the new assignment
                    const teacherName = document.getElementById(`teacher-${selectedTeacherId}`)
                        .querySelector('h3').textContent;
                    document.getElementById(`subject-teacher-${subjectId}`).textContent = `Assigned to: ${teacherName}`;
                    document.getElementById(`subject-${subjectId}`).classList.add('bg-gray-50');
                } else {
                    alert(result.error || 'Failed to assign subject');
                }
            })
            .catch(error => console.error('Error assigning subject:', error));
        }
    </script>
</body>
</html> 