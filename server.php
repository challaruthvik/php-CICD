<?php

require __DIR__ . '/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use App\WebSocket\RatchetExtensions\WsServer;
use App\WebSocket\MonitoringServer;
use App\Database\DatabaseConnection;
use Dotenv\Dotenv;

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Database connection
$config = require __DIR__ . '/config/database.php';
$db = DatabaseConnection::getInstance()->getConnection();

// Create a log file for WebSocket server
$logFile = __DIR__ . '/websocket.log';
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

logMessage("Starting WebSocket server...");

echo "Starting WebSocket server...\n";

try {
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new MonitoringServer($db)
            )
        ),
        $config['websocket']['port'],
        $config['websocket']['host']
    );

    logMessage("WebSocket server running on {$config['websocket']['host']}:{$config['websocket']['port']}");
    echo "WebSocket server running on {$config['websocket']['host']}:{$config['websocket']['port']}\n";
    $server->run();
} catch (Exception $e) {
    logMessage("Error starting server: " . $e->getMessage());
    echo "Error starting server: " . $e->getMessage() . "\n";
    exit(1);
}