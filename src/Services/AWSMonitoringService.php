<?php

namespace App\Services;

use Aws\CloudWatch\CloudWatchClient;
use Aws\Ec2\Ec2Client;
use App\Database\DatabaseConnection;
use Dotenv\Dotenv;

class AWSMonitoringService {
    private $cloudWatch;
    private $ec2;
    private $db;
    private $websocketServer;
    private $instances;

    public function __construct($websocketServer = null) {
        // Load environment variables
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->safeLoad();
        
        // Validate required environment variables
        if (empty($_ENV['AWS_ACCESS_KEY_ID']) || empty($_ENV['AWS_SECRET_ACCESS_KEY'])) {
            throw new \Exception("AWS credentials not found in environment variables");
        }

        if (empty($_ENV['AWS_EC2_INSTANCES'])) {
            throw new \Exception("No EC2 instances specified in AWS_EC2_INSTANCES");
        }

        $awsConfig = [
            'version' => 'latest',
            'region'  => $_ENV['AWS_REGION'] ?? 'us-east-1',
            'credentials' => [
                'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
                'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
            ]
        ];

        try {
            $this->cloudWatch = new CloudWatchClient($awsConfig);
            $this->ec2 = new Ec2Client($awsConfig);
            $this->db = DatabaseConnection::getInstance()->getConnection();
            $this->websocketServer = $websocketServer;
            $this->instances = array_filter(explode(',', $_ENV['AWS_EC2_INSTANCES']));

            error_log("AWS Service initialized with region: " . $awsConfig['region']);
            error_log("Monitoring instances: " . implode(', ', $this->instances));
        } catch (\Exception $e) {
            error_log("AWS Service initialization error: " . $e->getMessage());
            throw $e;
        }
    }

