<?php
require_once 'db.php';

echo "Verifying user accounts...\n";

$sql = "SELECT username, role, password FROM users";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "Username: " . $row['username'] . "\n";
        echo "Role: " . $row['role'] . "\n";
        echo "Password hash length: " . strlen($row['password']) . "\n";
        echo "-------------------\n";
    }
} else {
    echo "No users found in database\n";
}

$conn->close();
?> 