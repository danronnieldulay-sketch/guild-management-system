<?php
require_once __DIR__ . '/start_session.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $ign = trim($_POST['ign']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (strlen($username) < 3 || strlen($password) < 6) {
        echo "Invalid username or password length.";
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo "Username already exists.";
        exit;
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, ign, email, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $ign, $email, $hashed]);

    $_SESSION['user_id'] = $pdo->lastInsertId();
    header("Location: dashboard.php");
    exit;
}
?>
