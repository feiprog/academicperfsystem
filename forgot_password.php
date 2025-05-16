<?php
ob_start();
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT id, username, full_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        
        // Get current time in PHP and convert to MySQL datetime
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $expires = clone $now;
        $expires->modify('+10 minutes');
        
        // Store reset token with expiration
        $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user['id'], $token, $expires->format('Y-m-d H:i:s'));
        if ($stmt->execute()) {
            // Debug output
            error_log('Token created with expiration: ' . $expires->format('Y-m-d H:i:s'));
            header('Location: reset_password.php?token=' . $token);
            exit();
        } else {
            $error = "Failed to generate reset token. Please try again.";
        }
    } else {
        $error = "No account found with that email address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Academic Performance Monitoring</title>
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
                <h1 class="text-4xl font-bold text-white mb-6 animate-slide-in">Forgot Password?</h1>
                <p class="text-sky-100 text-lg mb-8 leading-relaxed animate-fade-in delay-100">
                    Don't worry! We'll help you recover your account. Enter your email address and we'll send you a password reset link.
                </p>
                <div class="space-y-6">
                    <div class="flex items-start animate-slide-in delay-200 hover:translate-x-2 transition-transform duration-300">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-white">Quick Recovery</h3>
                            <p class="mt-1 text-sky-100">Get back to your account in minutes</p>
                        </div>
                    </div>
                    <div class="flex items-start animate-slide-in delay-300 hover:translate-x-2 transition-transform duration-300">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-white">Secure Process</h3>
                            <p class="mt-1 text-sky-100">Your account security is our priority</p>
                        </div>
                    </div>
                    <div class="flex items-start animate-slide-in delay-400 hover:translate-x-2 transition-transform duration-300">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-white">Easy Steps</h3>
                            <p class="mt-1 text-sky-100">Follow simple instructions to reset</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-sky-100 text-sm animate-fade-in delay-500">
                <p>© <?php echo date('Y'); ?> Granby Colleges of Science and Technology</p>
                <p class="mt-1">Empowering Education Through Technology</p>
            </div>
        </div>

        <!-- Right Section - Forgot Password Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <!-- Mobile Logo (visible only on small screens) -->
                <div class="lg:hidden text-center mb-8 animate-scale-in">
                    <img src="images/logo.png" alt="School Logo" class="w-20 h-20 mx-auto rounded-xl bg-sky-50 p-2 hover:scale-105 transition-transform duration-300">
                    <h1 class="mt-4 text-2xl font-bold text-gray-900 animate-slide-in">Forgot Password?</h1>
                </div>

                <!-- Forgot Password Card -->
                <div class="bg-white rounded-2xl shadow-xl p-8 animate-fade-in">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 animate-slide-in">Reset Your Password</h2>
                        <p class="mt-2 text-gray-600 animate-fade-in delay-100">Enter your email to receive reset instructions</p>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="mb-6 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg flex items-center animate-fade-in">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-6">
                        <div class="animate-slide-in delay-200">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       required 
                                       class="w-full pl-10 px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-colors"
                                       placeholder="Enter your email address">
                            </div>
                        </div>

                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-sky-500 to-sky-600 text-white py-2.5 px-4 rounded-lg hover:from-sky-600 hover:to-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transform transition-all duration-200 hover:-translate-y-0.5 font-medium animate-slide-in delay-400">
                            Send Reset Link
                        </button>
                    </form>

                    <div class="mt-6 text-center animate-fade-in delay-500">
                        <a href="login.php" class="text-sky-600 hover:text-sky-700 font-medium transition-colors">
                            Back to Login
                        </a>
                    </div>
                </div>

                <!-- Mobile Footer (visible only on small screens) -->
                <div class="lg:hidden mt-8 text-center text-sm text-gray-600 animate-fade-in delay-500">
                    <p>© <?php echo date('Y'); ?> Academic Performance Monitoring System</p>
                    <p class="mt-1">Granby Colleges of Science and Technology</p>
                </div>
            </div>
        </div>
    </div>

    <script>
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