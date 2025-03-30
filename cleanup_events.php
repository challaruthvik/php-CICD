<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Database\DatabaseConnection;

try {
    $db = DatabaseConnection::getInstance()->getConnection();
    $db->exec('TRUNCATE TABLE github_events');
    echo "Successfully cleaned up github_events table\n";
} catch (Exception $e) {
    die("Error cleaning up events: " . $e->getMessage() . "\n");
}