<?php
// backup.php - Database backup system using mysqldump
require_once __DIR__ . '/db.php';

/**
 * Create a database backup
 * 
 * @param PDO $pdo Database connection
 * @param string $backup_dir Directory to store backups
 * @return array Result with success status and file info
 */
function createBackup($pdo, $backup_dir = null)
{
    if ($backup_dir === null) {
        $backup_dir = dirname(__DIR__) . '/backups';
    }
    
    if (!is_dir($backup_dir)) {
        if (!mkdir($backup_dir, 0755, true)) {
            return ['success' => false, 'message' => 'Failed to create backup directory'];
        }
    }
    
    $filename = $backup_dir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    // Get database credentials from environment
    $db_user = $_ENV['DB_USER'] ?? 'root';
    $db_pass = $_ENV['DB_PASS'] ?? '';
    $db_host = $_ENV['DB_HOST'] ?? 'localhost';
    $db_name = $_ENV['DB_NAME'] ?? 'alm_biometrics';
    
    // Build mysqldump command
    $command = sprintf(
        'mysqldump --user=%s --password=%s --host=%s --single-transaction --routines --triggers %s > %s 2>&1',
        escapeshellarg($db_user),
        escapeshellarg($db_pass),
        escapeshellarg($db_host),
        escapeshellarg($db_name),
        escapeshellarg($filename)
    );
    
    exec($command, $output, $return_var);
    
    if ($return_var === 0 && file_exists($filename) && filesize($filename) > 0) {
        // Compress the backup
        $gz_filename = $filename . '.gz';
        $file_data = file_get_contents($filename);
        $gz_data = gzencode($file_data, 9);
        
        if ($gz_data !== false) {
            file_put_contents($gz_filename, $gz_data);
            unlink($filename); // Remove uncompressed version
            
            // Cleanup old backups (keep last 30 days)
            cleanupOldBackups($backup_dir, 30);
            
            error_log("Database backup created: " . basename($gz_filename));
            return [
                'success' => true, 
                'file' => basename($gz_filename),
                'path' => $gz_filename,
                'size' => round(filesize($gz_filename) / 1024 / 1024, 2) . ' MB'
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to compress backup'];
        }
    } else {
        $error_msg = isset($output[0]) ? $output[0] : 'Unknown error';
        error_log("Database backup failed: $error_msg");
        return ['success' => false, 'message' => 'Backup failed: ' . $error_msg];
    }
}

/**
 * Clean up old backups
 */
function cleanupOldBackups($backup_dir, $days_to_keep)
{
    $files = glob($backup_dir . '/backup_*.sql.gz');
    $cutoff = strtotime("-$days_to_keep days");
    
    foreach ($files as $file) {
        if (filemtime($file) < $cutoff) {
            unlink($file);
            error_log("Deleted old backup: " . basename($file));
        }
    }
}

/**
 * Get list of available backups
 */
function getBackupList($backup_dir = null)
{
    if ($backup_dir === null) {
        $backup_dir = dirname(__DIR__) . '/backups';
    }
    
    if (!is_dir($backup_dir)) {
        return [];
    }
    
    $files = glob($backup_dir . '/backup_*.sql.gz');
    $backups = [];
    
    foreach ($files as $file) {
        $backups[] = [
            'filename' => basename($file),
            'path' => $file,
            'size' => round(filesize($file) / 1024 / 1024, 2) . ' MB',
            'created' => date('Y-m-d H:i:s', filemtime($file))
        ];
    }
    
    // Sort by date descending
    usort($backups, function($a, $b) {
        return strtotime($b['created']) - strtotime($a['created']);
    });
    
    return $backups;
}

/**
 * Restore database from backup
 */
function restoreBackup($backup_file, $pdo)
{
    if (!file_exists($backup_file)) {
        return ['success' => false, 'message' => 'Backup file not found'];
    }
    
    // Decompress if .gz
    $sql_file = $backup_file;
    if (substr($backup_file, -3) === '.gz') {
        $sql_file = $backup_file . '.tmp';
        $gz_data = file_get_contents($backup_file);
        $sql_data = gzdecode($gz_data);
        
        if ($sql_data === false) {
            return ['success' => false, 'message' => 'Failed to decompress backup'];
        }
        
        file_put_contents($sql_file, $sql_data);
    }
    
    // Get database credentials
    $db_user = $_ENV['DB_USER'] ?? 'root';
    $db_pass = $_ENV['DB_PASS'] ?? '';
    $db_host = $_ENV['DB_HOST'] ?? 'localhost';
    $db_name = $_ENV['DB_NAME'] ?? 'alm_biometrics';
    
    // Restore using mysql command
    $command = sprintf(
        'mysql --user=%s --password=%s --host=%s %s < %s 2>&1',
        escapeshellarg($db_user),
        escapeshellarg($db_pass),
        escapeshellarg($db_host),
        escapeshellarg($db_name),
        escapeshellarg($sql_file)
    );
    
    exec($command, $output, $return_var);
    
    // Cleanup temp file
    if ($sql_file !== $backup_file && file_exists($sql_file)) {
        unlink($sql_file);
    }
    
    if ($return_var === 0) {
        error_log("Database restored from: " . basename($backup_file));
        return ['success' => true, 'message' => 'Database restored successfully'];
    } else {
        $error_msg = isset($output[0]) ? $output[0] : 'Unknown error';
        return ['success' => false, 'message' => 'Restore failed: ' . $error_msg];
    }
}
