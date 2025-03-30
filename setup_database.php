<?php
/**
 * Database Setup Script
 * Creates the database and imports the schema for SePHP Monitoring System
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Database connection parameters
$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'] ?? 3306;
$dbname = $_ENV['DB_NAME'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];

echo "Setting up database for SePHP Monitoring System\n";
echo "=============================================\n";
echo "Host: {$host}:{$port}\n";
echo "Database: {$dbname}\n";
echo "Username: {$username}\n\n";

try {
    // Connect without specifying database (to create it)
    echo "Connecting to MySQL server...\n";
    $conn = new mysqli($host, $username, $password, '', $port);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "Connected to MySQL server successfully!\n";
    
    // Create database if it doesn't exist
    echo "Creating database '{$dbname}' if it doesn't exist...\n";
    $sql = "CREATE DATABASE IF NOT EXISTS `{$dbname}`";
    
    if ($conn->query($sql) === TRUE) {
        echo "Database created or already exists.\n";
    } else {
        throw new Exception("Error creating database: " . $conn->error);
    }
    
    // Switch to the new database
    echo "Switching to database '{$dbname}'...\n";
    $conn->select_db($dbname);
    
    // Create tables
    echo "Creating tables...\n";
    
    // Create GitHub events table
    $sql = "CREATE TABLE IF NOT EXISTS `github_events` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `event_id` VARCHAR(255) NOT NULL,
        `event_type` VARCHAR(50) NOT NULL,
        `repository` VARCHAR(255) NOT NULL,
        `sender` VARCHAR(255) NOT NULL,
        `payload` TEXT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "- Table 'github_events' created or already exists.\n";
    } else {
        throw new Exception("Error creating table 'github_events': " . $conn->error);
    }
    
    // Create deployments table
    $sql = "CREATE TABLE IF NOT EXISTS `deployments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `repository` VARCHAR(255) NOT NULL,
        `environment` VARCHAR(50) NOT NULL,
        `target` VARCHAR(255) NOT NULL,
        `status` VARCHAR(50) NOT NULL,
        `log` TEXT,
        `deployment_config` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "- Table 'deployments' created or already exists.\n";
    } else {
        throw new Exception("Error creating table 'deployments': " . $conn->error);
    }
    
    echo "Adding sample data...\n";
    
    // Insert sample GitHub event
    $sampleEventId = "sample-" . uniqid();
    $samplePayload = json_encode([
        'action' => 'opened',
        'issue' => [
            'number' => 1,
            'title' => 'Sample Issue'
        ]
    ]);
    
    $sql = "INSERT INTO `github_events` 
            (`event_id`, `event_type`, `repository`, `sender`, `payload`) 
            VALUES 
            ('{$sampleEventId}', 'issues', 'challaruthvik/testing', 'challaruthvik', '{$samplePayload}')";
    
    if ($conn->query($sql) === TRUE) {
        echo "- Added sample GitHub event.\n";
    } else {
        echo "- Note: " . $conn->error . "\n";
    }
    
    // Insert sample deployment with correct schema
    $deploymentConfig = json_encode([
        'branch' => 'main',
        'commit_hash' => '1a2b3c4d5e6f7g8h9i0j'
    ]);
    
    $sql = "INSERT INTO `deployments` 
            (`repository`, `environment`, `target`, `status`, `log`, `deployment_config`) 
            VALUES 
            ('challaruthvik/testing', 'production', 'rtest.ruthvikchalla.com', 'success', 'Deployment completed successfully.', '{$deploymentConfig}')";
    
    if ($conn->query($sql) === TRUE) {
        echo "- Added sample deployment record.\n";
    } else {
        echo "- Note: " . $conn->error . "\n";
    }
    
    // Close connection
    $conn->close();
    
    echo "\nDatabase setup completed successfully!\n";
    echo "You can now access the database at: {$host}:{$port}/{$dbname}\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    
    if (isset($conn)) {
        $conn->close();
    }
    
    exit(1);
}