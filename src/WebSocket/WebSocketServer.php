<?php

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Services\MonitoringService;
use App\Services\AWSMonitoringService;
use PDO;

class WebSocketServer implements MessageComponentInterface {
    protected $clients;
    protected $pdo;
    protected $monitoringService;
    protected $awsMonitoringService;
    protected $updateInterval = 60; // seconds
    protected $lastUpdate = 0;
    private $lastMetrics = [];

    public function __construct(PDO $pdo) {
        $this->clients = new \SplObjectStorage;
        $this->pdo = $pdo;
        try {
            $this->monitoringService = new MonitoringService($this);
            $this->awsMonitoringService = new AWSMonitoringService($this);
            echo "Services initialized successfully\n";
        } catch (\Exception $e) {
            echo "Error initializing services: " . $e->getMessage() . "\n";
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        echo "New connection! ({$conn->resourceId})\n";
        
        // Allow CORS
        $conn->send(json_encode([
            'type' => 'connection_established',
            'connectionId' => $conn->resourceId
        ]));
        
        $this->clients->attach($conn);
        $this->storeConnection($conn);
        
        // Send initial metrics immediately
        $this->checkAndUpdateMetrics();
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        try {
            $data = json_decode($msg, true);
            if (isset($data['type'])) {
                switch ($data['type']) {
                    case 'get_metrics':
                        $this->checkAndUpdateMetrics();
                        break;
                    default:
                        foreach ($this->clients as $client) {
                            if ($from !== $client) {
                                $client->send($msg);
                            }
                        }
                }
            }
        } catch (\Exception $e) {
            echo "Error handling message: " . $e->getMessage() . "\n";
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        $this->removeConnection($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    public function broadcast($data) {
        echo "Broadcasting data: " . substr($data, 0, 100) . "...\n";
        foreach ($this->clients as $client) {
            try {
                $client->send($data);
            } catch (\Exception $e) {
                echo "Error sending to client {$client->resourceId}: " . $e->getMessage() . "\n";
            }
        }
    }

    private function checkAndUpdateMetrics() {
        $currentTime = time();
        if ($currentTime - $this->lastUpdate >= $this->updateInterval) {
            try {
                // System metrics
                $metrics = $this->monitoringService->collectMetrics();
                $this->broadcast(json_encode([
                    'type' => 'metrics',
                    'metrics' => $metrics
                ]));

                // AWS metrics
                $awsMetrics = $this->awsMonitoringService->collectMetrics();
                foreach ($awsMetrics as $instanceId => $metrics) {
                    $metricHash = md5(json_encode($metrics));
                    // Only broadcast if metrics have changed
                    if (!isset($this->lastMetrics[$instanceId]) || $this->lastMetrics[$instanceId] !== $metricHash) {
                        $this->lastMetrics[$instanceId] = $metricHash;
                        $this->broadcast(json_encode([
                            'type' => 'aws_metrics',
                            'instanceId' => $instanceId,
                            'metrics' => $metrics,
                            'timestamp' => date('Y-m-d H:i:s')
                        ]));
                    }
                }
                $this->lastUpdate = $currentTime;
            } catch (\Exception $e) {
                echo "Error updating metrics: " . $e->getMessage() . "\n";
            }
        }
    }

    private function storeConnection($conn) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO connections (connection_id, connected_at) VALUES (?, NOW())");
            $stmt->execute([$conn->resourceId]);
        } catch (\Exception $e) {
            echo "Error storing connection: " . $e->getMessage() . "\n";
        }
    }

    private function removeConnection($conn) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM connections WHERE connection_id = ?");
            $stmt->execute([$conn->resourceId]);
        } catch (\Exception $e) {
            echo "Error removing connection: " . $e->getMessage() . "\n";
        }
    }
}