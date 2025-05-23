<?php
require_once 'init.php';

function checkDatabaseConnection() {
    global $conn;
    echo "Checking database connection... ";
    if ($conn && $conn->ping()) {
        echo "OK\n";
        return true;
    }
    echo "FAILED\n";
    return false;
}

function checkTables() {
    global $conn;
    echo "\nChecking required tables:\n";
    
    $required_tables = [
        'users', 'students', 'teachers', 'subjects', 'grades', 'attendance',
        'curriculum', 'student_subjects', 'reports', 'report_requests',
        'login_history', 'remember_tokens', 'password_resets',
        'login_attempts', 'system_settings', 'maintenance_logs',
        'activity_logs', 'notifications', 'academic_terms'
    ];
    
    $missing_tables = [];
    foreach ($required_tables as $table) {
        echo "- $table... ";
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "OK\n";
        } else {
            echo "MISSING\n";
            $missing_tables[] = $table;
        }
    }
    
    return empty($missing_tables) ? true : $missing_tables;
}

function checkDefaultData() {
    global $conn;
    echo "\nChecking default data:\n";
    
    $checks = [
        'Admin user' => "SELECT COUNT(*) as count FROM users WHERE role = 'admin'",
        'Teacher accounts' => "SELECT COUNT(*) as count FROM users WHERE role = 'teacher'",
        'Default subjects' => "SELECT COUNT(*) as count FROM subjects",
        'Curriculum entries' => "SELECT COUNT(*) as count FROM curriculum",
        'System settings' => "SELECT COUNT(*) as count FROM system_settings"
    ];
    
    $missing_data = [];
    foreach ($checks as $name => $query) {
        echo "- $name... ";
        $result = $conn->query($query);
        $count = $result->fetch_assoc()['count'];
        if ($count > 0) {
            echo "OK ($count records)\n";
        } else {
            echo "MISSING\n";
            $missing_data[] = $name;
        }
    }
    
    return empty($missing_data) ? true : $missing_data;
}

function checkFilePermissions() {
    echo "\nChecking file permissions:\n";
    
    $paths = [
        'logs' => LOG_PATH,
        'uploads' => UPLOAD_PATH,
        'backups' => __DIR__ . '/backups'
    ];
    
    $issues = [];
    foreach ($paths as $name => $path) {
        echo "- $name directory ($path)... ";
        if (!file_exists($path)) {
            echo "MISSING\n";
            $issues[] = "$name directory does not exist";
            continue;
        }
        if (!is_writable($path)) {
            echo "NOT WRITABLE\n";
            $issues[] = "$name directory is not writable";
            continue;
        }
        echo "OK\n";
    }
    
    return empty($issues) ? true : $issues;
}

function checkSecuritySettings() {
    echo "\nChecking security settings:\n";
    
    $issues = [];
    
    // Check session settings
    echo "- Session security... ";
    if (ini_get('session.cookie_httponly') && 
        ini_get('session.use_only_cookies') && 
        ini_get('session.cookie_secure')) {
        echo "OK\n";
    } else {
        echo "ISSUES FOUND\n";
        $issues[] = "Insecure session settings";
    }
    
    // Check error reporting in production
    echo "- Error reporting... ";
    if (!DEBUG_MODE && error_reporting() === 0) {
        echo "OK\n";
    } else {
        echo "WARNING\n";
        $issues[] = "Error reporting might expose sensitive information";
    }
    
    return empty($issues) ? true : $issues;
}

// Run all checks
echo "Starting system verification...\n\n";

$all_passed = true;

// Check database connection
if (!checkDatabaseConnection()) {
    echo "\nFATAL: Database connection failed. Please check configuration.\n";
    exit(1);
}

// Check tables
$tables_check = checkTables();
if ($tables_check !== true) {
    echo "\nWARNING: Missing tables: " . implode(", ", $tables_check) . "\n";
    $all_passed = false;
}

// Check default data
$data_check = checkDefaultData();
if ($data_check !== true) {
    echo "\nWARNING: Missing data: " . implode(", ", $data_check) . "\n";
    $all_passed = false;
}

// Check file permissions
$permissions_check = checkFilePermissions();
if ($permissions_check !== true) {
    echo "\nWARNING: Permission issues: " . implode(", ", $permissions_check) . "\n";
    $all_passed = false;
}

// Check security settings
$security_check = checkSecuritySettings();
if ($security_check !== true) {
    echo "\nWARNING: Security issues: " . implode(", ", $security_check) . "\n";
    $all_passed = false;
}

echo "\nVerification complete. ";
if ($all_passed) {
    echo "All checks passed successfully!\n";
} else {
    echo "Some issues were found. Please review the warnings above.\n";
} 