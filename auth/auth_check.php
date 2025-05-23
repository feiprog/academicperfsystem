<?php
session_start();
require_once 'db.php';  // Add database connection

// Function to check if this is an API request
function isApiRequest() {
    return strpos($_SERVER['REQUEST_URI'], '/api/') !== false;
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function to check if user is teacher
function isTeacher() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
}

// Function to check if user is student
function isStudent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

// Function to require admin role
function requireAdmin() {
    if (!isLoggedIn()) {
        if (isApiRequest()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Please log in to continue']);
            exit();
        } else {
            header("Location: login.php");
            exit();
        }
    }
    
    if (!isAdmin()) {
        if (isApiRequest()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Admin access required']);
            exit();
        } else {
            header("Location: login.php");
            exit();
        }
    }
}

// Function to require teacher role
function requireTeacher() {
    if (!isLoggedIn()) {
        if (isApiRequest()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Please log in to continue']);
            exit();
        } else {
            header("Location: login.php");
            exit();
        }
    }
    
    if (!isTeacher()) {
        if (isApiRequest()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Teacher access required']);
            exit();
        } else {
            header("Location: login.php");
            exit();
        }
    }
}

// Function to require student role
function requireStudent() {
    if (!isLoggedIn()) {
        if (isApiRequest()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Please log in to continue']);
            exit();
        } else {
            header("Location: login.php");
            exit();
        }
    }
    
    if (!isStudent()) {
        if (isApiRequest()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Student access required']);
            exit();
        } else {
            header("Location: login.php");
            exit();
        }
    }
}

// Get current user's data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    global $conn;
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    try {
        // First get the base user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if (!$user) {
            return null;
        }
        
        // Remove sensitive information
        unset($user['password']);
        
        // Add role-specific data
        switch ($role) {
            case 'admin':
                $stmt = $conn->prepare("
                    SELECT teacher_id as admin_id, department
                    FROM teachers
                    WHERE user_id = ?
                ");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $admin_data = $stmt->get_result()->fetch_assoc();
                if ($admin_data) {
                    $user = array_merge($user, $admin_data);
                }
                break;
                
            case 'teacher':
                $stmt = $conn->prepare("
                    SELECT teacher_id, department
                    FROM teachers
                    WHERE user_id = ?
                ");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $teacher_data = $stmt->get_result()->fetch_assoc();
                if ($teacher_data) {
                    $user = array_merge($user, $teacher_data);
                }
                break;
                
            case 'student':
                $stmt = $conn->prepare("
                    SELECT student_id, first_name, last_name, year_level, degree_program, semester, academic_year
                    FROM students
                    WHERE user_id = ?
                ");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $student_data = $stmt->get_result()->fetch_assoc();
                if ($student_data) {
                    $user = array_merge($user, $student_data);
                }
                break;
        }
        
        return $user;
        
    } catch (Exception $e) {
        error_log('Error getting user data: ' . $e->getMessage());
        return null;
    }
}

// Add login history tracking
function logLogin($user_id) {
    global $conn;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $conn->prepare("
        INSERT INTO login_history (user_id, ip_address, user_agent)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("iss", $user_id, $ip, $user_agent);
    $stmt->execute();
}

// If not logged in and not on login page, redirect to login
if (!isLoggedIn() && !in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'register.php'])) {
    header("Location: login.php");
    exit();
}

// Add this after successful login validation
if (isset($_SESSION['user_id'])) {
    logLogin($_SESSION['user_id']);
}
?> 