    public function collectMetrics() {
        $metrics = [];

        foreach ($this->instances as $instanceId) {
            try {
                error_log("Collecting metrics for instance: " . $instanceId);
                
                $metrics[$instanceId] = [
                    'cpu' => $this->getCpuUtilization($instanceId),
                    'memory' => $this->getMemoryUtilization($instanceId),
                    'network' => $this->getNetworkUtilization($instanceId),
                    'status' => $this->getInstanceStatus($instanceId)
                ];

                error_log("Metrics collected successfully for instance: " . $instanceId);
                $this->storeAwsMetrics($instanceId, $metrics[$instanceId]);
            } catch (\Exception $e) {
                error_log("Error collecting metrics for instance {$instanceId}: " . $e->getMessage());
                $metrics[$instanceId] = [
                    'cpu' => 0,
                    'memory' => 0,
                    'network' => ['in' => 0, 'out' => 0],
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $metrics;
    }

    private function getCpuUtilization($instanceId) {
        try {
            $result = $this->cloudWatch->getMetricStatistics([
                'Namespace' => 'AWS/EC2',
                'MetricName' => 'CPUUtilization',
                'Dimensions' => [
                    ['Name' => 'InstanceId', 'Value' => $instanceId]
                ],
                'StartTime' => strtotime('-5 minutes'),
                'EndTime' => time(),
                'Period' => 300,
                'Statistics' => ['Average']
            ]);

            $datapoints = $result->get('Datapoints');
            return !empty($datapoints) ? end($datapoints)['Average'] : 0;
        } catch (\Exception $e) {
            error_log("Error getting CPU utilization: " . $e->getMessage());
            return 0;
        }
    }

    private function getMemoryUtilization($instanceId) {
        try {
            // For memory metrics, we need to use AWS/EC2 metrics
            $result = $this->cloudWatch->getMetricStatistics([
                'Namespace' => 'AWS/EC2',
                'MetricName' => 'MemoryUtilization',
                'Dimensions' => [
                    ['Name' => 'InstanceId', 'Value' => $instanceId]
                ],
                'StartTime' => strtotime('-5 minutes'),
                'EndTime' => time(),
                'Period' => 300,
                'Statistics' => ['Average']
            ]);

            $datapoints = $result->get('Datapoints');
            
            // If standard EC2 memory metrics aren't available, try CloudWatch agent metrics
            if (empty($datapoints)) {
                $result = $this->cloudWatch->getMetricStatistics([
                    'Namespace' => 'CWAgent',
                    'MetricName' => 'mem_used_percent',
                    'Dimensions' => [
                        ['Name' => 'InstanceId', 'Value' => $instanceId]
                    ],
                    'StartTime' => strtotime('-5 minutes'),
                    'EndTime' => time(),
                    'Period' => 300,
                    'Statistics' => ['Average']
                ]);
                $datapoints = $result->get('Datapoints');
            }

            return !empty($datapoints) ? end($datapoints)['Average'] : 0;
        } catch (\Exception $e) {
            error_log("Error getting memory utilization: " . $e->getMessage());
            return 0;
        }
    }

    private function getNetworkUtilization($instanceId) {
        try {
            // Get both NetworkIn and NetworkOut metrics
            $networkIn = $this->cloudWatch->getMetricStatistics([
                'Namespace' => 'AWS/EC2',
                'MetricName' => 'NetworkIn',
                'Dimensions' => [
                    ['Name' => 'InstanceId', 'Value' => $instanceId]
                ],
                'StartTime' => strtotime('-5 minutes'),
                'EndTime' => time(),
                'Period' => 300,
                'Statistics' => ['Average', 'Maximum']
            ]);

            $networkOut = $this->cloudWatch->getMetricStatistics([
                'Namespace' => 'AWS/EC2',
                'MetricName' => 'NetworkOut',
                'Dimensions' => [
                    ['Name' => 'InstanceId', 'Value' => $instanceId]
                ],
                'StartTime' => strtotime('-5 minutes'),
                'EndTime' => time(),
                'Period' => 300,
                'Statistics' => ['Average', 'Maximum']
            ]);

            $inDatapoints = $networkIn->get('Datapoints');
            $outDatapoints = $networkOut->get('Datapoints');

            // Convert bytes to megabytes for better readability
            return [
                'in' => !empty($inDatapoints) ? end($inDatapoints)['Average'] / (1024 * 1024) : 0,
                'out' => !empty($outDatapoints) ? end($outDatapoints)['Average'] / (1024 * 1024) : 0,
                'max_in' => !empty($inDatapoints) ? end($inDatapoints)['Maximum'] / (1024 * 1024) : 0,
                'max_out' => !empty($outDatapoints) ? end($outDatapoints)['Maximum'] / (1024 * 1024) : 0
            ];
        } catch (\Exception $e) {
            error_log("Error getting network utilization: " . $e->getMessage());
            return ['in' => 0, 'out' => 0, 'max_in' => 0, 'max_out' => 0];
        }
    }

    private function getInstanceStatus($instanceId) {
        try {
            $result = $this->ec2->describeInstances([
                'InstanceIds' => [$instanceId]
            ]);

            $reservations = $result->get('Reservations');
            if (empty($reservations)) {
                error_log("No reservations found for instance: " . $instanceId);
                return 'unknown';
            }

            $instance = $reservations[0]['Instances'][0];
            $state = $instance['State']['Name'];

            switch ($state) {
                case 'running':
                    return 'healthy';
                case 'stopped':
                case 'stopping':
                    return 'error';
                case 'pending':
                case 'rebooting':
                    return 'warning';
                default:
                    return 'unknown';
            }
        } catch (\Exception $e) {
            error_log("Error getting instance status: " . $e->getMessage());
            return 'error';
        }
    }

    /**
     * Store AWS metrics in the database
     * 
     * @param string $instanceId The EC2 instance ID
     * @param array $metrics The collected metrics
     * @return bool Success/failure
     */
    private function storeAwsMetrics($instanceId, array $metrics)
    {
        try {
            $sql = "INSERT INTO aws_metrics 
                   (instance_id, cpu_utilization, memory_utilization, 
                    network_in, network_out, status, disk_utilization, 
                    collected_at) 
                   VALUES 
                   (:instance_id, :cpu, :memory, :network_in, :network_out, 
                    :status, :disk_utilization, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':instance_id', $instanceId);
            $stmt->bindParam(':cpu', $metrics['cpu']);
            $stmt->bindParam(':memory', $metrics['memory']);
            $stmt->bindParam(':network_in', $metrics['network']['in']);
            $stmt->bindParam(':network_out', $metrics['network']['out']);
            $stmt->bindParam(':status', $metrics['status']);
            
            // Default disk utilization to 0 as it's not currently being collected
            $diskUtilization = 0;
            $stmt->bindParam(':disk_utilization', $diskUtilization);
            
            $result = $stmt->execute();
            
            if ($result) {
                error_log("AWS metrics stored successfully for instance: " . $instanceId);
            } else {
                error_log("Failed to store AWS metrics for instance: " . $instanceId);
            }
            
            return $result;
        } catch (\Exception $e) {
            error_log("Error storing AWS metrics: " . $e->getMessage());
            return false;
        }
    }

    public function getStoredMetrics($instanceId = null, $limit = 100) {
        try {
            $query = "SELECT * FROM aws_metrics";
            $params = [];
            
            if ($instanceId) {
                $query .= " WHERE instance_id = :instance_id";
                $params[':instance_id'] = $instanceId;
            }
            
            $query .= " ORDER BY collected_at DESC LIMIT :limit";
            $params[':limit'] = $limit;
            
            $stmt = $this->db->prepare($query);
            foreach ($params as $key => &$value) {
                $stmt->bindParam($key, $value);
            }
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error retrieving AWS metrics: " . $e->getMessage());
            return [];
        }
    }

    private function broadcastAwsMetrics($instanceId, $metrics) {
        $data = json_encode([
            'type' => 'aws_metrics',
            'instanceId' => $instanceId,
            'metrics' => $metrics,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        if (method_exists($this->websocketServer, 'broadcast')) {
            $this->websocketServer->broadcast($data);
        }
    }

    public function getMetricsHistory(string $period = 'hourly', ?string $instanceId = null): array {
        try {
            $sql = match($period) {
                'hourly' => "SELECT * FROM aws_metrics WHERE collected_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)",
                'daily' => "SELECT * FROM aws_metrics WHERE collected_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
                'weekly' => "SELECT * FROM aws_metrics WHERE collected_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
                default => throw new \InvalidArgumentException("Invalid period: {$period}")
            };
            
            if ($instanceId) {
                $sql .= " AND instance_id = :instance_id";
            }
            
            $sql .= " ORDER BY collected_at";
            
            $stmt = $this->db->prepare($sql);
            if ($instanceId) {
                $stmt->bindParam(':instance_id', $instanceId);
            }
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error retrieving metrics history: " . $e->getMessage());
            return [];
        }
    }
}