<?php
require_once __DIR__ . '/vendor/autoload.php';

use Sephp\Database\DatabaseConnection;
use Sephp\Database\Migrations;

// Create an instance of the Database Connection
$dbConnection = DatabaseConnection::getInstance();
$pdo = $dbConnection->getConnection();

// Create an instance of the Migrations class
$migrations = new Migrations($pdo);

// Run the AWS Metrics migration
echo "Running AWS Metrics migration...\n";
try {
    // Manually run just the AWS metrics table creation
    $sql = "CREATE TABLE IF NOT EXISTS aws_metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        instance_id VARCHAR(50) NOT NULL,
        cpu_utilization FLOAT,
        memory_utilization FLOAT,
        disk_utilization FLOAT,
        network_in FLOAT,
        network_out FLOAT,
        instance_status VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (instance_id),
        INDEX (created_at)
    )";
    
    $pdo->exec($sql);
    echo "AWS Metrics table created successfully!\n";
} catch (PDOException $e) {
    echo "Error creating AWS Metrics table: " . $e->getMessage() . "\n";
}