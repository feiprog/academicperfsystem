<?php
require_once 'auth_check.php';
requireAdmin(); // Only administrators can enroll students

// Get current academic term
$currentMonth = date('n');
$currentYear = date('Y');
$currentSemester = ($currentMonth >= 6 && $currentMonth <= 10) ? 'First Semester' : 'Second Semester';
$academicYear = ($currentMonth >= 6) ? $currentYear . '-' . ($currentYear + 1) : ($currentYear - 1) . '-' . $currentYear;

// Get all students with their current semester
$stmt = $conn->prepare("
    SELECT 
        s.id,
        s.student_id,
        CONCAT(s.first_name, ' ', s.last_name) as full_name,
        s.year_level,
        s.semester,
        s.academic_year
    FROM students s
    ORDER BY s.last_name, s.first_name
");
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get subjects for the current semester only
$stmt = $conn->prepare("
    SELECT 
        s.id,
        s.subject_code,
        s.subject_name,
        CONCAT(tu.full_name, ' (', s.subject_code, ')') as teacher_name,
        c.semester,
        c.year_level,
        c.degree_program
    FROM subjects s
    JOIN curriculum c ON s.id = c.subject_id
    LEFT JOIN teachers t ON s.teacher_id = t.id
    LEFT JOIN users tu ON t.user_id = tu.id
    WHERE c.semester = ?
    ORDER BY s.subject_code
");
$stmt->bind_param("s", $currentSemester);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle enrollment submission with semester validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? null;
    $subject_ids = $_POST['subject_ids'] ?? [];
    $enrollment_date = date('Y-m-d');

    if ($student_id && !empty($subject_ids)) {
        try {
            // Get student's current semester and year level
            $stmt = $conn->prepare("SELECT semester, year_level, degree_program FROM students WHERE id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $student_info = $stmt->get_result()->fetch_assoc();

            if ($student_info['semester'] !== $currentSemester) {
                throw new Exception("Cannot enroll student in subjects for a different semester.");
            }

            $conn->begin_transaction();

            // First, mark all existing enrollments as 'dropped'
            $stmt = $conn->prepare("
                UPDATE student_subjects 
                SET status = 'dropped' 
                WHERE student_id = ?
            ");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();

            // Validate and insert new enrollments
            $stmt = $conn->prepare("
                INSERT INTO student_subjects (student_id, subject_id, enrollment_date, status)
                SELECT ?, ?, ?, 'active'
                FROM curriculum c
                WHERE c.subject_id = ?
                AND c.semester = ?
                AND c.year_level = ?
                AND c.degree_program = ?
            ");

            foreach ($subject_ids as $subject_id) {
                $stmt->bind_param("iissss", 
                    $student_id, 
                    $subject_id, 
                    $enrollment_date, 
                    $subject_id,
                    $currentSemester,
                    $student_info['year_level'],
                    $student_info['degree_program']
                );
                $stmt->execute();
                
                if ($stmt->affected_rows === 0) {
                    throw new Exception("Subject ID $subject_id is not in the curriculum for this semester and year level.");
                }
            }

            $conn->commit();
            $success_message = "Student enrolled successfully in $currentSemester subjects!";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error enrolling student: " . $e->getMessage();
        }
    }
}

// Get current enrollments for display
$current_enrollments = [];
if (isset($_GET['student_id'])) {
    $stmt = $conn->prepare("
        SELECT ss.subject_id 
        FROM student_subjects ss
        JOIN curriculum c ON ss.subject_id = c.subject_id
        WHERE ss.student_id = ? 
        AND ss.status = 'active'
        AND c.semester = ?
    ");
    $stmt->bind_param("is", $_GET['student_id'], $currentSemester);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $current_enrollments[] = $row['subject_id'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll Student - Academic Performance Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="bg-sky-50 font-sans min-h-screen">
    <div class="p-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="bg-gradient-to-r from-sky-500 to-sky-600 rounded-2xl p-8 text-white shadow-lg mb-8">
                <h1 class="text-3xl font-semibold mb-2">Enroll Student in Subjects</h1>
                <p class="text-sky-100">Current Term: <?php echo htmlspecialchars($currentSemester . ' - ' . $academicYear); ?></p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <!-- Enrollment Form -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-sky-50 to-white">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                        <span class="mr-2 text-sky-500">📝</span>
                        Enrollment Form
                    </h2>
                </div>
                <div class="p-6">
                    <form method="POST" class="space-y-6">
                        <!-- Student Selection -->
                        <div>
                            <label for="student" class="block text-sm font-medium text-gray-700 mb-1">Select Student</label>
                            <select id="student" name="student_id" required onchange="window.location.href='?student_id='+this.value"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                                <option value="">Choose a student...</option>
                                <?php foreach ($students as $student): ?>
                                    <?php if ($student['semester'] === $currentSemester): ?>
                                        <option value="<?php echo $student['id']; ?>" 
                                                <?php echo (isset($_GET['student_id']) && $_GET['student_id'] == $student['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['full_name'] . ' (' . $student['year_level'] . ' - ' . $student['semester'] . ')'); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-1 text-sm text-gray-500">Only showing students enrolled in <?php echo htmlspecialchars($currentSemester); ?></p>
                        </div>

                        <?php if (isset($_GET['student_id'])): ?>
                            <!-- Subject Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Subjects for <?php echo htmlspecialchars($currentSemester); ?></label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <?php 
                                    // Get student's year level and program
                                    $student_info = null;
                                    foreach ($students as $s) {
                                        if ($s['id'] == $_GET['student_id']) {
                                            $student_info = $s;
                                            break;
                                        }
                                    }
                                    
                                    foreach ($subjects as $subject): 
                                        // Only show subjects for student's year level and program
                                        if ($subject['year_level'] === $student_info['year_level'] && 
                                            $subject['degree_program'] === $student_info['degree_program']):
                                    ?>
                                        <div class="flex items-start">
                                            <div class="flex items-center h-5">
                                                <input type="checkbox" 
                                                       name="subject_ids[]" 
                                                       value="<?php echo $subject['id']; ?>"
                                                       <?php echo in_array($subject['id'], $current_enrollments) ? 'checked' : ''; ?>
                                                       class="w-4 h-4 text-sky-600 border-gray-300 rounded focus:ring-sky-500">
                                            </div>
                                            <div class="ml-3">
                                                <label class="text-sm font-medium text-gray-700">
                                                    <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']); ?>
                                                </label>
                                                <p class="text-xs text-gray-500">
                                                    <?php echo htmlspecialchars($subject['teacher_name']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit"
                                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 transition-colors shadow-sm hover:shadow">
                                    📚 Save Enrollment
                                </button>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add validation to ensure 6-8 subjects are selected
        document.querySelector('form').addEventListener('submit', function(e) {
            const selectedSubjects = document.querySelectorAll('input[name="subject_ids[]"]:checked');
            if (selectedSubjects.length < 6 || selectedSubjects.length > 8) {
                e.preventDefault();
                alert('Please select between 6 and 8 subjects for enrollment.');
            }
        });
    </script>
</body>
</html> 