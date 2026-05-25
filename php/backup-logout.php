<?php
session_start();

// Clear backup admin session
if (isset($_SESSION['backup_admin_id'])) {
    unset($_SESSION['backup_admin_id']);
}
if (isset($_SESSION['backup_admin_username'])) {
    unset($_SESSION['backup_admin_username']);
}

// Destroy session
session_destroy();

// Redirect to login page
header("Location: /backup-login.php");
exit;
?>
