<?php
/**
 * Automatic Database Backup Script
 * Compatible with EasyCron
 * 
 * EasyCron URL: https://your-domain.com/guild-system/backups/backup.php?auth=YOUR_SECRET_KEY
 * 
 * SETUP INSTRUCTIONS:
 * 1. Generate a strong random token and replace YOUR_SECRET_AUTH_KEY
 * 2. Add this URL to EasyCron with the correct auth token
 * 3. EasyCron will call this daily/weekly to create automatic backups
 */

// ========== SECURITY ==========
// Change this to a strong random string! Example: sha1(uniqid(mt_rand(), true))
define('BACKUP_AUTH_TOKEN', 'c3673591591474aa536a2585cebcbb89f7f7a070');
define('MAX_BACKUPS', 7); // Keep last 7 backups to save space

// Verify authorization
if (!isset($_GET['auth']) || $_GET['auth'] !== BACKUP_AUTH_TOKEN) {
    http_response_code(403);
    die(json_encode([
        'success' => false, 
        'message' => 'Unauthorized - Invalid or missing auth token'
    ]));
}

// ========== LOAD ENVIRONMENT VARIABLES ==========
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments and empty lines
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim(trim($value), "\"'");
        }
    }
}

// ========== DATABASE CONFIGURATION ==========
$dbHost = $_ENV['DB_HOST'] ?? 'sql309.infinityfree.com';
$dbUser = $_ENV['DB_USER'] ?? 'if0_41947788';
$dbPassword = $_ENV['DB_PASSWORD'] ?? 'ar7P5I8wH8';
$dbName = $_ENV['DB_NAME'] ?? 'if0_41947788_guild_management';
$dbPort = $_ENV['DB_PORT'] ?? '3306';

// ========== CREATE BACKUP ==========
$backupDir = __DIR__;
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Generate unique filename with timestamp
$timestamp = date('Y-m-d_H-i-s');
$backupFile = $backupDir . '/backup_' . $timestamp . '.sql';

// Build mysqldump command
// For Infinity Free MySQL, use: mysqldump -h sql102.infinityfree.com -u USER -p PASSWORD DB_NAME
$command = sprintf(
    "mysqldump -h %s -P %s -u %s -p'%s' %s > %s 2>&1",
    escapeshellarg($dbHost),
    escapeshellarg($dbPort),
    escapeshellarg($dbUser),
    str_replace("'", "'\\''", $dbPassword), // Escape single quotes in password
    escapeshellarg($dbName),
    escapeshellarg($backupFile)
);

// Execute backup command
$output = [];
$returnCode = 0;
exec($command, $output, $returnCode);

// ========== HANDLE RESULTS ==========
$startTime = microtime(true);
$success = $returnCode === 0 && file_exists($backupFile) && filesize($backupFile) > 0;

if ($success) {
    $fileSize = filesize($backupFile);
    $fileSizeKB = round($fileSize / 1024, 2);
    
    // Cleanup old backups - keep only MAX_BACKUPS recent ones
    $backups = glob($backupDir . '/backup_*.sql');
    if (is_array($backups)) {
        // Sort by modification time (newest first)
        usort($backups, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Delete backups beyond MAX_BACKUPS
        while (count($backups) > MAX_BACKUPS) {
            $oldBackup = array_pop($backups);
            if (file_exists($oldBackup)) {
                unlink($oldBackup);
            }
        }
    }
    
    // Log successful backup
    $logEntry = date('Y-m-d H:i:s') . " - SUCCESS\n" .
                "  File: " . basename($backupFile) . "\n" .
                "  Size: " . $fileSizeKB . " KB\n" .
                "  Database: " . $dbName . "\n\n";
    
    file_put_contents($backupDir . '/backup.log', $logEntry, FILE_APPEND);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Database backup created successfully',
        'file' => basename($backupFile),
        'size_kb' => $fileSizeKB,
        'size_bytes' => $fileSize,
        'database' => $dbName,
        'timestamp' => $timestamp
    ]);
    
} else {
    // Log failed backup
    $errorMsg = implode("\n", $output) ?: "Unknown error (mysqldump may not be available)";
    $logEntry = date('Y-m-d H:i:s') . " - FAILED\n" .
                "  Reason: " . $errorMsg . "\n" .
                "  Command: mysqldump from " . $dbHost . "\n\n";
    
    file_put_contents($backupDir . '/backup.log', $logEntry, FILE_APPEND);
    
    // Cleanup incomplete backup file
    if (file_exists($backupFile)) {
        unlink($backupFile);
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database backup failed',
        'reason' => $errorMsg,
        'troubleshooting' => [
            'If mysqldump not found: Contact Infinity Free support to enable command-line tools',
            'Check database credentials in .env file',
            'Ensure database exists on server'
        ]
    ]);
}

// Optional: Log to file for debugging
file_put_contents(
    $backupDir . '/backup-requests.log',
    date('Y-m-d H:i:s') . " | Auth: " . (isset($_GET['auth']) ? 'OK' : 'MISSING') . 
    " | Success: " . ($success ? 'YES' : 'NO') . "\n",
    FILE_APPEND
);
?>
