<?php
session_start();

// Clear remember me token if it exists
if (isset($_COOKIE['remember_token'])) {
    // Delete token from database
    require_once 'config.php';
    $token = $_COOKIE['remember_token'];
    $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    
    // Clear cookie
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

// Clear session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?> 