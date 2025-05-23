<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

error_log("Attempting database connection...");

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'academicperfsystem';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql) === FALSE) {
    error_log("Error creating database: " . $conn->error);
    die("Error creating database: " . $conn->error);
}

// Select the database
if (!$conn->select_db($database)) {
    error_log("Error selecting database: " . $conn->error);
    die("Error selecting database: " . $conn->error);
}

// Test the connection and database
$test = $conn->query("SELECT 1");
if (!$test) {
    error_log("Database test failed: " . $conn->error);
    die("Database test failed: " . $conn->error);
}

error_log("Database connection successful");
?>
