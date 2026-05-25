<?php
/**
 * Backup Scheduler API
 * Handles manual and interval-based automatic backups
 * Works on any hosting: Render, InfinityFree, iFastNet, etc.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/backup-auth.php';

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

// ========== AUTO-BACKUP TOKEN ==========
// Read from .env or use default
define('AUTO_BACKUP_TOKEN', $_ENV['AUTO_BACKUP_TOKEN'] ?? 'c3673591591474aa536a2585cebcbb89f7f7a070');

// Get action from either GET or POST
$action = $_REQUEST['action'] ?? null;

function respond($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

// Backup API now requires a separate backup admin login for manual operations
// BUT: auto_backup works without authentication (scheduled backup)
$auth = new BackupAuth($pdo);
$auth->ensureTableExists();

// Allow auto_backup to work without authentication
$isAutoBackupRequest = ($action === 'auto_backup');

if (!$isAutoBackupRequest && !$auth->isAuthenticated()) {
    respond(false, 'Backup authentication required', []);
}

// Directory setup
define('BACKUP_DIR', __DIR__ . '/../backups');
define('BACKUP_LOG', BACKUP_DIR . '/backup.log');
define('BACKUP_STATE', BACKUP_DIR . '/backup-state.json');

if (!is_dir(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
}

/**
 * Logging function
 */
function logBackup($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[$timestamp] [$level] $message\n";
    file_put_contents(BACKUP_LOG, $entry, FILE_APPEND | LOCK_EX);
}

/**
 * Get backup state (intervals, last backup, etc)
 */
function getBackupState() {
    if (!file_exists(BACKUP_STATE)) {
        return [
            'backup_interval' => '1day',  // Default: daily
            'last_backup' => null,
            'next_backup' => null,
            'backup_count' => 0,
            'total_size' => 0
        ];
    }
    return json_decode(file_get_contents(BACKUP_STATE), true);
}

/**
 * Save backup state
 */
function saveBackupState($state) {
    file_put_contents(BACKUP_STATE, json_encode($state, JSON_PRETTY_PRINT), LOCK_EX);
}

/**
 * Convert interval string to seconds
 */
function intervalToSeconds($interval) {
    $intervals = [
        '5min' => 300,
        '10min' => 600,
        '30min' => 1800,
        '1h' => 3600,
        '3h' => 10800,
        '5h' => 18000,
        '1day' => 86400,
        '2day' => 172800,
        '3day' => 259200,
        '1week' => 604800
    ];
    return $intervals[$interval] ?? 86400; // Default: 1 day
}

/**
 * Check if backup is due based on interval
 */
function isBackupDue($lastBackup, $interval) {
    if (!$lastBackup) {
        return true;
    }
    
    $lastTime = strtotime($lastBackup);
    $intervalSeconds = intervalToSeconds($interval);
    $nextDue = $lastTime + $intervalSeconds;
    
    return time() >= $nextDue;
}

/**
 * Export database to SQL file
 */
