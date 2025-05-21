<?php
session_start();

// Clear remember me token if it exists
if (isset($_COOKIE['remember_token'])) {
    try {
        // Delete token from database
        require_once 'db.php';
        $token = $_COOKIE['remember_token'];
        $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        
        // Clear cookie
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    } catch (Exception $e) {
        // Log error but continue with logout process
        error_log("Error clearing remember token: " . $e->getMessage());
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?> 