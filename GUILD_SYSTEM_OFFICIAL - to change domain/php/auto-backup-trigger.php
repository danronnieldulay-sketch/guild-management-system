<?php
/**
 * Auto-Backup Trigger
 * Silently checks and creates backups if they're due
 * Include this in your main pages to enable automatic backups
 * 
 * Usage: require_once __DIR__ . '/auto-backup-trigger.php';
 */

// Only run if there's a database connection and backup system is initialized
if (!function_exists('PDO') || !isset($pdo)) {
    return; // Skip if not in proper context
}

try {
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

    // ========== BACKUP CONFIGURATION ==========
    define('BACKUP_TRIGGER_DIR', __DIR__ . '/../backups');
    define('BACKUP_TRIGGER_STATE', BACKUP_TRIGGER_DIR . '/backup-state.json');
    define('BACKUP_TRIGGER_LOG', BACKUP_TRIGGER_DIR . '/backup.log');

    // Ensure backup directory exists
    if (!is_dir(BACKUP_TRIGGER_DIR)) {
        mkdir(BACKUP_TRIGGER_DIR, 0755, true);
    }

    /**
     * Get backup state
     */
    function getBackupTriggerState() {
        if (!file_exists(BACKUP_TRIGGER_STATE)) {
            return [
                'backup_interval' => '1day',
                'last_backup' => null,
                'next_backup' => null,
            ];
        }
        return json_decode(file_get_contents(BACKUP_TRIGGER_STATE), true) ?: [];
    }

    /**
     * Convert interval to seconds
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
        return $intervals[$interval] ?? 86400;
    }

    /**
     * Check if backup is due
     */
    function isBackupDueTrigger($lastBackup, $interval) {
        if (!$lastBackup) {
            return true;
        }
        
        $lastTime = strtotime($lastBackup);
        $intervalSeconds = intervalToSeconds($interval);
        $nextDue = $lastTime + $intervalSeconds;
        
        return time() >= $nextDue;
    }

    /**
     * Log auto-backup
     */
    function logBackupTrigger($message) {
        $timestamp = date('Y-m-d H:i:s');
        $entry = "[$timestamp] [AUTO-TRIGGER] $message\n";
        @file_put_contents(BACKUP_TRIGGER_LOG, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Trigger auto-backup if due
     */
    function triggerAutoBackupIfDue() {
        global $pdo;

        // Check if backup is due
        $state = getBackupTriggerState();
        if (!isBackupDueTrigger($state['last_backup'] ?? null, $state['backup_interval'] ?? '1day')) {
            return false; // Not due yet
        }

        try {
            logBackupTrigger("Backup is due, starting automatic backup...");

            // Get all tables
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

            $sql = "-- Guild System Database Backup\n";
            $sql .= "-- Exported: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Auto-triggered backup\n\n";

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

            // Create backup file
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backup_" . $timestamp . ".sql";
            $filepath = BACKUP_TRIGGER_DIR . '/' . $filename;

            if (!file_put_contents($filepath, $sql, LOCK_EX)) {
                logBackupTrigger("ERROR: Failed to write backup file: $filepath");
                return false;
            }

            $filesize = filesize($filepath);
            logBackupTrigger("SUCCESS: Backup created - $filename (" . round($filesize / 1024, 2) . " KB)");

            // Update state
            $state['last_backup'] = date('Y-m-d H:i:s');
            $state['next_backup'] = date('Y-m-d H:i:s', time() + intervalToSeconds($state['backup_interval']));
            @file_put_contents(BACKUP_TRIGGER_STATE, json_encode($state, JSON_PRETTY_PRINT), LOCK_EX);

            return true;

        } catch (Exception $e) {
            logBackupTrigger("ERROR: Backup failed - " . $e->getMessage());
            return false;
        }
    }

    // ========== TRIGGER AUTO-BACKUP IF DUE ==========
    // This runs silently in the background
    triggerAutoBackupIfDue();

} catch (Exception $e) {
    // Silently fail - don't interrupt page loading
    @file_put_contents(
        __DIR__ . '/../backups/backup.log',
        date('Y-m-d H:i:s') . " [TRIGGER-ERROR] " . $e->getMessage() . "\n",
        FILE_APPEND
    );
}
?>
