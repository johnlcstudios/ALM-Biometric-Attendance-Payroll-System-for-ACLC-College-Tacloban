#!/usr/bin/env php
<?php
// Automated Database Backup Script
// Run this via cron job: 0 2 * * * /usr/bin/php /path/to/backup.php

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'alm_biometrics';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$backupDir = getenv('BACKUP_DIR') ?: __DIR__ . '/backups';
$retentionDays = (int)(getenv('BACKUP_RETENTION_DAYS') ?: 30);

// Create backup directory if it doesn't exist
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Generate backup filename with timestamp
$timestamp = date('Y-m-d_H-i-s');
$backupFile = $backupDir . '/' . $dbName . '_' . $timestamp . '.sql';
$compressedFile = $backupFile . '.gz';

echo "Starting database backup...\n";
echo "Database: $dbName\n";
echo "Backup file: $backupFile\n";

// Build mysqldump command
$command = sprintf(
    'mysqldump --host=%s --user=%s --password=%s --single-transaction --quick --lock-tables=false %s > %s',
    escapeshellarg($dbHost),
    escapeshellarg($dbUser),
    escapeshellarg($dbPass),
    escapeshellarg($dbName),
    escapeshellarg($backupFile)
);

// Execute backup
exec($command, $output, $returnVar);

if ($returnVar === 0 && file_exists($backupFile)) {
    $fileSize = filesize($backupFile);
    echo "Backup created successfully: " . number_format($fileSize / 1024, 2) . " KB\n";
    
    // Compress the backup
    echo "Compressing backup...\n";
    $data = file_get_contents($backupFile);
    $compressed = gzencode($data, 9);
    file_put_contents($compressedFile, $compressed);
    
    $compressedSize = filesize($compressedFile);
    echo "Compressed backup: " . number_format($compressedSize / 1024, 2) . " KB\n";
    
    // Remove uncompressed file
    unlink($backupFile);
    
    // Clean old backups
    echo "Cleaning old backups (older than $retentionDays days)...\n";
    $cleaned = 0;
    $files = glob($backupDir . '/*.sql.gz');
    
    foreach ($files as $file) {
        if (filemtime($file) < strtotime("-$retentionDays days")) {
            unlink($file);
            $cleaned++;
            echo "Deleted: " . basename($file) . "\n";
        }
    }
    
    echo "Cleaned $cleaned old backup(s)\n";
    echo "Backup completed successfully!\n";
    
    // Log backup
    $logFile = $backupDir . '/backup.log';
    $logEntry = date('Y-m-d H:i:s') . " - Backup completed: " . basename($compressedFile) . " (" . number_format($compressedSize / 1024, 2) . " KB)\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
} else {
    echo "ERROR: Backup failed!\n";
    echo "Return code: $returnVar\n";
    echo "Output: " . implode("\n", $output) . "\n";
    
    // Log error
    $logFile = $backupDir . '/backup.log';
    $logEntry = date('Y-m-d H:i:s') . " - ERROR: Backup failed\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    exit(1);
}
?>

