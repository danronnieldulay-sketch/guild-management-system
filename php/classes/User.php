<?php
class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function register($username, $email, $password) {
        if (strlen($username) < 3 || strlen($password) < 6) {
            return ["success" => false, "message" => "Invalid username or password length."];
        }

        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ["success" => false, "message" => "Username already exists."];
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashed]);

        $_SESSION['user_id'] = $this->pdo->lastInsertId();
        return ["success" => true, "message" => "Registration successful."];
    }

    public function login($username, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            return ["success" => false, "message" => "Invalid username or password."];
        }

        $_SESSION['user_id'] = $user['id'];
        
        // Set user status to "Online" in all guild memberships
        $updateStatus = $this->pdo->prepare("UPDATE members SET status = 'Online', last_active = NOW() WHERE user_id = ?");
        $updateStatus->execute([$user['id']]);
        
        return ["success" => true, "message" => "Login successful.", "data" => $user];
    }

    public function logout() {
        // Set user status to "Offline" before destroying session
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            $updateStatus = $this->pdo->prepare("UPDATE members SET status = 'Offline', last_active = NOW() WHERE user_id = ?");
            $updateStatus->execute([$userId]);
        }
        
        session_destroy();
        return ["success" => true, "message" => "Logged out successfully."];
    }

    public function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php");
            exit;
        }       
    }

    // ---------------- PASSWORD MANAGEMENT ----------------
    public function changePassword($userId, $currentPassword, $newPassword) {
        // Fetch the current hashed password from the database
        $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ["success" => false, "message" => "User not found."];
        }

        // Verify the current password
        if (!password_verify($currentPassword, $user['password'])) {
            return ["success" => false, "message" => "Current password is incorrect."];
        }

        // Hash the new password
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update the password in the database
        $update = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->execute([$hashed, $userId]);

        return ["success" => true, "message" => "Password updated successfully."];
    }
}
?>
