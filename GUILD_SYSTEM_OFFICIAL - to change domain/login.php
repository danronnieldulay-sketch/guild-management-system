<?php
require_once 'php/start_session.php';
require_once 'php/db.php';
require_once 'php/classes/User.php';

$userObj = new User($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $result = $userObj->login($username, $password);
        if ($result['success']) {
            header("Location: dashboard.php");
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Guild Management</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <button class="back-button" onclick="window.location.href='index.php'">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back
            </button>

            <div class="form-header">
                <svg class="icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                    <path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
                <h2>Login to Your Account</h2>
                <p class="subtitle">Enter your credentials to continue</p>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" required placeholder="Enter your username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required placeholder="Enter your password">
                </div>

                <?php if (isset($error)): ?>
                    <div class="error-message" style="display: block;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <button type="submit" class="submit-button">Login</button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="signup.php" class="link-button">Create Account</a></p>
            </div>
        </div>
    </div>
</body>
</html>