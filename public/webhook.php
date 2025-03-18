<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\GitHubService;

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create a log file
$logFile = __DIR__ . '/webhook.log';

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    error_log($formattedMessage); // Also log to PHP error log
}

// Log the incoming request
logMessage("==== New Request ====");
logMessage("Method: " . $_SERVER['REQUEST_METHOD']);
logMessage("URI: " . $_SERVER['REQUEST_URI']);

// Log all headers for debugging
$headers = getallheaders();
$logHeaders = [];
foreach ($headers as $key => $value) {
    // Mask sensitive information if needed
    $logHeaders[$key] = $value;
}
logMessage("Headers: " . json_encode($logHeaders, JSON_PRETTY_PRINT));

// Get the raw payload
$payload = file_get_contents('php://input');
logMessage("Raw Input: " . $payload);

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logMessage("Error: Method not allowed - " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    die('Method not allowed');
}

// Get GitHub signature and event type
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';
$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';

logMessage("Event Type: " . $event);
logMessage("Signature: " . $signature);

try {
    $githubService = new GitHubService();
    
    // For debugging - handle the request even if signature verification fails
    if (empty($signature)) {
        logMessage("Warning: Missing signature header, but processing anyway for testing");
        $result = $githubService->handleWebhook($payload, $signature, true);
    } else {
        $result = $githubService->handleWebhook($payload, $signature);
    }
    
    logMessage("Success: " . json_encode($result, JSON_PRETTY_PRINT));
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Webhook processed successfully',
        'details' => $result
    ]);
} catch (Exception $e) {
    logMessage("Error: " . $e->getMessage());
    logMessage("Stack trace: " . $e->getTraceAsString());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}