<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
use Dotenv\Dotenv;
use App\Database\DatabaseConnection;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

echo "Updating database schema for deployments...\n";

try {
    $db = DatabaseConnection::getInstance()->getConnection();
    
    // Check if deployment_target column exists in deployments table
    $stmt = $db->query("SHOW COLUMNS FROM deployments LIKE 'deployment_target'");
    if ($stmt->rowCount() == 0) {
        // Add new columns for deployment target
        $db->exec("ALTER TABLE deployments 
            ADD COLUMN deployment_target VARCHAR(50) DEFAULT 'github' AFTER environment,
            ADD COLUMN deployment_config JSON AFTER description,
            ADD COLUMN deployment_log TEXT AFTER deployment_config,
            ADD COLUMN started_at TIMESTAMP NULL DEFAULT NULL AFTER created_at,
            ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL AFTER started_at
        ");
        echo "Updated deployments table schema.\n";
    } else {
        echo "Deployment target columns already exist.\n";
    }
    
    echo "Database schema updated successfully!\n";
} catch (Exception $e) {
    echo "Error updating database schema: " . $e->getMessage() . "\n";
    exit(1);
}