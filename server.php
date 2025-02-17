<?php

require __DIR__ . '/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSocket\WebSocketServer;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Database connection
$config = require __DIR__ . '/config/database.php';
$pdo = new PDO(
    "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}",
    $config['database']['username'],
    $config['database']['password'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);

echo "Starting WebSocket server...\n";

try {
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new WebSocketServer($pdo)
            )
        ),
        $config['websocket']['port'],
        $config['websocket']['host']
    );

    echo "WebSocket server running on {$config['websocket']['host']}:{$config['websocket']['port']}\n";
    $server->run();
} catch (Exception $e) {
    echo "Error starting server: " . $e->getMessage() . "\n";
    exit(1);
}