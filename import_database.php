<?php
require_once 'config.php';

// Set maximum execution time to 5 minutes
set_time_limit(300);

// Set path to MySQL executable
$mysql = 'C:\\xampp\\mysql\\bin\\mysql.exe';

// Function to create database if it doesn't exist
function createDatabase($host, $user, $pass, $dbname, $mysql) {
    $command = sprintf(
        '"%s" --host=%s --user=%s --password=%s -e "CREATE DATABASE IF NOT EXISTS %s"',
        $mysql,
        escapeshellarg($host),
        escapeshellarg($user),
        escapeshellarg($pass),
        escapeshellarg($dbname)
    );
    
    system($command, $returnValue);
    
    if ($returnValue === 0) {
        echo "<p style='color: green;'>Database created successfully or already exists</p>";
    } else {
        die("<p style='color: red;'>Error creating database. Please check your MySQL credentials.</p>");
    }
}

// Add some basic HTML styling
echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Import</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .file-list { margin: 20px 0; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>";

// Check if backup file is provided
$backupFile = isset($_GET['file']) ? $_GET['file'] : null;
if (!$backupFile) {
    // List available backup files
    $backupDir = __DIR__ . '/backups';
    $files = glob($backupDir . '/*.sql');
    if (empty($files)) {
        die("<p class='error'>No backup files found in backups directory</p></body></html>");
    }
    
    echo "<h2>Available backup files:</h2><div class='file-list'>";
    foreach ($files as $file) {
        $filename = basename($file);
        echo "<p><a href='?file=" . urlencode($filename) . "'>" . htmlspecialchars($filename) . "</a></p>";
    }
    echo "</div></body></html>";
    exit();
}

// Validate and construct full backup file path
$backupDir = __DIR__ . '/backups';
$backupFile = $backupDir . '/' . basename($backupFile);

if (!file_exists($backupFile)) {
    die("<p class='error'>Backup file not found: " . htmlspecialchars(basename($backupFile)) . "</p></body></html>");
}

// Create database if it doesn't exist
createDatabase(DB_HOST, DB_USER, DB_PASS, DB_NAME, $mysql);

// Construct the mysql import command
$command = sprintf(
    '"%s" --host=%s --user=%s --password=%s %s < %s',
    $mysql,
    escapeshellarg(DB_HOST),
    escapeshellarg(DB_USER),
    escapeshellarg(DB_PASS),
    escapeshellarg(DB_NAME),
    escapeshellarg($backupFile)
);

// Execute the import command
system($command, $returnValue);

if ($returnValue === 0) {
    echo "<p class='success'>Database imported successfully from: " . htmlspecialchars(basename($backupFile)) . "</p>";
} else {
    echo "<p class='error'>Error importing database. Please check your MySQL credentials and permissions.</p>";
}

echo "</body></html>";
?> 