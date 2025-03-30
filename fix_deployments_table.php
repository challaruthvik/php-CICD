<?php
require_once __DIR__ . '/vendor/autoload.php';

use Sephp\Database\DatabaseConnection;

try {
    // Get database connection
    $db = DatabaseConnection::getInstance()->getConnection();
    
    // First, get a list of all existing columns
    $stmt = $db->query("DESCRIBE `deployments`");
    $existingColumns = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $row['Field'];
    }
    
    echo "Current columns in deployments table: " . implode(', ', $existingColumns) . "\n\n";
    
    // Check if environment column exists
    if (!in_array('environment', $existingColumns)) {
        // Add environment column if it doesn't exist
        $db->exec("ALTER TABLE `deployments` ADD `environment` VARCHAR(100) NOT NULL DEFAULT 'production'");
        echo "Added 'environment' column to deployments table.\n";
    }
    
    // Check if target column exists
    if (!in_array('target', $existingColumns)) {
        // Add target column without referring to environment
        $db->exec("ALTER TABLE `deployments` ADD `target` VARCHAR(100) NOT NULL DEFAULT 'hostinger'");
        echo "Added 'target' column to deployments table.\n";
    }
    
    // Check if deployment_config column exists
    if (!in_array('deployment_config', $existingColumns)) {
        // Add deployment_config column
        $db->exec("ALTER TABLE `deployments` ADD `deployment_config` TEXT NULL");
        echo "Added 'deployment_config' column to deployments table.\n";
    }
    
    // Check if we need to rename commit_sha to commit_hash
    if (in_array('commit_sha', $existingColumns) && !in_array('commit_hash', $existingColumns)) {
        // Rename commit_sha to commit_hash
        $db->exec("ALTER TABLE `deployments` CHANGE `commit_sha` `commit_hash` VARCHAR(40) NOT NULL");
        echo "Renamed 'commit_sha' to 'commit_hash'.\n";
    } else if (!in_array('commit_sha', $existingColumns) && !in_array('commit_hash', $existingColumns)) {
        // Add commit_hash column if neither exists
        $db->exec("ALTER TABLE `deployments` ADD `commit_hash` VARCHAR(40) NULL");
        echo "Added 'commit_hash' column to deployments table.\n";
    }
    
    // Make sure completed_at and log columns exist for details view
    if (!in_array('completed_at', $existingColumns)) {
        $db->exec("ALTER TABLE `deployments` ADD `completed_at` TIMESTAMP NULL");
        echo "Added 'completed_at' column to deployments table.\n";
    }
    
    if (!in_array('log', $existingColumns)) {
        $db->exec("ALTER TABLE `deployments` ADD `log` TEXT NULL");
        echo "Added 'log' column to deployments table.\n";
    }
    
    // Update existing rows to set target and environment for demonstration
    $db->exec("UPDATE `deployments` SET `target` = 'hostinger' WHERE `target` = '' OR `target` IS NULL");
    $db->exec("UPDATE `deployments` SET `environment` = 'production' WHERE `environment` = '' OR `environment` IS NULL");
    echo "Updated existing deployments with default values.\n";
    
    echo "\nDatabase schema update completed successfully!";
    
} catch (Exception $e) {
    echo "Error updating database schema: " . $e->getMessage();
}