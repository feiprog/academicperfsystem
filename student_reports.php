<?php
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Reports</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; }
        .container { max-width: 1000px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        h1 { margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px 12px; border-bottom: 1px solid #e0e0e0; text-align: left; }
        th { background: #f8f8f8; }
        tr:last-child td { border-bottom: none; }
        .no-data { color: #888; text-align: center; padding: 40px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Student Reports</h1>
        <table id="reportsTable">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Subject</th>
                    <th>Report Content</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody id="reportsBody">
                <tr><td colspan="4" class="no-data">Loading...</td></tr>
            </tbody>
        </table>
    </div>
    <script>
    function escapeHtml(text) {
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    fetch('api/get_teacher_reports.php')
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById('reportsBody');
            tbody.innerHTML = '';
            if (!data.length) {
                tbody.innerHTML = '<tr><td colspan="4" class="no-data">No reports found.</td></tr>';
                return;
            }
            data.forEach(report => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${escapeHtml(report.first_name + ' ' + report.last_name)}</td>
                    <td>${escapeHtml(report.subject_name)}</td>
                    <td>${escapeHtml(report.report_content || '')}</td>
                    <td>${escapeHtml(report.created_at || '')}</td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(() => {
            document.getElementById('reportsBody').innerHTML = '<tr><td colspan="4" class="no-data">Failed to load reports.</td></tr>';
        });
    </script>
</body>
</html> 