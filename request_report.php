<?php
require_once 'auth_check.php';
requireStudent();
$user = getCurrentUser();

// Get student's enrolled subjects with teacher info
$stmt = $conn->prepare("
    SELECT 
        s.id as subject_id,
        s.subject_code,
        s.subject_name,
        tu.full_name as teacher_name,
        tu.email as teacher_email
    FROM subjects s
    JOIN student_subjects ss ON s.id = ss.subject_id
    JOIN students st ON ss.student_id = st.id
    JOIN users u ON st.user_id = u.id
    LEFT JOIN teachers t ON s.teacher_id = t.id
    LEFT JOIN users tu ON t.user_id = tu.id
    WHERE u.id = ? AND ss.status = 'active'
    ORDER BY s.subject_name
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent report requests
$stmt = $conn->prepare("
    SELECT 
        rr.id,
        rr.request_type,
        rr.request_reason,
        rr.status,
        rr.request_date,
        rr.response_date,
        rr.response_notes,
        s.subject_code,
        s.subject_name,
        tu.full_name as teacher_name
    FROM report_requests rr
    JOIN subjects s ON rr.subject_id = s.id
    JOIN students st ON rr.student_id = st.id
    JOIN users u ON st.user_id = u.id
    LEFT JOIN teachers t ON rr.response_by = t.id
    LEFT JOIN users tu ON t.user_id = tu.id
    WHERE u.id = ?
    ORDER BY rr.request_date DESC
    LIMIT 10
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$recentRequests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Performance Report - Academic Performance Monitoring</title>
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
                    <a href="student_dashboard.php" class="flex items-center px-4 py-3 text-sky-100 hover:bg-white/10 rounded-lg transition-colors">
                        <span class="mr-3">📊</span>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="request_report.php" class="flex items-center px-4 py-3 text-white bg-white/10 rounded-lg backdrop-blur-sm">
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
        <!-- Header -->
        <div class="bg-gradient-to-r from-sky-500 to-sky-600 rounded-2xl p-8 text-white shadow-lg mb-8">
            <h1 class="text-3xl font-semibold mb-2">Request Performance Report</h1>
            <p class="text-sky-100">Submit a request for a performance report from your subject instructors</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Request Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-sky-50 to-white">
                        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                            <span class="mr-2 text-sky-500">📝</span>
                            New Report Request
                        </h2>
                    </div>
                    <div class="p-6">
                        <form id="reportRequestForm" class="space-y-6">
                            <div>
                                <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Select Subject</label>
                                <select id="subject" name="subject_id" required
                                        class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                                    <option value="">Choose a subject...</option>
                                    <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['subject_id']; ?>">
                                        <?php echo htmlspecialchars($subject['subject_name'] . ' (' . $subject['subject_code'] . ')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="requestType" class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                                <select id="requestType" name="request_type" required
                                        class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                                    <option value="">Select report type...</option>
                                    <option value="progress">Progress Report</option>
                                    <option value="midterm">Midterm Report</option>
                                    <option value="final">Final Report</option>
                                    <option value="special">Special Request</option>
                                </select>
                            </div>

                            <div>
                                <label for="requestReason" class="block text-sm font-medium text-gray-700 mb-1">Reason for Request</label>
                                <textarea id="requestReason" name="request_reason" rows="4" required
                                          placeholder="Please provide a brief explanation for your request..."
                                          class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"></textarea>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit"
                                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 transition-colors shadow-sm hover:shadow">
                                    📨 Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Recent Requests -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-sky-50 to-white">
                        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                            <span class="mr-2 text-sky-500">📋</span>
                            Recent Requests
                        </h2>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <?php if (empty($recentRequests)): ?>
                            <div class="p-6 text-center text-gray-500">
                                No recent requests
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentRequests as $request): ?>
                                <div class="p-4 hover:bg-sky-50/50 transition-colors">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="font-medium text-gray-900">
                                            <?php echo htmlspecialchars($request['subject_name']); ?>
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium <?php echo getStatusColorClass($request['status']); ?>">
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600 mb-2">
                                        <?php echo ucfirst($request['request_type']); ?> Report
                                    </div>
                                    <?php if ($request['response_notes']): ?>
                                        <div class="text-sm text-gray-600 bg-gray-50 p-2 rounded-lg">
                                            <?php echo htmlspecialchars($request['response_notes']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="text-xs text-gray-500 mt-2">
                                        Requested: <?php echo date('M d, Y', strtotime($request['request_date'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('reportRequestForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const form = e.target;
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '⏳ Submitting...';

            try {
                const formData = new FormData(form);
                const response = await fetch('api/submit_report_request.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (response.ok) {
                    // Show success message
                    alert('Report request submitted successfully!');
                    // Reset form
                    form.reset();
                    // Reload page to show updated recent requests
                    window.location.reload();
                } else {
                    throw new Error(result.message || 'Failed to submit request');
                }
            } catch (error) {
                alert(error.message);
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = '📨 Submit Request';
            }
        });
    </script>

    <?php
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