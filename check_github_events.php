<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Database\DatabaseConnection;

// Connect to the database
$db = DatabaseConnection::getInstance()->getConnection();

// Query GitHub events
$stmt = $db->query("SELECT * FROM github_events ORDER BY id DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "GitHub Events in Database:\n";
echo "============================\n";

if (count($events) > 0) {
    foreach ($events as $event) {
        echo "ID: {$event['id']}\n";
        echo "Event Type: {$event['event_type']}\n";
        echo "Repository: {$event['repository']}\n"; 
        echo "Branch: {$event['branch']}\n";
        echo "Author: {$event['author']}\n";
        echo "Commit Count: {$event['commit_count']}\n";
        echo "Created At: {$event['created_at']}\n";
        echo "----------------------------\n";
    }
} else {
    echo "No GitHub events found in the database.\n";
}

echo "\n\nDeployments in Database:\n";
echo "============================\n";

// Query deployments
$stmt = $db->query("SELECT * FROM deployments ORDER BY id DESC");
$deployments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($deployments) > 0) {
    foreach ($deployments as $deployment) {
        echo "ID: {$deployment['id']}\n";
        echo "Repository: {$deployment['repository']}\n";
        echo "Environment: {$deployment['environment']}\n";
        echo "Status: {$deployment['status']}\n";
        echo "Commit SHA: {$deployment['commit_sha']}\n";
        echo "Description: {$deployment['description']}\n";
        echo "Created At: {$deployment['created_at']}\n";
        echo "----------------------------\n";
    }
} else {
    echo "No deployments found in the database.\n";
}