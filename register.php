<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Debug: Log the POST data
    error_log("Registration POST data: " . print_r($_POST, true));
    
    $username = $conn->real_escape_string($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $conn->real_escape_string($_POST['email']);
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $full_name = $first_name . ' ' . $last_name;
    $role = 'student'; // Since this is student registration
    
    // Additional fields based on role
    $year_level = isset($_POST['year_level']) ? $conn->real_escape_string($_POST['year_level']) : null;
    
    // Fix year level formatting
    switch($year_level) {
        case '1':
            $year_level = '1st Year';
            break;
        case '2':
            $year_level = '2nd Year';
            break;
        case '3':
            $year_level = '3rd Year';
            break;
        case '4':
            $year_level = '4th Year';
            break;
        default:
            $year_level = null;
    }
    
    $degree_program = isset($_POST['program']) ? $conn->real_escape_string($_POST['program']) : null;
    
    // Debug: Log the processed data
    error_log("Processed registration data: " . print_r([
        'username' => $username,
        'email' => $email,
        'full_name' => $full_name,
        'year_level' => $year_level,
        'degree_program' => $degree_program
    ], true));
    
    // Determine current semester based on date
    $currentMonth = date('n');
    $semester = ($currentMonth >= 6 && $currentMonth <= 10) ? 'First Semester' : 'Second Semester';
    $academicYear = ($currentMonth >= 6) ? date('Y') . '-' . (date('Y') + 1) : (date('Y') - 1) . '-' . date('Y');

    // Debug: Log the semester determination
    error_log("Current month: $currentMonth, Determined semester: $semester");

    // Validate password match
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $error = "Passwords do not match";
        error_log("Registration failed: Passwords do not match");
    } else {
    // Start transaction
    $conn->begin_transaction();

    try {
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Username or email already exists");
            }

            // Debug: Log the SQL operations
            error_log("Inserting new user into users table");
            $sql = "INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $username, $password, $email, $full_name, $role);
            $stmt->execute();
            $user_id = $conn->insert_id;
            error_log("New user ID: " . $user_id);

            // Insert into students table with auto-generated student ID
            $student_id = 'STU' . date('Y') . str_pad($user_id, 4, '0', STR_PAD_LEFT);
            error_log("Generated student ID: " . $student_id);
            
            $sql = "INSERT INTO students (user_id, student_id, first_name, last_name, year_level, degree_program, semester, academic_year) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssssss", $user_id, $student_id, $first_name, $last_name, $year_level, $degree_program, $semester, $academicYear);
            
            error_log("Executing student insert with params: " . print_r([
                'user_id' => $user_id,
                'student_id' => $student_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'year_level' => $year_level,
                'degree_program' => $degree_program,
                'semester' => $semester,
                'academic_year' => $academicYear
            ], true));
            
            $stmt->execute();
            $student_db_id = $conn->insert_id;
            error_log("New student ID in students table: " . $student_db_id);

            // Automatically enroll student in subjects based on curriculum for the current semester only
            $sql = "INSERT INTO student_subjects (student_id, subject_id, enrollment_date, status)
                    SELECT ?, c.subject_id, CURDATE(), 'active'
                    FROM curriculum c
                    WHERE c.degree_program = ? 
                    AND c.year_level = ?
                    AND c.semester = ?
                    AND NOT EXISTS (
                        SELECT 1 FROM student_subjects ss 
                        WHERE ss.student_id = ? 
                        AND ss.subject_id = c.subject_id
                    )";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssi", $student_db_id, $degree_program, $year_level, $semester, $student_db_id);
            
            error_log("Executing subject enrollment with params: " . print_r([
                'student_db_id' => $student_db_id,
                'degree_program' => $degree_program,
                'year_level' => $year_level,
                'semester' => $semester
            ], true));
            
            $stmt->execute();

            // Log enrollment for debugging
            $enrolled_count = $stmt->affected_rows;
            error_log("Number of subjects enrolled: " . $enrolled_count);
            
            if ($enrolled_count == 0) {
                // Check if curriculum exists for this program/year/semester
                $check_curriculum = $conn->prepare("
                    SELECT COUNT(*) as count 
                    FROM curriculum 
                    WHERE degree_program = ? 
                    AND year_level = ? 
                    AND semester = ?
                ");
                $check_curriculum->bind_param("sss", $degree_program, $year_level, $semester);
                $check_curriculum->execute();
                $curriculum_count = $check_curriculum->get_result()->fetch_assoc()['count'];
                error_log("Curriculum count for {$degree_program} {$year_level} {$semester}: {$curriculum_count}");
                
                if ($curriculum_count == 0) {
                    throw new Exception("No curriculum found for $degree_program $year_level $semester. Please contact the administrator.");
                }
            }

        $conn->commit();
            $_SESSION['success'] = "Registration successful! Your student ID is " . $student_id . ". Please login.";
            error_log("Registration successful for student ID: " . $student_id);
        header("Location: login.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Registration failed: " . $e->getMessage();
        error_log("Registration failed with error: " . $e->getMessage());
    }
}
}

