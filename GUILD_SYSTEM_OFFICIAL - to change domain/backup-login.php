<?php
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/backup-auth.php';

$auth = new BackupAuth($pdo);
$auth->ensureTableExists();

if ($auth->isAuthenticated()) {
    header('Location: admin-backup.php');
    exit;
}

$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($action === 'register') {
        $result = $auth->registerAdmin($username, $password);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    } else {
        $result = $auth->login($username, $password);
        if ($result['success']) {
            header('Location: admin-backup.php');
            exit;
        }
        $message = $result['message'];
        $messageType = 'error';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Admin Login</title>
    <style>
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7fb; }
        .page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { width: 100%; max-width: 520px; background: white; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.08); padding: 30px; }
        h1 { margin-bottom: 10px; font-size: 28px; color: #2d3748; }
        p { color: #4a5568; margin-bottom: 20px; }
        .grid { display: grid; gap: 20px; }
        .form-group { display: grid; gap: 8px; }
        label { font-weight: 600; color: #2d3748; }
        input { width: 100%; padding: 12px 14px; border: 1px solid #cbd5e0; border-radius: 10px; font-size: 14px; box-sizing: border-box; transition: border-color 0.2s; }
        input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        button { padding: 12px 18px; border: none; border-radius: 10px; cursor: pointer; font-weight: 700; background: #667eea; color: white; transition: transform 0.2s ease, box-shadow 0.2s ease; font-size: 14px; }
        button:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3); }
        button:active { transform: translateY(0); }
        .message { padding: 14px 16px; border-radius: 10px; margin-bottom: 15px; }
        .message.info { background: #e8f0fe; color: #1e3a8a; }
        .message.success { background: #e6ffed; color: #166534; }
        .message.error { background: #fee2e2; color: #991b1b; }
        .tabs { display: flex; gap: 12px; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; }
        .tab-btn { background: none; border: none; padding: 12px 0; font-weight: 600; color: #a0aec0; cursor: pointer; border-bottom: 3px solid transparent; margin-bottom: -2px; transition: all 0.2s ease; }
        .tab-btn:hover { color: #667eea; }
        .tab-btn.active { color: #667eea; border-bottom-color: #667eea; }
        .form-container { display: none; }
        .form-container.active { display: grid; }
        .secondary { background: #edf2ff; color: #3730a3; padding: 12px 18px; border: none; border-radius: 10px; cursor: pointer; font-weight: 700; transition: all 0.2s; text-decoration: none; display: inline-block; }
        .secondary:hover { background: #dde2ff; transform: translateY(-1px); }
        .hint { margin-top: 20px; font-size: 13px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <h1>Backup Admin Access</h1>
            <p>Secure admin access for the backup manager. Use a separate admin account from your guild system login.</p>

            <?php if ($message): ?>
                <div class="message <?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="tabs">
                <button type="button" class="tab-btn active" data-tab="login">Sign In</button>
                <button type="button" class="tab-btn" data-tab="register">Create Account</button>
            </div>

            <div class="form-container active" id="login-form">
                <form method="post" class="grid">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label for="loginUsername">Admin username</label>
                        <input id="loginUsername" name="username" type="text" required autocomplete="username" placeholder="Enter admin username">
                    </div>
                    <div class="form-group">
                        <label for="loginPassword">Password</label>
                        <input id="loginPassword" name="password" type="password" required autocomplete="current-password" placeholder="Enter password">
                    </div>
                    <button type="submit">Sign in</button>
                </form>
            </div>

            <div class="form-container" id="register-form">
                <form method="post" class="grid">
                    <input type="hidden" name="action" value="register">
                    <div class="form-group">
                        <label for="registerUsername">New admin username</label>
                        <input id="registerUsername" name="username" type="text" required autocomplete="username" placeholder="Create a username">
                    </div>
                    <div class="form-group">
                        <label for="registerPassword">New password</label>
                        <input id="registerPassword" name="password" type="password" required autocomplete="new-password" placeholder="Create a strong password">
                    </div>
                    <button type="submit">Create admin account</button>
                </form>
            </div>

            <p class="hint">If you already have an admin account, use the sign in form above.</p>
        </div>
    </div>

    <script>
        // Tab switching functionality
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tab = btn.getAttribute('data-tab');
                
                // Update active tab button
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                // Update active form
                document.querySelectorAll('.form-container').forEach(form => form.classList.remove('active'));
                document.getElementById(tab + '-form').classList.add('active');
                
                // Clear form inputs
                document.getElementById(tab + '-form').querySelector('form').reset();
            });
        });
    </script>
</body>
</html>
