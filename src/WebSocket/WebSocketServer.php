<?php

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SplObjectStorage;
use App\Services\SystemMetricsCollector;

class MonitoringServer implements MessageComponentInterface
{
    protected $clients;
    private $connections = [];
    protected SystemMetricsCollector $metricsCollector;
    protected array $metricsHistory = [];
    protected int $maxMetricsHistory = 20;
    protected array $scheduledTasks = [];
    protected $lastTickTime = 0;

    public function __construct()
    {
        $this->clients = new SplObjectStorage;
        $this->metricsCollector = new SystemMetricsCollector();
        
        error_log("WebSocket Server initialized");
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
        
        // Send initial metrics
        $this->sendInitialData($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        echo "Received message from {$from->resourceId}: {$msg}\n";
        $data = json_decode($msg, true);
        
        if ($data && isset($data['type'])) {
            switch ($data['type']) {
                case 'get_metrics':
                    $this->sendMetrics($from);
                    break;
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    public function broadcast($message) {
        foreach ($this->clients as $client) {
            $client->send($message);
        }
    }

    protected function sendInitialData(ConnectionInterface $conn) {
        // Send system metrics
        $metrics = [
            'type' => 'metrics',
            'metrics' => [
                'cpu_usage' => 45,
                'memory_usage' => 60,
                'disk_usage' => 75
            ]
        ];
        $conn->send(json_encode($metrics));

        // Send initial service status
        $service = [
            'type' => 'service',
            'name' => 'Web Server',
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $conn->send(json_encode($service));

        // Send recent deployments
        if ($this->pdo) {
            $stmt = $this->pdo->query("
                SELECT * FROM deployments 
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            $deployments = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($deployments as $deployment) {
                $deploymentData = [
                    'type' => 'deployment',
                    'status' => $deployment['status'],
                    'repository' => $deployment['repository'],
                    'environment' => $deployment['environment'],
                    'commit_sha' => $deployment['commit_sha'],
                    'description' => $deployment['description'],
                    'timestamp' => $deployment['created_at']
                ];
                $conn->send(json_encode($deploymentData));
            }
        }
    }

    protected function sendMetrics(ConnectionInterface $conn) {
        $metrics = [
            'type' => 'metrics',
            'metrics' => [
                'cpu_usage' => rand(20, 80),
                'memory_usage' => rand(30, 90),
                'disk_usage' => rand(40, 95)
            ]
        ];
        $conn->send(json_encode($metrics));
    }
}