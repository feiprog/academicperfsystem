<?php
require_once 'db.php';

try {
    // Read and execute SQL file
    $sql = file_get_contents('sql/create_login_history.sql');
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $conn->query($statement);
        }
    }
    
    echo "Database changes applied successfully!\n";
    
} catch (Exception $e) {
    echo "Error applying database changes: " . $e->getMessage() . "\n";
}

$conn->close(); 