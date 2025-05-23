<?php
require_once 'config.php';

// Set maximum execution time to 5 minutes
set_time_limit(300);

// Create backups directory if it doesn't exist
$backupDir = __DIR__ . '/backups';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Generate backup filename with timestamp
$backupFile = $backupDir . '/db_backup_' . date('Y-m-d_H-i-s') . '.sql';

// Set path to mysqldump
$mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';

// Construct the mysqldump command
$command = sprintf(
    '"%s" --host=%s --user=%s --password=%s %s > %s',
    $mysqldump,
    escapeshellarg(DB_HOST),
    escapeshellarg(DB_USER),
    escapeshellarg(DB_PASS),
    escapeshellarg(DB_NAME),
    escapeshellarg($backupFile)
);

// Execute the backup command
system($command, $returnValue);

if ($returnValue === 0) {
    echo "Database backup created successfully: " . basename($backupFile) . "\n";
} else {
    echo "Error creating database backup\n";
}
?> 