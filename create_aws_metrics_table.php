<?php
// This script creates the aws_metrics table if it doesn't exist

require_once __DIR__ . '/vendor/autoload.php';

use Sephp\Database\DatabaseConnection;
use Sephp\Database\Migrations;

try {
    echo "Starting AWS metrics table migration...\n";
    
    // Get database connection
    $db = DatabaseConnection::getInstance()->getConnection();
    
    // Create and run the migration
    $migrations = new Migrations($db);
    $migrations->runMigrations();
    
    echo "AWS metrics table migration completed successfully.\n";
} catch (Exception $e) {
    echo "Error running migration: " . $e->getMessage() . "\n";
}