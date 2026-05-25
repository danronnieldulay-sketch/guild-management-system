<?php
/**
 * Backup Authentication Handler
 * Simple session-based authentication for backup admin panel
 */

class BackupAuth {
    private $pdo;
    private $tableName = 'backup_admins';

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Ensure the backup_admins table exists
     */
    public function ensureTableExists() {
        try {
            $this->pdo->query("CREATE TABLE IF NOT EXISTS `{$this->tableName}` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Exception $e) {
            // ignore
        }
    }

    /**
     * Register a new backup admin
     */
    public function registerAdmin($username, $password) {
        if (strlen($username) < 3) {
            return ['success' => false, 'message' => 'Username must be at least 3 characters'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters'];
        }

        try {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $this->pdo->prepare("INSERT INTO `{$this->tableName}` (username, password_hash) VALUES (?, ?)");
            $stmt->execute([$username, $passwordHash]);

            return ['success' => true, 'message' => 'Admin account created successfully'];
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return ['success' => false, 'message' => 'Username already exists'];
            }
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    /**
     * Login backup admin
     */
    public function login($username, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, password_hash FROM `{$this->tableName}` WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin || !password_verify($password, $admin['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid username or password'];
            }

            // Update last login
            $updateStmt = $this->pdo->prepare("UPDATE `{$this->tableName}` SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$admin['id']]);

            // Set session
            require_once __DIR__ . '/start_session.php';

            $_SESSION['backup_admin_id'] = $admin['id'];
            $_SESSION['backup_admin_username'] = $username;
            $_SESSION['backup_admin_login_time'] = time();

            return ['success' => true, 'message' => 'Login successful'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        require_once __DIR__ . '/start_session.php';
        return isset($_SESSION['backup_admin_id']) && isset($_SESSION['backup_admin_username']);
    }

    /**
     * Get admin info
     */
    public function getAdminInfo() {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return [
            'id' => $_SESSION['backup_admin_id'],
            'username' => $_SESSION['backup_admin_username'],
            'login_time' => $_SESSION['backup_admin_login_time'] ?? null
        ];
    }

    /**
     * Logout
     */
    public function logout() {
        require_once __DIR__ . '/start_session.php';
        unset($_SESSION['backup_admin_id'], $_SESSION['backup_admin_username'], $_SESSION['backup_admin_login_time']);
        session_regenerate_id(true);
    }
}
