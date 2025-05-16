<?php
session_start();
require_once 'db.php';  // Add database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Function to check if user is a student
function isStudent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

// Function to check if user is a teacher
function isTeacher() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
}

// Function to require student role
function requireStudent() {
    if (!isStudent()) {
        header("Location: login.php");
        exit();
    }
}

// Function to require teacher role
function requireTeacher() {
    if (!isTeacher()) {
        header("Location: login.php");
        exit();
    }
}

// Get current user's data
function getCurrentUser() {
    global $conn;
    $user_id = $_SESSION['user_id'];
    
    $sql = "SELECT u.*, 
            CASE 
                WHEN u.role = 'student' THEN s.student_id 
                WHEN u.role = 'teacher' THEN t.teacher_id 
            END as role_id,
            CASE 
                WHEN u.role = 'student' THEN s.year_level 
                WHEN u.role = 'teacher' THEN t.department 
            END as role_info
            FROM users u 
            LEFT JOIN students s ON u.id = s.user_id 
            LEFT JOIN teachers t ON u.id = t.user_id 
            WHERE u.id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}
?> 