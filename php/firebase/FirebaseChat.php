<?php
/**
 * Firebase Chat Service
 * Integrates Firebase Realtime Database for guild chat
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Kreait\Firebase\Factory as FirebaseFactory;
use Kreait\Firebase\ServiceAccount;

class FirebaseChat {
    private $database;
    private $factory;
    
    /**
     * Initialize Firebase connection
     */
    public function __construct() {
        try {
            // Path to your Firebase service account JSON
            $credentialsPath = __DIR__ . '/../../firebase-credentials.json';
            
            if (!file_exists($credentialsPath)) {
                throw new Exception("Firebase credentials file not found at: $credentialsPath");
            }
            
            $this->factory = (new FirebaseFactory)
                ->withServiceAccount($credentialsPath)
                ->withDatabaseUri($_ENV['FIREBASE_DATABASE_URL'] ?? 'https://your-project.firebaseio.com');
                
            $this->database = $this->factory->createDatabase();
        } catch (Exception $e) {
            error_log("Firebase initialization error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get all messages for a guild
     */
    public function getGuildMessages($guildId, $limit = 50) {
        try {
            $reference = $this->database->getReference("guilds/{$guildId}/messages");
            $snapshot = $reference->getSnapshot();
            
            if (!$snapshot->exists()) {
                return [];
            }
            
            $messages = array_values($snapshot->getValue());
            // Return last $limit messages
            return array_slice($messages, -$limit);
        } catch (Exception $e) {
            error_log("Error fetching guild messages: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Send a message to guild chat
     */
    public function sendMessage($guildId, $userId, $username, $message) {
        try {
            $reference = $this->database->getReference("guilds/{$guildId}/messages");
            
            $newMessage = [
                'userId' => $userId,
                'username' => $username,
                'text' => $message,
                'timestamp' => date('c'), // ISO 8601 format
                'createdAt' => time()
            ];
            
            $result = $reference->push($newMessage);
            
            return [
                'success' => true,
                'messageId' => $result->getKey(),
                'data' => $newMessage
            ];
        } catch (Exception $e) {
            error_log("Error sending message: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete a message
     */
    public function deleteMessage($guildId, $messageId) {
        try {
            $reference = $this->database->getReference("guilds/{$guildId}/messages/{$messageId}");
            $reference->remove();
            
            return [
                'success' => true,
                'message' => 'Message deleted successfully'
            ];
        } catch (Exception $e) {
            error_log("Error deleting message: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete message: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Update a message
     */
    public function updateMessage($guildId, $messageId, $newText) {
        try {
            $reference = $this->database->getReference("guilds/{$guildId}/messages/{$messageId}");
            $reference->update([
                'text' => $newText,
                'editedAt' => date('c'),
                'edited' => true
            ]);
            
            return [
                'success' => true,
                'message' => 'Message updated successfully'
            ];
        } catch (Exception $e) {
            error_log("Error updating message: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update message: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Clear all messages for a guild (admin only)
     */
    public function clearGuildChat($guildId) {
        try {
            $reference = $this->database->getReference("guilds/{$guildId}/messages");
            $reference->remove();
            
            return [
                'success' => true,
                'message' => 'Guild chat cleared successfully'
            ];
        } catch (Exception $e) {
            error_log("Error clearing guild chat: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to clear guild chat: ' . $e->getMessage()
            ];
        }
    }
}
?>
