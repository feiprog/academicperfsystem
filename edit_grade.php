<?php
require_once 'auth_check.php';
require_once 'db.php';

// Get grade ID from URL
$gradeId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$gradeId) {
    header('Location: manage_grades.php');
    exit;
}

// Verify that the grade belongs to a subject assigned to the logged-in teacher
$stmt = $conn->prepare("
    SELECT 
        g.*,
        s.student_id,
        CONCAT(s.first_name, ' ', s.last_name) as student_name,
        sub.subject_code,
        sub.subject_name
    FROM grades g
    JOIN students s ON g.student_id = s.id
    JOIN student_subjects ss ON s.id = ss.student_id
    JOIN subjects sub ON ss.subject_id = sub.id
    JOIN teachers t ON sub.teacher_id = t.id
    WHERE g.id = ? AND t.user_id = ?
");
$stmt->bind_param("ii", $gradeId, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: manage_grades.php');
    exit;
}

$grade = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Grade - Academic Performance Monitoring</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .edit-container {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .edit-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .info-box p {
            margin: 5px 0;
            color: #666;
        }
        .info-box strong {
            color: #333;
        }
        .button-group {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        .submit-btn {
            background: #2196F3;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .submit-btn:hover {
            background: #1976D2;
        }
        .cancel-btn {
            background: #f5f5f5;
            color: #333;
            padding: 10px 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
        }
        .cancel-btn:hover {
            background: #e0e0e0;
        }
        .error-message {
            color: #d32f2f;
            margin-top: 5px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2 class="Top-text">TEACHER DASHBOARD</h2>
        <img src="images/logo.png" alt="School Logo" class="logo">
        <h3 class="Bottom-text">PERFORMANCE MONITORING</h3>
        <ul>
            <li><a href="teacher_dashboard.php">📊 Dashboard</a></li>
            <li class="active"><a href="manage_grades.php">📝 Manage Grades</a></li>
            <li><a href="student_reports.php">📄 Student Reports</a></li>
            <li><a href="logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="edit-container">
            <h2>Edit Grade</h2>
            
            <!-- Student and Subject Info -->
            <div class="info-box">
                <p><strong>Student:</strong> <?php echo htmlspecialchars($grade['student_name']); ?> (<?php echo htmlspecialchars($grade['student_id']); ?>)</p>
                <p><strong>Subject:</strong> <?php echo htmlspecialchars($grade['subject_code'] . ' - ' . $grade['subject_name']); ?></p>
                <p><strong>Last Updated:</strong> <?php echo date('F j, Y g:i A', strtotime($grade['graded_at'])); ?></p>
            </div>

            <!-- Edit Form -->
            <form id="editGradeForm" class="edit-form" onsubmit="return updateGrade(event)">
                <input type="hidden" name="grade_id" value="<?php echo $gradeId; ?>">
                
                <div class="form-group">
                    <label for="grade_type">Grade Type</label>
                    <select id="grade_type" name="grade_type" required>
                        <option value="quiz" <?php echo $grade['grade_type'] === 'quiz' ? 'selected' : ''; ?>>Quiz</option>
                        <option value="assignment" <?php echo $grade['grade_type'] === 'assignment' ? 'selected' : ''; ?>>Assignment</option>
                        <option value="exam" <?php echo $grade['grade_type'] === 'exam' ? 'selected' : ''; ?>>Exam</option>
                        <option value="project" <?php echo $grade['grade_type'] === 'project' ? 'selected' : ''; ?>>Project</option>
                        <option value="final" <?php echo $grade['grade_type'] === 'final' ? 'selected' : ''; ?>>Final</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="score">Score (%)</label>
                    <input type="number" id="score" name="score" min="0" max="100" step="0.1" 
                           value="<?php echo htmlspecialchars($grade['score']); ?>" required>
                    <div id="scoreError" class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="comments">Comments</label>
                    <textarea id="comments" name="comments"><?php echo htmlspecialchars($grade['comments'] ?? ''); ?></textarea>
                </div>

                <div class="button-group">
                    <button type="submit" class="submit-btn">Update Grade</button>
                    <a href="manage_grades.php" class="cancel-btn">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateGrade(event) {
            event.preventDefault();
            
            // Clear previous error
            document.getElementById('scoreError').textContent = '';
            
            // Validate score
            const score = parseFloat(document.getElementById('score').value);
            if (score < 0 || score > 100) {
                document.getElementById('scoreError').textContent = 'Score must be between 0 and 100';
                return false;
            }

            // Get form data
            const formData = new FormData(event.target);
            
            // Send update request
            fetch('api/update_grade.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Grade updated successfully!');
                    window.location.href = 'manage_grades.php';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the grade.');
            });

            return false;
        }
    </script>
</body>
</html> 