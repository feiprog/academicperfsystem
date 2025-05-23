<?php
$servername = "localhost"; // Database server
$username = "root"; // Your database username
$password = ""; // Your database password (leave empty if no password set)
$dbname = "academicperfsystem"; // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
