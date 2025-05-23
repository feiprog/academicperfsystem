<?php
require_once 'config.php';

$backup_dir = __DIR__ . '/backups';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

$date = date('Y-m-d_H-i-s');
$backup_file = $backup_dir . "/backup_$date.sql";

// Set mysqldump path for XAMPP
$mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';

// Create backup command
$command = sprintf(
    '"%s" -h %s -u %s %s %s > %s',
    $mysqldump,
    escapeshellarg(DB_HOST),
    escapeshellarg(DB_USER),
    DB_PASS ? '-p' . escapeshellarg(DB_PASS) : '',
    escapeshellarg(DB_NAME),
    escapeshellarg($backup_file)
);

echo "Creating database backup...\n";

// Execute backup
exec($command, $output, $return_var);

if ($return_var === 0) {
    echo "Backup created successfully: $backup_file\n";
} else {
    echo "Error creating backup: " . implode("\n", $output) . "\n";
    exit(1);
} 