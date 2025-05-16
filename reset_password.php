<?php
ob_start();
session_start();
require_once 'db.php';

if (!isset($_GET['token'])) {
    $fatal_error = 'No token provided in URL.';
} else {
    $token = $_GET['token'];
    echo "<pre>DEBUG: Token from URL = [$token]</pre>";
    
    // Get current time in PHP
    $now = new DateTime('now', new DateTimeZone('UTC'));
    echo "<pre>DEBUG: Current PHP Time (UTC): " . $now->format('Y-m-d H:i:s') . "</pre>";
    
    $result = $conn->query("SELECT * FROM password_resets WHERE token = '$token'");
    $row = $result->fetch_assoc();
    echo "<pre>DEBUG: DB Row = "; print_r($row); echo "</pre>";

    // Verify token
    $stmt = $conn->prepare("
        SELECT u.id, u.username 
        FROM users u 
        JOIN password_resets pr ON u.id = pr.user_id 
        WHERE pr.token = ? AND pr.expires_at > ? AND pr.used = 0
    ");
    $stmt->bind_param("ss", $token, $now->format('Y-m-d H:i:s'));
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $fatal_error = "Invalid or expired reset link. Please request a new password reset.";
    } else {
        $user = $result->fetch_assoc();
    }
}

if (!isset($fatal_error) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    // Validate password
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $password)) {
        $error = "Password can only contain letters and numbers.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Update password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user['id']);
        $stmt->execute();
        // Mark reset token as used
        $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $_SESSION['success'] = "Your password has been reset successfully. Please login with your new password.";
        header('Location: login.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Academic Performance Monitoring</title>
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
    <div class="min-h-screen flex">
        <div class="w-full flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <div class="bg-white rounded-2xl shadow-xl p-8 animate-fade-in">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 animate-slide-in">Create New Password</h2>
                        <p class="mt-2 text-gray-600 animate-fade-in delay-100">Enter your new password below</p>
                    </div>
                    <?php if (isset($fatal_error)): ?>
                        <div class="mb-6 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg flex items-center animate-fade-in">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <?php echo $fatal_error; ?>
                        </div>
                    <?php elseif (isset($error)): ?>
                        <div class="mb-6 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg flex items-center animate-fade-in">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!isset($fatal_error)): ?>
                    <form method="POST" action="" class="space-y-6">
                        <div class="animate-slide-in delay-200">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                            <div class="relative">
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       required 
                                       minlength="8"
                                       pattern="[a-zA-Z0-9]+"
                                       class="w-full pl-10 px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-colors"
                                       placeholder="Enter new password">
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Password must be at least 8 characters long and contain only letters and numbers.</p>
                        </div>
                        <div class="animate-slide-in delay-300">
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                            <div class="relative">
                                <input type="password" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       required 
                                       minlength="8"
                                       pattern="[a-zA-Z0-9]+"
                                       class="w-full pl-10 px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-colors"
                                       placeholder="Confirm new password">
                            </div>
                        </div>
                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-sky-500 to-sky-600 text-white py-2.5 px-4 rounded-lg hover:from-sky-600 hover:to-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transform transition-all duration-200 hover:-translate-y-0.5 font-medium animate-slide-in delay-400">
                            Reset Password
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>