function exportDatabase() {
    global $pdo;
    
    try {
        // Get all tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        $sql = "-- Guild System Database Backup\n";
        $sql .= "-- Exported: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            $sql .= "-- Table: $table\n";
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            
            // Get CREATE TABLE statement
            $createResult = $pdo->query("SHOW CREATE TABLE `$table`");
            $createRow = $createResult->fetch(PDO::FETCH_ASSOC);
            $sql .= $createRow['Create Table'] . ";\n\n";
            
            // Get table data
            $dataResult = $pdo->query("SELECT * FROM `$table`");
            $rows = $dataResult->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($rows as $row) {
                $columns = implode("`, `", array_keys($row));
                $values = implode("', '", array_map(function($v) use ($pdo) {
                    return $pdo->quote($v);
                }, array_values($row)));
                
                $sql .= "INSERT INTO `$table` (`$columns`) VALUES ($values);\n";
            }
            
            $sql .= "\n";
        }
        
        return $sql;
    } catch (Exception $e) {
        logBackup("Database export failed: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Create backup file
 */
function createBackup($manual = false) {
    try {
        logBackup("Starting " . ($manual ? "manual" : "automatic") . " backup...");
        
        // Export database
        $dbExport = exportDatabase();
        if (!$dbExport) {
            return false;
        }
        
        // Create backup filename with timestamp
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "backup_" . $timestamp . ".sql";
        $filepath = BACKUP_DIR . '/' . $filename;
        
        // Save backup file
        if (!file_put_contents($filepath, $dbExport)) {
            logBackup("Failed to write backup file: $filepath", 'ERROR');
            return false;
        }
        
        $filesize = filesize($filepath);
        logBackup("Backup created successfully: $filename (" . formatBytes($filesize) . ")");
        
        return [
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => $filesize,
            'timestamp' => $timestamp
        ];
    } catch (Exception $e) {
        logBackup("Backup creation failed: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Get all backups
 */
function getBackups() {
    $backups = [];
    
    if (!is_dir(BACKUP_DIR)) {
        return $backups;
    }
    
    $files = array_filter(scandir(BACKUP_DIR), function($file) {
        return strpos($file, 'backup_') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'sql';
    });
    
    // Sort by date, newest first
    usort($files, function($a, $b) {
        return filemtime(BACKUP_DIR . '/' . $b) - filemtime(BACKUP_DIR . '/' . $a);
    });
    
    foreach ($files as $file) {
        $filepath = BACKUP_DIR . '/' . $file;
        $backups[] = [
            'filename' => $file,
            'size' => filesize($filepath),
            'created' => date('Y-m-d H:i:s', filemtime($filepath)),
            'created_timestamp' => filemtime($filepath)
        ];
    }
    
    return $backups;
}

/**
 * Format bytes to human readable
 */
function formatBytes($bytes) {
    $sizes = ['B', 'KB', 'MB', 'GB'];
    if ($bytes == 0) return '0 B';
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $sizes[$i];
}

/**
 * Get backup statistics
 */
function getStats() {
    $backups = getBackups();
    $totalSize = array_sum(array_column($backups, 'size'));
    $state = getBackupState();
    
    return [
        'total_backups' => count($backups),
        'total_size' => $totalSize,
        'last_backup' => $backups[0]['created'] ?? null,
        'backup_interval' => $state['backup_interval'],
        'backup_directory' => BACKUP_DIR,
        'backups' => $backups
    ];
}

// ==================== ROUTES ====================

// List backups
if ($action === 'list') {
    $stats = getStats();
    $backups = array_map(function($backup) {
        return [
            'filename' => $backup['filename'],
            'size' => formatBytes($backup['size']),
            'date' => $backup['created']
        ];
    }, $stats['backups']);
    
    respond(true, "Backups retrieved", [
        'backups' => $backups,
        'backup_count' => $stats['total_backups'],
        'total_size' => formatBytes($stats['total_size']),
        'last_backup' => $stats['last_backup'] ?? 'Never'
    ]);
}

// Manual backup trigger (also handle 'create' from frontend)
elseif ($action === 'manual_backup' || $action === 'create') {
    $backup = createBackup(true);
    
    if ($backup) {
        $state = getBackupState();
        $state['last_backup'] = date('Y-m-d H:i:s');
        saveBackupState($state);
        
        respond(true, "Backup created successfully: " . $backup['filename'], $backup);
    } else {
        respond(false, "Backup failed. Check logs for details.");
    }
}

// Set backup interval
elseif ($action === 'set_interval') {
    $interval = $_POST['interval'] ?? '1day';
    $validIntervals = ['5min', '10min', '30min', '1h', '3h', '5h', '1day', '2day', '3day', '1week'];
    
    if (!in_array($interval, $validIntervals)) {
        respond(false, "Invalid interval");
    }
    
    $state = getBackupState();
    $state['backup_interval'] = $interval;
    $state['next_backup'] = date('Y-m-d H:i:s', time() + intervalToSeconds($interval));
    saveBackupState($state);
    
    logBackup("Backup interval changed to: $interval");
    respond(true, "Interval set to: $interval", $state);
}

// Check if backup is due
elseif ($action === 'check_interval') {
    $state = getBackupState();
    $due = isBackupDue($state['last_backup'], $state['backup_interval']);
    
    respond(true, "Backup status checked", [
        'is_due' => $due,
        'last_backup' => $state['last_backup'],
        'interval' => $state['backup_interval'],
        'next_backup' => $state['next_backup']
    ]);
}

// Auto backup (triggered periodically)
elseif ($action === 'auto_backup') {
    $state = getBackupState();
    
    if (isBackupDue($state['last_backup'], $state['backup_interval'])) {
        $backup = createBackup(false);
        
        if ($backup) {
            $state['last_backup'] = date('Y-m-d H:i:s');
            $state['next_backup'] = date('Y-m-d H:i:s', time() + intervalToSeconds($state['backup_interval']));
            saveBackupState($state);
            
            respond(true, "Auto-backup completed", $backup);
        } else {
            respond(false, "Auto-backup failed");
        }
    } else {
        respond(true, "Backup not due yet", ['next_backup' => $state['next_backup']]);
    }
}

// Get statistics
elseif ($action === 'get_stats') {
    $stats = getStats();
    respond(true, "Statistics retrieved", $stats);
}

// Get backup log
elseif ($action === 'get_log') {
    $lines = file_exists(BACKUP_LOG) ? file(BACKUP_LOG) : [];
    $lastLines = array_slice($lines, -100); // Last 100 lines
    
    respond(true, "Log retrieved", [
        'log' => implode('', $lastLines)
    ]);
}

// Delete backup (handle both 'delete_backup' and 'delete' from frontend)
elseif ($action === 'delete_backup' || $action === 'delete') {
    $filename = $_POST['file'] ?? $_POST['filename'] ?? $_GET['file'] ?? null;
    
    if (!$filename || strpos($filename, 'backup_') !== 0) {
        respond(false, "Invalid filename");
    }
    
    $filepath = BACKUP_DIR . '/' . $filename;
    
    if (!file_exists($filepath)) {
        respond(false, "Backup file not found");
    }
    
    if (unlink($filepath)) {
        logBackup("Backup deleted: $filename");
        respond(true, "Backup deleted successfully");
    } else {
        respond(false, "Failed to delete backup");
    }
}

// Download backup
elseif ($action === 'download' && isset($_GET['file'])) {
    $filename = basename($_GET['file']);
    $filepath = BACKUP_DIR . '/' . $filename;
    
    if (!file_exists($filepath) || strpos($filename, 'backup_') !== 0) {
        http_response_code(404);
        die('File not found');
    }
    
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    
    readfile($filepath);
    exit;
}

else {
    respond(false, "Unknown action: $action");
}
?>
