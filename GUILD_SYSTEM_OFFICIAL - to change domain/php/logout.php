<?php
require_once __DIR__ . '/start_session.php';
require_once 'db.php';

// Set user status to "Offline" before logging out
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $updateStatus = $pdo->prepare("UPDATE members SET status = 'Offline', last_active = NOW() WHERE user_id = ?");
    $updateStatus->execute([$userId]);
}

session_destroy();
header("Location: login.php");
exit;
?>
