<?php

namespace Sephp\Database;

/**
 * Database migrations for application tables
 */
class Migrations {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Run all migrations
     */
    public function runMigrations() {
        $this->createGithubEventsTable();
        $this->createDeploymentsTable();
        $this->createAWSMetricsTable();
        return true;
    }

    /**
     * Create github_events table if it doesn't exist
     */
    private function createGithubEventsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS github_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(50) NOT NULL,
            payload TEXT NOT NULL,
            repository VARCHAR(255) NOT NULL,
            sender VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->pdo->exec($sql);
    }

    /**
     * Create deployments table if it doesn't exist
     */
    private function createDeploymentsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS deployments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            repository VARCHAR(255) NOT NULL,
            branch VARCHAR(100) DEFAULT 'main',
            commit_hash VARCHAR(40),
            environment VARCHAR(50) NOT NULL,
            target VARCHAR(50) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            log TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL
        )";
        
        $this->pdo->exec($sql);
    }
    
    /**
     * Create aws_metrics table if it doesn't exist
     */
    private function createAWSMetricsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS aws_metrics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            instance_id VARCHAR(50) NOT NULL,
            cpu_utilization FLOAT,
            memory_utilization FLOAT,
            disk_utilization FLOAT,
            network_in FLOAT,
            network_out FLOAT,
            status VARCHAR(20),
            reservation_id VARCHAR(100),
            reservation_start TIMESTAMP NULL,
            reservation_end TIMESTAMP NULL,
            collected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (instance_id),
            INDEX (collected_at)
        )";
        
        $this->pdo->exec($sql);
    }
}