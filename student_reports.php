<?php
require_once 'auth_check.php';
require_once 'db.php';
requireTeacher();
$user = getCurrentUser();

// Get teacher's id
$stmt = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
if (!$teacher) die('Teacher not found.');
$teacher_id = $teacher['id'];

// Fetch latest report per student/subject for this teacher's subjects
$stmt = $conn->prepare("
    SELECT r1.id, r1.student_id, r1.subject_id, r1.report_type, r1.status, r1.submission_date, r1.content,
           s.subject_name, st.first_name, st.last_name
    FROM reports r1
    JOIN (
        SELECT student_id, subject_id, MAX(submission_date) as max_date
        FROM reports
        GROUP BY student_id, subject_id
    ) r2 ON r1.student_id = r2.student_id AND r1.subject_id = r2.subject_id AND r1.submission_date = r2.max_date
    JOIN subjects s ON r1.subject_id = s.id
    JOIN students st ON r1.student_id = st.id
    WHERE s.teacher_id = ?
    ORDER BY r1.submission_date DESC
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Reports - Academic Performance Monitoring</title>
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
                    <a href="manage_grades.php" class="flex items-center px-4 py-3 text-sky-100 hover:bg-white/10 rounded-lg transition-colors">
                        <span class="mr-3">📝</span>
                        Manage Grades
                    </a>
                </li>
                <li>
                    <a href="student_reports.php" class="flex items-center px-4 py-3 text-white bg-white/10 rounded-lg backdrop-blur-sm">
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
        <div class="bg-gradient-to-r from-sky-500 to-sky-600 rounded-2xl p-8 text-white shadow-lg mb-8 flex flex-col md:flex-row md:items-center md:justify-between">
            <h1 class="text-3xl font-semibold mb-2">Student Reports</h1>
        </div>
        <div class="bg-white rounded-2xl shadow-sm mb-8 overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-sky-50 to-white flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                    <span class="mr-2 text-sky-500">📄</span>
                    All Student Reports
                </h2>
                <div class="flex flex-col md:flex-row gap-2 md:gap-4 items-center">
                    <input type="text" id="reportSearch" placeholder="Search by student, subject, or type..." class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500" />
                    <select id="sortReports" class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <option value="date_desc">Newest First</option>
                        <option value="date_asc">Oldest First</option>
                        <option value="student_asc">Student Name (A-Z)</option>
                        <option value="student_desc">Student Name (Z-A)</option>
                        <option value="subject_asc">Subject (A-Z)</option>
                        <option value="subject_desc">Subject (Z-A)</option>
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full" id="reportsTable">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Student</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Subject</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Type</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Date</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Status</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100" id="reportsTableBody">
                        <?php foreach ($reports as $report): ?>
                        <tr class="hover:bg-sky-50/50 transition-colors" id="row-<?php echo $report['student_id'] . '-' . $report['subject_id']; ?>">
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($report['subject_name']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo ucfirst($report['report_type']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo date('Y-m-d', strtotime($report['submission_date'])); ?></td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium <?php echo $report['status'] === 'approved' ? 'bg-green-100 text-green-800' : ($report['status'] === 'pending' ? 'bg-sky-100 text-sky-800' : 'bg-red-100 text-red-800'); ?>">
                                    <?php echo ucfirst($report['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <button onclick="toggleHistory('<?php echo $report['student_id']; ?>','<?php echo htmlspecialchars($report['subject_name'], ENT_QUOTES); ?>', this)" class="px-3 py-1.5 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 transition-colors">History</button>
                                <button onclick="viewReport(<?php echo $report['id']; ?>)" class="px-3 py-1.5 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 transition-colors">View</button>
                            </td>
                        </tr>
                        <tr id="history-<?php echo $report['student_id'] . '-' . $report['subject_id']; ?>" class="hidden">
                            <td colspan="6" class="bg-sky-50 px-6 py-4">
                                <div class="text-center text-gray-500">Loading...</div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Modal for viewing report -->
        <div id="reportModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center overflow-y-auto z-50">
            <div class="bg-white rounded-2xl p-8 max-w-2xl w-full mx-4 my-8 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-6 sticky top-0 bg-white pb-4 border-b">
                    <h3 class="text-xl font-semibold text-gray-800">Report Details</h3>
                    <button onclick="closeReportModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="reportContent" class="space-y-4"></div>
                <div id="reportActions" class="flex justify-end space-x-4 mt-6"></div>
            </div>
        </div>
    </div>
    <script>
    function viewReport(reportId) {
        const modal = document.getElementById('reportModal');
        const content = document.getElementById('reportContent');
        const actions = document.getElementById('reportActions');
        content.innerHTML = '<div class="text-center text-gray-500">Loading...</div>';
        actions.innerHTML = '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        fetch(`api/get_report_details.php?id=${reportId}`)
            .then(response => response.json())
            .then(report => {
                content.innerHTML = `
                    <div><strong>Student:</strong> ${report.student_name}</div>
                    <div><strong>Subject:</strong> ${report.subject_name}</div>
                    <div><strong>Type:</strong> ${report.report_type}</div>
                    <div><strong>Date:</strong> ${report.submission_date}</div>
                    <div class="mt-4 whitespace-pre-line">${report.content}</div>
                `;
                if (report.status === 'pending') {
                    actions.innerHTML = `
                        <button onclick="approveReport(${report.id})" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">Approve</button>
                        <button onclick="rejectReport(${report.id})" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">Reject</button>
                    `;
                }
            })
            .catch(() => {
                content.innerHTML = '<div class="text-center text-red-500">Error loading report details.</div>';
            });
    }
    function closeReportModal() {
        const modal = document.getElementById('reportModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    function approveReport(reportId) {
        fetch(`api/approve_report.php?id=${reportId}`, { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                alert(data.message || 'Report approved');
                closeReportModal();
                location.reload();
            });
    }
    function rejectReport(reportId) {
        fetch(`api/reject_report.php?id=${reportId}`, { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                alert(data.message || 'Report rejected');
                closeReportModal();
                location.reload();
            });
    }
    function toggleHistory(studentId, subjectName, btn) {
        const rowId = `history-${studentId}-${subjectName.replace(/[^a-zA-Z0-9]/g, '')}`;
        let historyRow = document.getElementById(rowId);
        if (!historyRow) {
            // fallback for special chars
            historyRow = document.querySelector(`[id^='history-${studentId}-']`);
        }
        if (!historyRow) return;
        if (!historyRow.classList.contains('hidden')) {
            historyRow.classList.add('hidden');
            btn.textContent = 'History';
            return;
        }
        historyRow.classList.remove('hidden');
        btn.textContent = 'Hide';
        const td = historyRow.querySelector('td');
        td.innerHTML = '<div class="text-center text-gray-500">Loading...</div>';
        fetch(`api/get_report_history.php?student_id=${studentId}&subject_name=${encodeURIComponent(subjectName)}`)
            .then(response => response.json())
            .then(history => {
                if (!history.length) {
                    td.innerHTML = '<div class="text-center text-gray-500">No report history found.</div>';
                    return;
                }
                td.innerHTML = history.map(r => `
                    <div class="border-b pb-4 mb-4">
                        <div class="font-semibold">${r.report_type} (${r.status})</div>
                        <div class="text-sm text-gray-600">Submitted: ${r.submission_date}</div>
                        <div class="mt-2 whitespace-pre-line">${r.content}</div>
                    </div>
                `).join('');
            })
            .catch(() => {
                td.innerHTML = '<div class="text-center text-red-500">Error loading history.</div>';
            });
    }
    // Add search and sort functionality for reports
    const reports = <?php echo json_encode($reports); ?>;
    const tableBody = document.getElementById('reportsTableBody');
    const searchInput = document.getElementById('reportSearch');
    const sortSelect = document.getElementById('sortReports');

    function renderReports(filteredReports) {
        if (!filteredReports.length) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-gray-500">No reports found.</td></tr>';
            return;
        }
        tableBody.innerHTML = filteredReports.map(report => `
            <tr class="hover:bg-sky-50/50 transition-colors">
                <td class="px-6 py-4 text-sm text-gray-900">${report.first_name} ${report.last_name}</td>
                <td class="px-6 py-4 text-sm text-gray-900">${report.subject_name}</td>
                <td class="px-6 py-4 text-sm text-gray-600">${report.report_type.charAt(0).toUpperCase() + report.report_type.slice(1)}</td>
                <td class="px-6 py-4 text-sm text-gray-600">${report.submission_date.split('T')[0]}</td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium ${report.status === 'approved' ? 'bg-green-100 text-green-800' : (report.status === 'pending' ? 'bg-sky-100 text-sky-800' : 'bg-red-100 text-red-800')}">
                        ${report.status.charAt(0).toUpperCase() + report.status.slice(1)}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <button onclick="viewReport(${report.id})" class="px-3 py-1.5 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 transition-colors">View</button>
                </td>
            </tr>
        `).join('');
    }

    function filterAndSortReports() {
        let filtered = reports.filter(r => {
            const q = searchInput.value.toLowerCase();
            return (
                r.first_name.toLowerCase().includes(q) ||
                r.last_name.toLowerCase().includes(q) ||
                r.subject_name.toLowerCase().includes(q) ||
                r.report_type.toLowerCase().includes(q)
            );
        });
        switch (sortSelect.value) {
            case 'date_asc':
                filtered.sort((a, b) => new Date(a.submission_date) - new Date(b.submission_date));
                break;
            case 'date_desc':
                filtered.sort((a, b) => new Date(b.submission_date) - new Date(a.submission_date));
                break;
            case 'student_asc':
                filtered.sort((a, b) => (a.first_name + ' ' + a.last_name).localeCompare(b.first_name + ' ' + b.last_name));
                break;
            case 'student_desc':
                filtered.sort((a, b) => (b.first_name + ' ' + b.last_name).localeCompare(a.first_name + ' ' + a.last_name));
                break;
            case 'subject_asc':
                filtered.sort((a, b) => a.subject_name.localeCompare(b.subject_name));
                break;
            case 'subject_desc':
                filtered.sort((a, b) => b.subject_name.localeCompare(a.subject_name));
                break;
        }
        renderReports(filtered);
    }

    searchInput.addEventListener('input', filterAndSortReports);
    sortSelect.addEventListener('change', filterAndSortReports);

    document.addEventListener('DOMContentLoaded', filterAndSortReports);
    </script>
</body>
</html> 