<?php
/**
 * Firebase API Wrapper
 * Bridges frontend requests to Firebase Realtime Database
 */

require_once __DIR__ . '/start_session.php';
require_once 'db.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

require_once __DIR__ . '/firebase/FirebaseChat.php';

function sendResponse($result) {
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

try {
    $firebaseChat = new FirebaseChat();
} catch (Exception $e) {
    sendResponse([
        'success' => false,
        'message' => 'Firebase initialization failed: ' . $e->getMessage()
    ]);
}

$action = $_GET['action'] ?? null;

switch ($action) {
    // ================== CHAT ACTIONS ==================
    
    case "chat/send":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(['success' => false, 'message' => 'Not logged in']);
            break;
        }
        
        $input = json_decode(file_get_contents("php://input"), true);
        
        if (!$input || !isset($input['guildId'])) {
            sendResponse(['success' => false, 'message' => 'Missing guildId']);
            break;
        }
        
        // Accept both 'message' and 'text' field names for compatibility
        $messageText = $input['message'] ?? $input['text'] ?? null;
        
        if (!$messageText) {
            sendResponse(['success' => false, 'message' => 'Missing message or text field']);
            break;
        }
        
        $guildId = $input['guildId'];
        $message = trim($messageText);
        
        if (empty($message)) {
            sendResponse(['success' => false, 'message' => 'Message cannot be empty']);
            break;
        }
        
        // Get username from session or database
        $username = $input['username'] ?? $_SESSION['username'] ?? null;
        
        if (!$username) {
            try {
                $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $username = $user['username'] ?? 'User_' . $_SESSION['user_id'];
            } catch (Exception $e) {
                $username = 'User_' . $_SESSION['user_id'];
            }
        }
        
        $result = $firebaseChat->sendMessage(
            $guildId,
            $_SESSION['user_id'],
            $username,
            $message
        );
        
        sendResponse($result);
        break;
    
    case "chat/list":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(['success' => false, 'message' => 'Not logged in']);
            break;
        }
        
        $guildId = $_GET['guildId'] ?? null;
        
        if (!$guildId) {
            sendResponse(['success' => false, 'message' => 'Missing guildId']);
            break;
        }
        
        try {
            $messages = $firebaseChat->getGuildMessages($guildId);
            sendResponse([
                'success' => true,
                'data' => $messages
            ]);
        } catch (Exception $e) {
            sendResponse([
                'success' => false,
                'message' => 'Error fetching messages: ' . $e->getMessage()
            ]);
        }
        break;
    
    case "chat/delete":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(['success' => false, 'message' => 'Not logged in']);
            break;
        }
        
        $input = json_decode(file_get_contents("php://input"), true);
        
        if (!$input || !isset($input['messageId']) || !isset($input['guildId'])) {
            sendResponse(['success' => false, 'message' => 'Missing required fields']);
            break;
        }
        
        // TODO: Verify user is guild admin before allowing delete
        $result = $firebaseChat->deleteMessage(
            $input['guildId'],
            $input['messageId']
        );
        
        sendResponse($result);
        break;
    
    case "chat/update":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(['success' => false, 'message' => 'Not logged in']);
            break;
        }
        
        $input = json_decode(file_get_contents("php://input"), true);
        
        if (!$input || !isset($input['messageId']) || !isset($input['guildId']) || !isset($input['text'])) {
            sendResponse(['success' => false, 'message' => 'Missing required fields']);
            break;
        }
        
        // TODO: Verify user is message owner before allowing update
        $result = $firebaseChat->updateMessage(
            $input['guildId'],
            $input['messageId'],
            $input['text']
        );
        
        sendResponse($result);
        break;
    
    case "chat/clear":
        if (!isset($_SESSION['user_id'])) {
            sendResponse(['success' => false, 'message' => 'Not logged in']);
            break;
        }
        
        $input = json_decode(file_get_contents("php://input"), true);
        $guildId = $input['guildId'] ?? null;
        
        if (!$guildId) {
            sendResponse(['success' => false, 'message' => 'Missing guildId']);
            break;
        }
        
        // TODO: Verify user is guild admin
        $result = $firebaseChat->clearGuildChat($guildId);
        
        sendResponse($result);
        break;
    
    default:
        sendResponse([
            'success' => false,
            'message' => 'Unknown action: ' . $action,
            'available_actions' => [
                'chat/send' => 'Send a message to guild chat',
                'chat/list' => 'Get all messages for a guild',
                'chat/delete' => 'Delete a message (admin)',
                'chat/update' => 'Update a message (owner)',
                'chat/clear' => 'Clear all guild chat (admin)'
            ]
        ]);
}
?>
