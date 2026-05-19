<?php
/**
 * Git Auto-Pull Script for GitHub Integration
 * Automatically pulls latest code from GitHub
 * Compatible with EasyCron
 * 
 * EasyCron URL: https://your-domain.com/guild-system/scripts/pull-updates.php?auth=YOUR_SECRET_KEY
 * 
 * SETUP INSTRUCTIONS:
 * 1. Generate a strong random token and replace YOUR_SECRET_AUTH_KEY
 * 2. Add this URL to EasyCron with the correct auth token
 * 3. EasyCron will call this to keep your code updated from GitHub
 * 4. Schedule it to run every 6-12 hours
 */

// ========== SECURITY ==========
// Change this to a strong random string (same auth token as backup.php for simplicity)
// Generate with: echo sha1(uniqid(mt_rand(), true));
define('PULL_AUTH_TOKEN', '472ea46f1f6191697b09852fc8df69a16de76840');

// Verify authorization
if (!isset($_GET['auth']) || $_GET['auth'] !== PULL_AUTH_TOKEN) {
    http_response_code(403);
    die(json_encode([
        'success' => false,
        'message' => 'Unauthorized - Invalid or missing auth token'
    ]));
}

// ========== CONFIGURATION ==========
$projectPath = __DIR__ . '/..'; // Parent directory (guild-system root)
$logDir = $projectPath . '/logs';
$logFile = $logDir . '/git-pull.log';

// Create logs directory if needed
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// ========== PULL FROM GITHUB ==========
// Only pull code - .env and firebase-credentials.json are protected by .gitignore
$output = [];
$returnCode = 0;

// GitHub authentication
$githubUsername = 'D50-dev'; //Repalce with your Github username
$githubToken = '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'; //Replace with GutHub token
$githubRepo = 'guild-management-system'; // Replace with Github repository

// Build git pull command with HTTPS authentication (works without SSH)
$command = "cd " . escapeshellarg($projectPath) . " && git pull https://{$githubUsername}:{$githubToken}@github.com/{$githubUsername}/{$githubRepo}.git main 2>&1";

// Execute git pull
exec($command, $output, $returnCode);

// ========== HANDLE RESULTS ==========
$success = $returnCode === 0;
$timestamp = date('Y-m-d H:i:s');
$outputText = implode("\n", $output);

if ($success) {
    // Log successful pull
    $logEntry = "[$timestamp] SUCCESS\n";
    $logEntry .= "  Output: $outputText\n";
    
    // Optionally, run composer install if composer.lock changed
    if (strpos($outputText, 'composer') !== false || strpos($outputText, 'composer.json') !== false) {
        $composerOutput = [];
        $composerCode = 0;
        $composerCmd = "cd " . escapeshellarg($projectPath) . " && composer install 2>&1";
        exec($composerCmd, $composerOutput, $composerCode);
        
        if ($composerCode === 0) {
            $logEntry .= "  Composer: Updated dependencies\n";
        } else {
            $logEntry .= "  Composer: Failed - " . implode("\n", $composerOutput) . "\n";
        }
    }
    
    $logEntry .= "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Code updated from GitHub successfully',
        'timestamp' => $timestamp,
        'output' => $output,
        'note' => '.env and firebase-credentials.json remain untouched'
    ]);
    
} else {
    // Log failed pull
    $logEntry = "[$timestamp] FAILED\n";
    $logEntry .= "  Command: git pull origin main\n";
    $logEntry .= "  Error: $outputText\n";
    $logEntry .= "  Troubleshooting:\n";
    $logEntry .= "    - Git may not be available on this server\n";
    $logEntry .= "    - SSH keys may need to be configured\n";
    $logEntry .= "    - Check if repository is initialized with: cd " . $projectPath . " && git status\n";
    $logEntry .= "\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to pull from GitHub',
        'error' => $outputText,
        'troubleshooting' => [
            'Verify Git is installed on Infinity Free: ssh to server and run "git --version"',
            'Check SSH keys are configured: ssh to server and run "git status" in project directory',
            'Alternatively, manually update files by downloading from GitHub',
            'Check the logs: ' . $logFile
        ]
    ]);
}

// ========== LOG REQUEST SUMMARY ==========
file_put_contents(
    $logDir . '/git-pull-requests.log',
    date('Y-m-d H:i:s') . " | Auth: " . (isset($_GET['auth']) ? 'OK' : 'DENIED') . 
    " | Success: " . ($success ? 'YES' : 'NO') . "\n",
    FILE_APPEND
);

?>
