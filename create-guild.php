<?php
require_once 'php/start_session.php';
require_once 'php/db.php';
require_once 'php/classes/User.php';
require_once 'php/classes/Guild.php';

$userObj = new User($pdo);
$userObj->requireLogin();
$guildObj = new Guild($pdo);

$formError = null;
$formValues = [
    'guildName' => '',
    'game' => '',
    'leaderIGN' => '',
    'description' => '',
    'requirements' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formValues['guildName'] = trim($_POST['guildName'] ?? '');
    $formValues['game'] = trim($_POST['game'] ?? '');
    $formValues['leaderIGN'] = trim($_POST['leaderIGN'] ?? '');
    $formValues['description'] = trim($_POST['description'] ?? '');
    $formValues['requirements'] = trim($_POST['requirements'] ?? '');

    if ($formValues['guildName'] === '' || $formValues['game'] === '' || $formValues['leaderIGN'] === '' || $formValues['description'] === '') {
        $formError = 'Please fill in all required fields.';
    } else {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $formError = 'Unable to verify current user. Please log in again.';
        } else {
            $result = $guildObj->createGuild(
                $_SESSION['user_id'],
                $formValues['guildName'],
                $formValues['game'],
                $formValues['leaderIGN'],
                $user['username'],
                $formValues['description'],
                $formValues['requirements']
            );
            if ($result['success']) {
                header('Location: my-guilds.php');
                exit;
            }
            $formError = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Guild</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <button type="button" class="back-button" onclick="navigateTo('dashboard.php')">
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
                <h2>Create Your Guild</h2>
                <p class="subtitle">Establish your legacy and lead your members</p>
            </div>

            <form id="createGuildForm" action="create-guild.php" method="POST">
                <?php if (!empty($formError)): ?>
                    <div class="error-message" style="display: block; margin-bottom: 1rem;">
                        <?php echo htmlspecialchars($formError); ?>
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Guild Name *</label>
                    <input type="text" name="guildName" required placeholder="Enter guild name" value="<?php echo htmlspecialchars($formValues['guildName']); ?>">
                </div>

                <div class="form-group">
                    <label>Game *</label>
                    <input type="text" name="game" required placeholder="Enter your game name" value="<?php echo htmlspecialchars($formValues['game']); ?>">
                </div>

                <div class="form-group">
                    <label>Your IGN (In-Game Name) *</label>
                    <input type="text" name="leaderIGN" required placeholder="Your in-game name" value="<?php echo htmlspecialchars($formValues['leaderIGN']); ?>">
                </div>

                <div class="form-group">
                    <label>Guild Description *</label>
                    <textarea name="description" required placeholder="Describe your guild's goals and culture" rows="4"><?php echo htmlspecialchars($formValues['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Member Requirements</label>
                    <textarea name="requirements" placeholder="Minimum level, activity requirements, etc." rows="4"><?php echo htmlspecialchars($formValues['requirements']); ?></textarea>
                </div>

                <button type="submit" class="submit-button">Create Guild</button>
            </form>
        </div>
    </div>

    <script src="app.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', async () => {
        initializeApp();
        await loadUserInfo();
        await requireLogin();
    });
    </script>
</body>
</html>
