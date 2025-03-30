<?php
/**
 * GitHub Webhook URL Updater
 * 
 * This script automatically updates your GitHub repository webhook URL
 * when you get a new ngrok or localtunnel URL.
 */

require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Configuration
$config = [
    'github_token' => getenv('GITHUB_TOKEN') ?: '',
    'github_username' => getenv('GITHUB_USERNAME') ?: '',
    'github_repo' => getenv('GITHUB_REPO') ?: '',
    'webhook_id' => getenv('GITHUB_WEBHOOK_ID') ?: '',
    'webhook_secret' => getenv('GITHUB_WEBHOOK_SECRET') ?: 'sephp_webhook_secret_2024'
];

// Get tunnel URL from command line argument
$tunnelUrl = $argv[1] ?? '';

if (empty($tunnelUrl)) {
    echo "Error: No tunnel URL provided\n";
    echo "Usage: php update_github_webhook.php <tunnel_url>\n";
    exit(1);
}

// Remove trailing slash if present
$tunnelUrl = rtrim($tunnelUrl, '/');

// Validate config
if (empty($config['github_token'])) {
    echo "Error: GitHub token not found. Please set the GITHUB_TOKEN environment variable.\n";
    exit(1);
}

if (empty($config['github_username']) || empty($config['github_repo'])) {
    echo "Error: GitHub username or repository not specified.\n";
    echo "Please set the GITHUB_USERNAME and GITHUB_REPO environment variables.\n";
    exit(1);
}

if (empty($config['webhook_id'])) {
    echo "Warning: No webhook ID provided. Will attempt to create a new webhook.\n";
    createNewWebhook($config, $tunnelUrl);
} else {
    updateExistingWebhook($config, $tunnelUrl);
}

/**
 * Update an existing GitHub webhook
 */
function updateExistingWebhook($config, $tunnelUrl) {
    $webhookUrl = "{$tunnelUrl}/Sephp/public/webhook.php";
    
    echo "Updating webhook URL to: {$webhookUrl}\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.github.com/repos/{$config['github_username']}/{$config['github_repo']}/hooks/{$config['webhook_id']}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    
    $payload = json_encode([
        'config' => [
            'url' => $webhookUrl,
            'content_type' => 'json',
            'secret' => $config['webhook_secret'],
            'insecure_ssl' => '0'
        ],
        'active' => true
    ]);
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Sephp-Webhook-Updater',
        'Authorization: token ' . $config['github_token'],
        'Accept: application/vnd.github.v3+json',
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        echo "Success! Webhook URL updated successfully.\n";
        
        // Test webhook connection
        echo "Testing webhook connection...\n";
        testWebhookConnection($webhookUrl);
    } else {
        echo "Error updating webhook (HTTP {$httpCode}):\n";
        echo $response . "\n";
    }
}

/**
 * Create a new GitHub webhook
 */
function createNewWebhook($config, $tunnelUrl) {
    $webhookUrl = "{$tunnelUrl}/Sephp/public/webhook.php";
    
    echo "Creating new webhook with URL: {$webhookUrl}\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.github.com/repos/{$config['github_username']}/{$config['github_repo']}/hooks");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    
    $payload = json_encode([
        'name' => 'web',
        'active' => true,
        'events' => ['push', 'pull_request', 'deployment', 'deployment_status'],
        'config' => [
            'url' => $webhookUrl,
            'content_type' => 'json',
            'secret' => $config['webhook_secret'],
            'insecure_ssl' => '0'
        ]
    ]);
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Sephp-Webhook-Updater',
        'Authorization: token ' . $config['github_token'],
        'Accept: application/vnd.github.v3+json',
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $data = json_decode($response, true);
        $webhookId = $data['id'] ?? 'unknown';
        echo "Success! New webhook created with ID: {$webhookId}\n";
        echo "Add this line to your .env file: GITHUB_WEBHOOK_ID={$webhookId}\n";
        
        // Test webhook connection
        echo "Testing webhook connection...\n";
        testWebhookConnection($webhookUrl);
    } else {
        echo "Error creating webhook (HTTP {$httpCode}):\n";
        echo $response . "\n";
    }
}

/**
 * Test webhook connection
 * 
 * Note: This test will intentionally send a GET request rather than a HEAD request.
 * GitHub webhooks require POST requests with proper signatures, so we expect a 405 or 401 response,
 * which just confirms the endpoint exists and is responding correctly.
 */
function testWebhookConnection($webhookUrl) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $webhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    // Use GET instead of HEAD to test for endpoint existence
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Sephp-Webhook-Tester'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // For webhook endpoints, 405 Method Not Allowed is actually expected 
    // when not sending a proper POST with correct signature
    if ($httpCode == 405 || $httpCode == 401 || ($httpCode >= 200 && $httpCode < 400)) {
        echo "Webhook connection test successful! Endpoint exists and is responding.\n";
        echo "Response code {$httpCode} is expected for a webhook endpoint when not sending a proper GitHub event.\n";
    } else if ($httpCode == 0) {
        echo "Warning: Could not connect to webhook URL. Your URL may not be publicly accessible.\n";
    } else {
        echo "Warning: Unexpected response from webhook endpoint (HTTP {$httpCode}).\n";
        echo "This may still work with GitHub, but you should verify the webhook in your GitHub repository settings.\n";
    }
}