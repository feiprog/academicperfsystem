<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'academicperfsystem');

// Security Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('REMEMBER_ME_DURATION', 30 * 24 * 60 * 60); // 30 days
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_DURATION', 15 * 60); // 15 minutes

// Application Configuration
define('TIMEZONE', 'Asia/Manila');
define('DEBUG_MODE', false);
define('MAINTENANCE_MODE', false);
define('LOG_PATH', __DIR__ . '/logs');
define('UPLOAD_PATH', __DIR__ . '/uploads');

// Create required directories if they don't exist
if (!file_exists(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting based on debug mode
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
} 