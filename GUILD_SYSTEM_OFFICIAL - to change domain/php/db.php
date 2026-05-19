<?php
// db.php - Environment-driven database connection for Guild Management System

class Database {
    private $host;
    private $port;
    private $dbname;
    private $username;
    private $password;
    private $pdo;

    public function __construct() {
        $config = $this->loadConfig();

        $this->host = $config['DB_HOST'] ?? 'localhost';
        $this->port = $config['DB_PORT'] ?? '3306';
        $this->dbname = $config['DB_NAME'] ?? 'guild_management';
        $this->username = $config['DB_USER'] ?? 'root';
        $this->password = $config['DB_PASSWORD'] ?? '';

        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset=utf8",
                $this->username,
                $this->password
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage() . " (host={$this->host};port={$this->port})");
        }
    }

    private function loadConfig(): array {
        $rootPath = dirname(__DIR__);
        $envFile = $rootPath . '/.env';
        $config = [];

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
            if ($line === '' || substr($line, 0, 1) === '#') {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2) + ['', '']);
                if ($key !== '') {
                    $config[$key] = trim($value, "\"'");
                }
            }
        }

        foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'] as $var) {
            $value = getenv($var);
            if ($value !== false) {
                $config[$var] = $value;
            }
        }

        return $config;
    }

    public function getConnection() {
        return $this->pdo;
    }
}

// Create a shared PDO instance for all pages
$db = new Database();
$pdo = $db->getConnection();

// ========== BULLETPROOF AUTO-BACKUP (Runs on Every Request) ==========
// This is foolproof - runs regardless of user actions, login status, or admin panel
try {
    $backupDir = dirname(__DIR__) . '/backups';
    $backupStateFile = $backupDir . '/backup-state.json';
    $backupLogFile = $backupDir . '/backup.log';

    // Ensure backup directory exists
    if (!is_dir($backupDir)) {
        @mkdir($backupDir, 0755, true);
    }

    // Get current backup state
    $backupState = [];
    if (file_exists($backupStateFile)) {
        $content = @file_get_contents($backupStateFile);
        $backupState = $content ? @json_decode($content, true) : [];
    }

    $backupInterval = $backupState['backup_interval'] ?? '1day';
    $lastBackup = $backupState['last_backup'] ?? null;

    // Convert interval to seconds
    $intervalSeconds = [
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
    ][$backupInterval] ?? 86400;

    // Check if backup is due (simple time-based check)
    $isBackupDue = true;
    if ($lastBackup) {
        $lastBackupTime = @strtotime($lastBackup);
        if ($lastBackupTime && (time() < $lastBackupTime + $intervalSeconds)) {
            $isBackupDue = false;
        }
    }

    // Create backup if due
    if ($isBackupDue && $backupInterval !== 'never') {
        // Try to create backup silently
        @file_put_contents($backupLogFile, "[" . date('Y-m-d H:i:s') . "] Starting automatic backup (interval: $backupInterval)...\n", FILE_APPEND | LOCK_EX);

        try {
            // Get all tables
            $tables = @$pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

            if ($tables && is_array($tables) && count($tables) > 0) {
                $sql = "-- Guild System Database Backup\n";
                $sql .= "-- Auto-generated: " . date('Y-m-d H:i:s') . "\n";
                $sql .= "-- Interval: $backupInterval\n\n";

                foreach ($tables as $table) {
                    $sql .= "-- Table: $table\n";
                    $sql .= "DROP TABLE IF EXISTS `$table`;\n";

                    // Get CREATE TABLE
                    $createResult = @$pdo->query("SHOW CREATE TABLE `$table`");
                    if ($createResult) {
                        $createRow = $createResult->fetch(PDO::FETCH_ASSOC);
                        if ($createRow) {
                            $sql .= $createRow['Create Table'] . ";\n\n";
                        }
                    }

                    // Get data
                    $dataResult = @$pdo->query("SELECT * FROM `$table`");
                    if ($dataResult) {
                        $rows = $dataResult->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($rows as $row) {
                            $columns = implode("`, `", array_keys($row));
                            $values = implode("', '", array_map(function($v) use ($pdo) {
                                return str_replace("'", "''", $v);
                            }, array_values($row)));
                            $sql .= "INSERT INTO `$table` (`$columns`) VALUES ('$values');\n";
                        }
                    }
                    $sql .= "\n";
                }

                // Save backup file
                $timestamp = date('Y-m-d_H-i-s');
                $backupFile = $backupDir . '/backup_' . $timestamp . '.sql';

                if (@file_put_contents($backupFile, $sql, LOCK_EX)) {
                    $filesize = filesize($backupFile);
                    $fileSizeKB = round($filesize / 1024, 2);

                    // Update backup state
                    $backupState['last_backup'] = date('Y-m-d H:i:s');
                    $backupState['next_backup'] = date('Y-m-d H:i:s', time() + $intervalSeconds);
                    @file_put_contents($backupStateFile, json_encode($backupState, JSON_PRETTY_PRINT), LOCK_EX);

                    @file_put_contents($backupLogFile, "[" . date('Y-m-d H:i:s') . "] ✓ SUCCESS - Backup created: $backupFile ($fileSizeKB KB)\n", FILE_APPEND | LOCK_EX);
                } else {
                    @file_put_contents($backupLogFile, "[" . date('Y-m-d H:i:s') . "] ✗ ERROR - Could not write backup file\n", FILE_APPEND | LOCK_EX);
                }
            }
        } catch (Exception $e) {
            @file_put_contents($backupLogFile, "[" . date('Y-m-d H:i:s') . "] ✗ ERROR - " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        }
    }
} catch (Exception $e) {
    // Silently fail - don't interrupt page load
    @file_put_contents(dirname(__DIR__) . '/backups/backup.log', "[" . date('Y-m-d H:i:s') . "] CRITICAL ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
}
?>
