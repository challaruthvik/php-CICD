<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

echo "Starting database setup...\n";

try {
    // Create PDO connection to MySQL without selecting a database
    $pdo = new PDO(
        'mysql:host=localhost',
        'root',
        '',
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    // Create the database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS sephp_monitoring");
    echo "Database 'sephp_monitoring' created or already exists.\n";
    
    // Connect to the sephp_monitoring database
    $pdo = new PDO(
        'mysql:host=localhost;dbname=sephp_monitoring',
        'root',
        '',
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    // Drop existing tables in the correct order (respecting foreign keys)
    echo "Cleaning up any existing tables...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $tables = [
        'metrics',
        'aws_metrics',
        'github_events',
        'deployments',
        'connections',
        'services'
    ];
    
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
        echo "Dropped table '$table' if it existed.\n";
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Now run the migrations to create all tables
    echo "Creating tables...\n";
    
    // Create services table
    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'unknown',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "Created 'services' table.\n";

    // Create metrics table
    $pdo->exec("CREATE TABLE IF NOT EXISTS metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_id INT,
        metric_name VARCHAR(255) NOT NULL,
        metric_value TEXT NOT NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
    )");
    echo "Created 'metrics' table.\n";

    // Create connections table
    $pdo->exec("CREATE TABLE IF NOT EXISTS connections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        connection_id VARCHAR(255) NOT NULL UNIQUE,
        connected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_ping TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Created 'connections' table.\n";

    // Create github_events table
    $pdo->exec("CREATE TABLE IF NOT EXISTS github_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_type VARCHAR(50) NOT NULL,
        repository VARCHAR(255) NOT NULL,
        branch VARCHAR(255) NOT NULL,
        author VARCHAR(255) NOT NULL,
        commit_count INT DEFAULT 0,
        details JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Created 'github_events' table.\n";

    // Create deployments table
    $pdo->exec("CREATE TABLE IF NOT EXISTS deployments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        repository VARCHAR(255) NOT NULL,
        environment VARCHAR(100) NOT NULL,
        status VARCHAR(50) NOT NULL,
        commit_sha VARCHAR(40) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "Created 'deployments' table.\n";

    // Create aws_metrics table
    $pdo->exec("CREATE TABLE IF NOT EXISTS aws_metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        instance_id VARCHAR(255) NOT NULL,
        cpu_utilization FLOAT,
        memory_utilization FLOAT,
        network_in FLOAT,
        network_out FLOAT,
        instance_status VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_instance_time (instance_id, created_at)
    )");
    echo "Created 'aws_metrics' table.\n";

    // Create aws_metrics table for storing historical data
    $pdo->exec("CREATE TABLE IF NOT EXISTS aws_metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        instance_id VARCHAR(255) NOT NULL,
        cpu_utilization FLOAT,
        memory_utilization FLOAT,
        network_in FLOAT,
        network_out FLOAT,
        instance_status VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Insert some sample data into deployments table
    $pdo->exec("INSERT INTO deployments (repository, environment, status, commit_sha, description) VALUES 
        ('main-app', 'production', 'success', '8f34dc21a3b9a0b4d669218d0f3fe5f0d2ef4c4a', 'Sprint 45 release'),
        ('api-service', 'staging', 'running', 'a7d3fcb91a3cb89d214fe52b8c8af3f9a234bd98', 'New API endpoints'),
        ('web-client', 'development', 'failed', 'c6d91fe528b4a1deb9742990f2c5192bc9a84b11', 'Frontend update')
    ");
    echo "Added sample deployment data.\n";

    echo "Database setup completed successfully!\n";
} catch (Exception $e) {
    echo "Error setting up database: " . $e->getMessage() . "\n";
    exit(1);
}