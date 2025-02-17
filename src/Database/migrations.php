<?php

require_once __DIR__ . '/../../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

try {
    $db = \App\Database\DatabaseConnection::getInstance()->getConnection();
    
    // Create services table
    $db->exec("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'unknown',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Create metrics table
    $db->exec("CREATE TABLE IF NOT EXISTS metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_id INT,
        metric_name VARCHAR(255) NOT NULL,
        metric_value TEXT NOT NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
    )");

    // Create connections table
    $db->exec("CREATE TABLE IF NOT EXISTS connections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        connection_id VARCHAR(255) NOT NULL UNIQUE,
        connected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_ping TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create github_events table
    $db->exec("CREATE TABLE IF NOT EXISTS github_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_type VARCHAR(50) NOT NULL,
        repository VARCHAR(255) NOT NULL,
        branch VARCHAR(255) NOT NULL,
        author VARCHAR(255) NOT NULL,
        commit_count INT DEFAULT 0,
        details JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create deployments table
    $db->exec("CREATE TABLE IF NOT EXISTS deployments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        environment VARCHAR(50) NOT NULL,
        status VARCHAR(50) NOT NULL,
        message TEXT,
        github_event_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (github_event_id) REFERENCES github_events(id) ON DELETE SET NULL
    )");

    // Create aws_metrics table
    $db->exec("CREATE TABLE IF NOT EXISTS aws_metrics (
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

    echo "Database tables created successfully\n";
} catch (\PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}