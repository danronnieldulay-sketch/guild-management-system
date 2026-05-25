<?php
require_once __DIR__ . '/start_session.php';
require_once 'db.php';
require_once 'classes/User.php';
require_once 'classes/Guild.php';

$userObj = new User($pdo);
$guildObj = new Guild($pdo);

function sendResponse($result) {
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

$action = $_GET['action'] ?? null;

switch ($action) {
    // ---------------- USER ACTIONS ----------------
    case "user/register":
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            if (!$input) {
                sendResponse(["success" => false, "message" => "Invalid JSON input"]);
                break;
            }
            error_log("Register input: " . print_r($input, true));
            $result = $userObj->register(
                $input['username'],
                $input['email'],
                $input['password']
            );
            error_log("Register result: " . print_r($result, true));
            sendResponse($result);
        } catch (Exception $e) {
            error_log("Register exception: " . $e->getMessage());
            sendResponse(["success" => false, "message" => "Registration failed: " . $e->getMessage()]);
        }
        break;

    case "user/login":
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            if (!$input) {
                sendResponse(["success" => false, "message" => "Invalid JSON input"]);
                break;
            }
            error_log("Login input: " . print_r($input, true));
            $result = $userObj->login(
                $input['username'],
                $input['password']
            );
            error_log("Login result: " . print_r($result, true));
            sendResponse($result);
        } catch (Exception $e) {
            error_log("Login exception: " . $e->getMessage());
            sendResponse(["success" => false, "message" => "Login failed: " . $e->getMessage()]);
        }
        break;

    case "user/logout":
        sendResponse($userObj->logout());
        break;

    case "user/login-status":
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("SELECT id, username, created_at AS createdAt FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                sendResponse(["success" => true, "data" => $user]);
            } else {
                sendResponse(["success" => false, "message" => "User not found."]);
            }
        } else {
            sendResponse(["success" => false, "message" => "Not logged in."]);
        }
        break;

    // ---------------- GUILD ACTIONS ----------------
    case "guild/create":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(["success" => false, "message" => "Not logged in."]);
            break;
        }
        $input = $_POST;
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            sendResponse(["success" => false, "message" => "Unable to identify current user."]);
            break;
        }
        sendResponse($guildObj->createGuild(
            $_SESSION['user_id'],
            $input['guildName'],
            $input['game'],
            $input['leaderIGN'],
            $user['username'],
            $input['description'],
            $input['requirements']
        ));
        break;

    case "guild/list":
        sendResponse($guildObj->listGuilds());
        break;

    case "guild/get":
        $guildId = $_GET['guildId'] ?? null;
        // For admin dashboard access, pass current user ID for permission check
        $userId = $_GET['admin'] === 'true' ? ($_SESSION['user_id'] ?? null) : null;
        sendResponse($guildObj->getGuildById($guildId, $userId));
        break;

    case "user/memberships":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(["success" => false, "message" => "Not logged in."]);
            break;
        }
        sendResponse($guildObj->getGuildsByMember($_SESSION['user_id']));
        break;

    case "user/applications":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(["success" => false, "message" => "Not logged in."]);
            break;
        }
        sendResponse($guildObj->listApplicationsByUser($_SESSION['user_id']));
        break;

    // ---------------- APPLICATION ACTIONS ----------------
    case "application/submit":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(["success" => false, "message" => "Not logged in."]);
            break;
        }
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            if (!$input) {
                sendResponse(["success" => false, "message" => "Invalid JSON input"]);
                break;
            }

            $guildId = $input['guildId'] ?? null;
            $message = $input['message'] ?? '';
            $ign = $input['ign'] ?? null;
            sendResponse($guildObj->submitApplication($guildId, $_SESSION['user_id'], $message, $ign));
        } catch (Exception $e) {
            sendResponse(["success" => false, "message" => "Error submitting application: " . $e->getMessage()]);
        }
        break;

    case "application/approve":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(["success" => false, "message" => "Not logged in."]);
            break;
        }

        try {
            $input = json_decode(file_get_contents("php://input"), true);
            if (!$input) {
                sendResponse(["success" => false, "message" => "Invalid JSON input"]);
                break;
            }

            $applicationId = $input['applicationId'] ?? null;
            sendResponse($guildObj->approveApplication($applicationId, $_SESSION['user_id']));
        } catch (Exception $e) {
            sendResponse(["success" => false, "message" => "Error approving application: " . $e->getMessage()]);
        }
        break;

    case "application/reject":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(["success" => false, "message" => "Not logged in."]);
            break;
        }

        try {
            $input = json_decode(file_get_contents("php://input"), true);
            if (!$input) {
                sendResponse(["success" => false, "message" => "Invalid JSON input"]);
                break;
            }

            $applicationId = $input['applicationId'] ?? null;
            sendResponse($guildObj->rejectApplication($applicationId, $_SESSION['user_id']));
        } catch (Exception $e) {
            sendResponse(["success" => false, "message" => "Error rejecting application: " . $e->getMessage()]);
        }
        break;

    case "application/list":
        $userId = $_GET['userId'] ?? null;
        if (!$userId) {
            sendResponse(["success" => false, "message" => "User ID required."]);
        }
        $stmt = $pdo->prepare("SELECT a.*, g.name AS guildName 
                               FROM applications a 
                               JOIN guilds g ON a.guild_id = g.id 
                               WHERE a.user_id = ?");
        $stmt->execute([$userId]);
        $apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendResponse(["success" => true, "data" => $apps]);
        break;

    // ---------------- GUILD MEMBERSHIP ACTIONS ----------------
    case "guild/leave":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(["success" => false, "message" => "Not logged in."]);
            break;
        }
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $guildId = $input['guildId'] ?? null;
            if (!$guildId) {
                sendResponse(["success" => false, "message" => "Guild ID required."]);
                break;
            }
            sendResponse($guildObj->leaveGuild($guildId, $_SESSION['user_id']));
        } catch (Exception $e) {
            sendResponse(["success" => false, "message" => "Error leaving guild: " . $e->getMessage()]);
        }
        break;

    case "guild/disband":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(["success" => false, "message" => "Not logged in."]);
            break;
        }
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $guildId = $input['guildId'] ?? null;
            if (!$guildId) {
                sendResponse(["success" => false, "message" => "Guild ID required."]);
                break;
            }
            sendResponse($guildObj->disbandGuild($guildId, $_SESSION['user_id']));
        } catch (Exception $e) {
            sendResponse(["success" => false, "message" => "Error disbanding guild: " . $e->getMessage()]);
        }
        break;

    // ---------------- MEMBER ACTIONS ----------------
    case "member/update-authority":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(["success" => false, "message" => "Not logged in."]);
            break;
        }
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $memberId = $input['memberId'] ?? null;
            $authority = $input['authority'] ?? null;
            
            if (!$memberId || !$authority) {
                sendResponse(["success" => false, "message" => "Member ID and authority required."]);
                break;
            }

            sendResponse($guildObj->updateMemberAuthority($memberId, $authority, $_SESSION['user_id']));
        } catch (Exception $e) {
            sendResponse(["success" => false, "message" => "Error updating authority: " . $e->getMessage()]);
        }
        break;

    case "member/kick":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(["success" => false, "message" => "Not logged in."]);
            break;
        }
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $memberId = $input['memberId'] ?? null;
            if (!$memberId) {
                sendResponse(["success" => false, "message" => "Member ID required."]);
                break;
            }

            sendResponse($guildObj->kickMember($memberId, $_SESSION['user_id']));
        } catch (Exception $e) {
            sendResponse(["success" => false, "message" => "Error kicking member: " . $e->getMessage()]);
        }
        break;

    case "member/update-ign":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(["success" => false, "message" => "Not logged in."]);
            break;
        }
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $memberId = $input['memberId'] ?? null;
            $newIGN = $input['ign'] ?? null;
            
            if (!$memberId || !$newIGN) {
                sendResponse(["success" => false, "message" => "Member ID and IGN required."]);
                break;
            }

            // Verify that the current user can update this member's IGN
            $stmt = $pdo->prepare("SELECT m.id, m.guild_id FROM members m WHERE m.id = ? AND m.user_id = ?");
            $stmt->execute([$memberId, $_SESSION['user_id']]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$member) {
                sendResponse(["success" => false, "message" => "Member not found or unauthorized."]);
                break;
            }

            // Update the IGN
            $updateStmt = $pdo->prepare("UPDATE members SET ign = ? WHERE id = ?");
            $updateStmt->execute([$newIGN, $memberId]);
            
            sendResponse(["success" => true, "message" => "IGN updated successfully."]);
        } catch (Exception $e) {
            sendResponse(["success" => false, "message" => "Error updating IGN: " . $e->getMessage()]);
        }
        break;

    // -------------------- ROLE ACTIONS --------------------
    case "role/create":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(["success" => false, "message" => "Not logged in."]);
            break;
        }
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            if (!$input) {
                sendResponse(["success" => false, "message" => "Invalid JSON input"]);
                break;
            }

            $guildId = $input['guildId'] ?? null;
            $roleName = $input['roleName'] ?? null;
            $maxLimit = $input['maxLimit'] ?? null;

            if (!$guildId || !$roleName) {
                sendResponse(["success" => false, "message" => "Guild ID and role name required."]);
                break;
            }

            $authStmt = $pdo->prepare("SELECT authority FROM members WHERE guild_id = ? AND user_id = ?");
            $authStmt->execute([$guildId, $_SESSION['user_id']]);
            $authRow = $authStmt->fetch(PDO::FETCH_ASSOC);
            $authority = $authRow['authority'] ?? null;
            if (!in_array($authority, ['Leader', 'Sub leader'], true)) {
                sendResponse(["success" => false, "message" => "Only guild leadership can add roles."]);
                break;
            }

            sendResponse($guildObj->createRole($guildId, $roleName, $maxLimit));
        } catch (Exception $e) {
            sendResponse(["success" => false, "message" => "Error creating role: " . $e->getMessage()]);
        }
        break;

    case "role/list":
        $guildId = $_GET['guildId'] ?? null;
        if (!$guildId) {
            sendResponse(["success" => false, "message" => "Guild ID required."]);
            break;
        }
        sendResponse($guildObj->getRolesByGuild($guildId));
        break;

    case "role/delete":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(["success" => false, "message" => "Not logged in."]);
            break;
        }
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $roleId = $input['roleId'] ?? null;
            $guildId = $input['guildId'] ?? null;

            if (!$roleId || !$guildId) {
                sendResponse(["success" => false, "message" => "Role ID and Guild ID required."]);
                break;
            }

            $authStmt = $pdo->prepare("SELECT authority FROM members WHERE guild_id = ? AND user_id = ?");
            $authStmt->execute([$guildId, $_SESSION['user_id']]);
            $authRow = $authStmt->fetch(PDO::FETCH_ASSOC);
            $authority = $authRow['authority'] ?? null;
            if (!in_array($authority, ['Leader', 'Sub leader'], true)) {
                sendResponse(["success" => false, "message" => "Only guild leadership can delete roles."]);
                break;
            }

            sendResponse($guildObj->deleteRole($roleId, $guildId));
        } catch (Exception $e) {
            sendResponse(["success" => false, "message" => "Error deleting role: " . $e->getMessage()]);
        }
        break;

    case "role/assign":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(["success" => false, "message" => "Not logged in."]);
            break;
        }
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $memberId = $input['memberId'] ?? null;
            $roleId = $input['roleId'] ?? null;

            if (!$memberId || !$roleId) {
                sendResponse(["success" => false, "message" => "Member ID and role ID required."]);
                break;
            }

            sendResponse($guildObj->assignRoleToMember($memberId, $roleId, $_SESSION['user_id']));
        } catch (Exception $e) {
            sendResponse(["success" => false, "message" => "Error assigning role: " . $e->getMessage()]);
        }
        break;

    case "role/remove":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(["success" => false, "message" => "Not logged in."]);
            break;
        }
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $memberId = $input['memberId'] ?? null;
            $roleId = $input['roleId'] ?? null;

            if (!$memberId || !$roleId) {
                sendResponse(["success" => false, "message" => "Member ID and role ID required."]);
                break;
            }

            sendResponse($guildObj->removeRoleFromMember($memberId, $roleId, $_SESSION['user_id']));
        } catch (Exception $e) {
            sendResponse(["success" => false, "message" => "Error removing role: " . $e->getMessage()]);
        }
        break;

    case "member/roles":
        $memberId = $_GET['memberId'] ?? null;
        if (!$memberId) {
            sendResponse(["success" => false, "message" => "Member ID required."]);
            break;
        }
        sendResponse($guildObj->getMemberRoles($memberId));
        break;

    case "chat/list":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(["success" => false, "message" => "Not logged in."]);
            break;
        }
        $guildId = $_GET['guildId'] ?? null;
        if (!$guildId) {
            sendResponse(["success" => false, "message" => "Guild ID required."]);
            break;
        }
        sendResponse($guildObj->getGuildChatMessages($guildId, $_SESSION['user_id']));
        break;

    case "chat/send":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(["success" => false, "message" => "Not logged in."]);
            break;
        }
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            if (!$input) {
                sendResponse(["success" => false, "message" => "Invalid JSON input"]);
                break;
            }

            $guildId = $input['guildId'] ?? null;
            $text = $input['text'] ?? '';
            sendResponse($guildObj->sendGuildChatMessage($guildId, $_SESSION['user_id'], $text));
        } catch (Exception $e) {
            sendResponse(["success" => false, "message" => "Error sending chat message: " . $e->getMessage()]);
        }
        break;

    // ---------------- PASSWORD ACTIONS ----------------
    case "user/change-password":
        $current = $_POST['currentPassword'] ?? '';
        $new = $_POST['newPassword'] ?? '';
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            sendResponse(["success" => false, "message" => "Not logged in."]);
        }
        sendResponse($userObj->changePassword($userId, $current, $new));
        break;

    default:
        sendResponse(["success" => false, "message" => "Invalid action."]);
}
?>
