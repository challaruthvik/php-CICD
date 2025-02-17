<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Services\AWSMonitoringService;

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "AWS Environment Check:\n";
echo "====================\n";
echo "AWS_ACCESS_KEY_ID: " . (strlen(getenv('AWS_ACCESS_KEY_ID')) > 0 ? "✓ Set" : "✗ Not Set") . "\n";
echo "AWS_SECRET_ACCESS_KEY: " . (strlen(getenv('AWS_SECRET_ACCESS_KEY')) > 0 ? "✓ Set" : "✗ Not Set") . "\n";
echo "AWS_REGION: " . getenv('AWS_REGION') . "\n";
echo "AWS_EC2_INSTANCES: " . getenv('AWS_EC2_INSTANCES') . "\n\n";

try {
    echo "Initializing AWS Monitoring Service...\n";
    $awsService = new AWSMonitoringService();
    
    echo "Collecting metrics...\n";
    $metrics = $awsService->collectMetrics();
    
    echo "\nAWS Metrics Collection Results\n";
    echo "============================\n\n";
    
    foreach ($metrics as $instanceId => $instanceMetrics) {
        echo "Instance ID: {$instanceId}\n";
        echo "Status: {$instanceMetrics['status']}\n";
        echo "CPU Usage: {$instanceMetrics['cpu']}%\n";
        echo "Memory Usage: {$instanceMetrics['memory']}%\n";
        echo "Network In: " . ($instanceMetrics['network']['in'] / 1024 / 1024) . " MB/s\n";
        echo "Network Out: " . ($instanceMetrics['network']['out'] / 1024 / 1024) . " MB/s\n";
        if (isset($instanceMetrics['error'])) {
            echo "Error: {$instanceMetrics['error']}\n";
        }
        echo "----------------------------\n\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}