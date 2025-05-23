<?php
// Load configuration
require_once __DIR__ . '/config.php';

// Load core classes
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/SessionManager.php';
require_once __DIR__ . '/includes/ErrorHandler.php';

// Initialize error handling
ErrorHandler::init();

// Initialize session
SessionManager::init();

// Initialize database connection
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (Exception $e) {
    error_log("Failed to initialize database: " . $e->getMessage());
    die("System is currently unavailable. Please try again later.");
}

// Set up global functions
function requireLogin() {
    if (!SessionManager::isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
    
    if (!SessionManager::checkInactivity()) {
        header("Location: login.php?msg=session_expired");
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    $user = SessionManager::getUser();
    if ($user['role'] !== $role) {
        header("Location: unauthorized.php");
        exit();
    }
}

function requireAdmin() {
    requireRole('admin');
}

function requireTeacher() {
    requireRole('teacher');
}

function requireStudent() {
    requireRole('student');
}

// Clean up function for script termination
function cleanup() {
    if (isset($GLOBALS['db'])) {
        $GLOBALS['db']->close();
    }
}

// Register cleanup function
register_shutdown_function('cleanup'); 