<?php
require_once 'db.php';

function executeSQLFile($conn, $file) {
    echo "Executing SQL file: $file\n";
    
    try {
        // Read SQL file
        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new Exception("Could not read SQL file: $file");
        }
        
        // Split into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        // Start transaction
        $conn->begin_transaction();
        
        // Execute each statement
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    if (!$conn->query($statement)) {
                        throw new Exception("Error executing statement: " . $conn->error);
                    }
                } catch (Exception $e) {
                    // Log the error and continue if it's just a duplicate key or similar
                    if (strpos($e->getMessage(), 'Duplicate') !== false ||
                        strpos($e->getMessage(), 'already exists') !== false) {
                        echo "Notice: " . $e->getMessage() . "\n";
                        continue;
                    }
                    throw $e;
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        echo "Successfully executed SQL file: $file\n";
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo "Error: " . $e->getMessage() . "\n";
        return false;
    }
    return true;
}

try {
    echo "Starting database update...\n";
    
    // Disable foreign key checks temporarily
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Execute SQL files in order
    $files = [
        'sql/create_login_history.sql',
        'sql/update_schema.sql',
        'sql/update_grades_table.sql'
    ];
    
    $success = true;
    foreach ($files as $file) {
        if (!executeSQLFile($conn, $file)) {
            $success = false;
            break;
        }
    }
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    if ($success) {
        echo "Database update completed successfully!\n";
    } else {
        echo "Database update completed with some errors. Please check the logs.\n";
    }
    
} catch (Exception $e) {
    // Re-enable foreign key checks even if there's an error
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    echo "Error during database update: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close(); 