<?php
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/backup-auth.php';

$auth = new BackupAuth($pdo);
$auth->ensureTableExists();

if ($auth->isAuthenticated()) {
    $auth->logout();
}

header('Location: backup-login.php');
exit;