// Get available degree programs
$degree_programs = ['BSIT', 'BSCS', 'BSCE', 'BSEE'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Academic Performance Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        @keyframes slideInLeft {
            from { transform: translateX(-50px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes scaleIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        .animate-slide-in {
            animation: slideInLeft 0.5s ease-out forwards;
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
        .animate-scale-in {
            animation: scaleIn 0.5s ease-out forwards;
        }
        .delay-100 { animation-delay: 100ms; }
        .delay-200 { animation-delay: 200ms; }
        .delay-300 { animation-delay: 300ms; }
        .delay-400 { animation-delay: 400ms; }
        .delay-500 { animation-delay: 500ms; }
    </style>
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
<body class="bg-gray-50 font-sans min-h-screen">
    <!-- Main Container -->
    <div class="min-h-screen flex">
        <!-- Left Section - Introduction -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-sky-600 to-sky-800 p-12 flex-col justify-between relative overflow-hidden">
            <!-- Animated Background Elements -->
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute -top-40 -right-40 w-80 h-80 bg-white/5 rounded-full blur-3xl animate-float"></div>
                <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-white/5 rounded-full blur-3xl animate-float delay-200"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-white/5 rounded-full blur-3xl animate-float delay-300"></div>
            </div>

            <div class="max-w-lg relative z-10">
                <div class="mb-8 animate-scale-in">
                    <img src="images/logo.png" alt="School Logo" class="w-20 h-20 rounded-xl bg-white/10 p-2 backdrop-blur-sm hover:scale-105 transition-transform duration-300">
                </div>
                <h1 class="text-4xl font-bold text-white mb-6 animate-slide-in">Join Our Academic Community</h1>
                <p class="text-sky-100 text-lg mb-8 leading-relaxed animate-fade-in delay-100">
                    Create your account to access our comprehensive academic performance monitoring system. 
                    Track your progress, view grades, and stay connected with your academic journey.
                </p>
                <div class="space-y-6">
                    <div class="flex items-start animate-slide-in delay-200 hover:translate-x-2 transition-transform duration-300">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-white">Track Your Progress</h3>
                            <p class="mt-1 text-sky-100">Monitor your academic performance in real-time</p>
                        </div>
                    </div>
                    <div class="flex items-start animate-slide-in delay-300 hover:translate-x-2 transition-transform duration-300">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-white">Access Course Materials</h3>
                            <p class="mt-1 text-sky-100">View assignments, grades, and course information</p>
                        </div>
                    </div>
                    <div class="flex items-start animate-slide-in delay-400 hover:translate-x-2 transition-transform duration-300">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-white">Stay Connected</h3>
                            <p class="mt-1 text-sky-100">Communicate with teachers and track your attendance</p>
                        </div>
                    </div>
            </div>
            </div>
            <div class="text-sky-100 text-sm animate-fade-in delay-500">
                <p>© <?php echo date('Y'); ?> Granby Colleges of Science and Technology</p>
                <p class="mt-1">Empowering Education Through Technology</p>
            </div>
            </div>

        <!-- Right Section - Registration Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <!-- Mobile Logo (visible only on small screens) -->
                <div class="lg:hidden text-center mb-8">
                    <img src="images/logo.png" alt="School Logo" class="w-20 h-20 mx-auto rounded-xl bg-sky-50 p-2">
                    <h1 class="mt-4 text-2xl font-bold text-gray-900">Student Registration</h1>
                </div>

                <!-- Registration Card -->
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-gray-900">Create Your Account</h2>
                        <p class="mt-2 text-gray-600">Fill in your details to get started</p>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="mb-6 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-6">
                        <!-- Personal Information -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900">Personal Information</h3>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                    <input type="text" 
                                           id="first_name" 
                                           name="first_name" 
                                           required 
                                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-colors"
                                           placeholder="Enter first name">
                                </div>
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                    <input type="text" 
                                           id="last_name" 
                                           name="last_name" 
                                           required 
                                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-colors"
                                           placeholder="Enter last name">
                                </div>
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       required 
                                       class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-colors"
                                       placeholder="Enter email address">
                            </div>
                        </div>

                        <!-- Academic Information -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900">Academic Information</h3>
                            
                            <div>
                                <label for="program" class="block text-sm font-medium text-gray-700 mb-1">Degree Program</label>
                                <select id="program" 
                                        name="program" 
                                        required 
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-colors">
                                    <option value="">Select your program</option>
                                    <option value="BSIT">Bachelor of Science in Information Technology</option>
                                    <option value="BSCS">Bachelor of Science in Computer Science</option>
                                    <option value="BSCE">Bachelor of Science in Computer Engineering</option>
                                </select>
                            </div>

                            <div>
                                <label for="year_level" class="block text-sm font-medium text-gray-700 mb-1">Year Level</label>
                                <select id="year_level" 
                                        name="year_level" 
                                        required 
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-colors">
                                    <option value="">Select year level</option>
                                    <option value="1">First Year</option>
                                    <option value="2">Second Year</option>
                                    <option value="3">Third Year</option>
                                    <option value="4">Fourth Year</option>
                    </select>
                </div>
            </div>

                        <!-- Account Security -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900">Account Security</h3>
                            
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                                <input type="text" 
                                       id="username" 
                                       name="username" 
                                       required 
                                       class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-colors"
                                       placeholder="Choose a username">
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                <div class="relative">
                                    <input type="password" 
                                           id="password" 
                                           name="password" 
                                           required 
                                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-colors"
                                           placeholder="Create a password"
                                           onkeyup="checkPasswordStrength()">
                                    <button type="button" 
                                            onclick="togglePassword()"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                        <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </div>
                                <!-- Password Strength Indicator -->
                                <div class="mt-2">
                                    <div class="flex items-center space-x-2">
                                        <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                            <div id="strengthBar" class="h-full w-0 transition-all duration-300"></div>
                                        </div>
                                        <span id="strengthText" class="text-sm font-medium"></span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500">
                                        Password must be at least 8 characters long and contain only letters and numbers.
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                                <input type="password" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       required 
                                       class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-colors"
                                       placeholder="Confirm your password">
                            </div>
                        </div>

                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-sky-500 to-sky-600 text-white py-2.5 px-4 rounded-lg hover:from-sky-600 hover:to-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transform transition-all duration-200 hover:-translate-y-0.5 font-medium">
                            Create Account
                        </button>
                    </form>

                    <div class="mt-6 text-center">
                        <p class="text-sm text-gray-600">
                            Already have an account? 
                            <a href="login.php" class="text-sky-600 hover:text-sky-700 font-medium transition-colors">
                                Sign in here
                            </a>
                        </p>
                    </div>
                </div>

                <!-- Mobile Footer (visible only on small screens) -->
                <div class="lg:hidden mt-8 text-center text-sm text-gray-600">
                    <p>© <?php echo date('Y'); ?> Academic Performance Monitoring System</p>
                    <p class="mt-1">Granby Colleges of Science and Technology</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                `;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                `;
            }
        }

        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            // Reset strength indicator
            strengthBar.style.width = '0%';
            strengthBar.className = 'h-full w-0 transition-all duration-300';
            strengthText.textContent = '';
            
            if (password.length === 0) return;
            
            // Check for special characters
            if (/[^a-zA-Z0-9]/.test(password)) {
                strengthBar.style.width = '0%';
                strengthBar.className = 'h-full w-0 bg-red-500 transition-all duration-300';
                strengthText.textContent = 'Special characters not allowed';
                strengthText.className = 'text-sm font-medium text-red-500';
                return;
            }
            
            let strength = 0;
            let feedback = [];
            
            // Length check
            if (password.length >= 8) {
                strength += 1;
            } else {
                feedback.push('At least 8 characters');
            }
            
            // Contains number
            if (/\d/.test(password)) {
                strength += 1;
            } else {
                feedback.push('Include numbers');
            }
            
            // Contains letter
            if (/[a-zA-Z]/.test(password)) {
                strength += 1;
            } else {
                feedback.push('Include letters');
            }
            
            // Contains both uppercase and lowercase
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) {
                strength += 1;
            } else {
                feedback.push('Mix of upper and lowercase');
            }
            
            // Update strength indicator
            switch(strength) {
                case 0:
                case 1:
                    strengthBar.style.width = '25%';
                    strengthBar.className = 'h-full bg-red-500 transition-all duration-300';
                    strengthText.textContent = 'Weak';
                    strengthText.className = 'text-sm font-medium text-red-500';
                    break;
                case 2:
                    strengthBar.style.width = '50%';
                    strengthBar.className = 'h-full bg-yellow-500 transition-all duration-300';
                    strengthText.textContent = 'Medium';
                    strengthText.className = 'text-sm font-medium text-yellow-500';
                    break;
                case 3:
                    strengthBar.style.width = '75%';
                    strengthBar.className = 'h-full bg-blue-500 transition-all duration-300';
                    strengthText.textContent = 'Good';
                    strengthText.className = 'text-sm font-medium text-blue-500';
                    break;
                case 4:
                    strengthBar.style.width = '100%';
                    strengthBar.className = 'h-full bg-green-500 transition-all duration-300';
                    strengthText.textContent = 'Strong';
                    strengthText.className = 'text-sm font-medium text-green-500';
                    break;
            }
            
            // Show feedback if password is not strong
            if (strength < 4) {
                strengthText.textContent += ' - ' + feedback.join(', ');
            }
        }

        // Add intersection observer for animations
        document.addEventListener('DOMContentLoaded', function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-slide-in');
                    }
                });
            }, {
                threshold: 0.1
            });

            // Observe all animated elements
            document.querySelectorAll('.animate-slide-in, .animate-fade-in, .animate-scale-in').forEach((el) => {
                observer.observe(el);
            });
        });
    </script>
</body>
</html>
