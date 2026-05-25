<?php
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/backup-auth.php';

$auth = new BackupAuth($pdo);
$auth->ensureTableExists();

if (!$auth->isAuthenticated()) {
    header('Location: backup-login.php');
    exit;
}

header('Content-Type: text/html; charset=utf-8');
readfile(__DIR__ . '/admin-backup.html');
exit;
