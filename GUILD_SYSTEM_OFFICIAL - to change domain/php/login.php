<?php
require_once __DIR__ . '/start_session.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        
        // Set user status to "Online" in all their guild memberships
        $updateStatus = $pdo->prepare("UPDATE members SET status = 'Online', last_active = NOW() WHERE user_id = ?");
        $updateStatus->execute([$user['id']]);
        
        header("Location: dashboard.php");
        exit;
    } else {
        echo "Invalid username or password.";
    }
}
?>
