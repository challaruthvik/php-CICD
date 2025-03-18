<?php

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SplObjectStorage;
use App\Services\AWSMonitoringService;

class MonitoringServer implements MessageComponentInterface
{
    protected $clients;
    private $connections = [];
    protected AWSMonitoringService $awsMonitoring;
    protected array $scheduledTasks = [];
    protected $lastTickTime = 0;

    public function __construct()
    {
        $this->clients = new SplObjectStorage;
        
        try {
            $this->awsMonitoring = new AWSMonitoringService($this);
            $this->log("AWS Monitoring Service initialized successfully");
            
            // Initialize scheduled tasks for AWS metrics
            $this->scheduledTasks = [
                [
                    'interval' => 60, // seconds
                    'lastRun' => 0,
                    'task' => function() {
                        $this->collectAndBroadcastAwsMetrics();
                    }
                ]
            ];
            
            $this->lastTickTime = time();
            $this->setupTickProcessor();
        } catch (\Exception $e) {
            $this->log("AWS Monitoring Service initialization failed: {$e->getMessage()}");
        }
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        $this->connections[$conn->resourceId] = $conn;
        $this->log("New connection established: {$conn->resourceId}");
        
        // Send initial AWS metrics when client connects
        $this->sendAwsMetrics($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        try {
            $data = json_decode($msg, true);
            
            if ($data['type'] === 'get_aws_metrics') {
                $this->sendAwsMetrics($from);
            }
        } catch (\Exception $e) {
            $this->log("Error handling message: {$e->getMessage()}");
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        unset($this->connections[$conn->resourceId]);
        $this->log("Connection {$conn->resourceId} has disconnected");
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->log("An error occurred: {$e->getMessage()}");
        $conn->close();
    }

    protected function setupTickProcessor()
    {
        register_shutdown_function(function() {
            $this->log("Server shutting down");
        });
        
        \React\EventLoop\Loop::get()->addPeriodicTimer(1, function() {
            $this->processTick();
        });
    }

    protected function processTick()
    {
        $currentTime = time();
        $elapsedTime = $currentTime - $this->lastTickTime;
        $this->lastTickTime = $currentTime;
        
        foreach ($this->scheduledTasks as &$task) {
            if ($currentTime - $task['lastRun'] >= $task['interval']) {
                try {
                    $task['task']();
                    $task['lastRun'] = $currentTime;
                } catch (\Exception $e) {
                    $this->log("Error in scheduled task: {$e->getMessage()}");
                }
            }
        }
    }

    protected function collectAndBroadcastAwsMetrics()
    {
        try {
            $awsMetrics = $this->awsMonitoring->collectMetrics();
            $this->log("Collected AWS metrics from " . count($awsMetrics) . " instances");
            
            foreach ($awsMetrics as $instanceId => $metrics) {
                $message = [
                    'type' => 'aws_metrics',
                    'instanceId' => $instanceId,
                    'metrics' => $metrics,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                $this->broadcast(json_encode($message));
            }
        } catch (\Exception $e) {
            $this->log("Error collecting AWS metrics: {$e->getMessage()}");
        }
    }

    protected function sendAwsMetrics(ConnectionInterface $client)
    {
        try {
            $awsMetrics = $this->awsMonitoring->collectMetrics();
            
            foreach ($awsMetrics as $instanceId => $metrics) {
                $message = [
                    'type' => 'aws_metrics',
                    'instanceId' => $instanceId,
                    'metrics' => $metrics,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                $client->send(json_encode($message));
            }
            
            $this->log("Sent AWS metrics to client {$client->resourceId}");
        } catch (\Exception $e) {
            $this->log("Error sending AWS metrics: {$e->getMessage()}");
            $client->send(json_encode([
                'type' => 'error',
                'message' => 'Failed to retrieve AWS metrics'
            ]));
        }
    }

    public function broadcast($message)
    {
        foreach ($this->clients as $client) {
            $client->send($message);
        }
    }
    
    protected function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
        file_put_contents(__DIR__ . '/../../websocket.log', $logMessage, FILE_APPEND);
    }
}