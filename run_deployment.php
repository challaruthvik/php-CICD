<?php
/**
 * Deployment Runner Script
 * 
 * This script is designed to be run from the command line to execute a deployment
 * Usage: php run_deployment.php <deployment_id>
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\HostingerDeploymentService;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Get deployment ID from command line
$deploymentId = $argv[1] ?? null;

if (!$deploymentId) {
    echo "Error: Deployment ID is required.\n";
    echo "Usage: php run_deployment.php <deployment_id>\n";
    exit(1);
}

echo "Starting deployment #{$deploymentId}...\n";

try {
    // Create deployment service and start the deployment
    $deploymentService = new HostingerDeploymentService();
    $result = $deploymentService->startDeployment($deploymentId);
    
    if ($result) {
        echo "Deployment completed successfully.\n";
        exit(0);
    } else {
        echo "Deployment failed. Check logs for details.\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}