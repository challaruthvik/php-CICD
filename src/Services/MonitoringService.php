<?php

namespace App\Services;

use App\Database\DatabaseConnection;
use PDO;

class MonitoringService {
    private $db;
    private $websocketServer;

    public function __construct($websocketServer = null) {
        $this->db = DatabaseConnection::getInstance()->getConnection();
        $this->websocketServer = $websocketServer;
    }

    public function collectMetrics() {
        // Example metrics collection (CPU, Memory, Disk)
        $metrics = [
            'cpu_usage' => $this->getCpuUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage()
        ];

        $this->storeMetrics('system', $metrics);
        return $metrics;
    }

    private function getCpuUsage() {
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = "wmic cpu get loadpercentage";
            $output = shell_exec($cmd);
            if (preg_match("/\d+/", $output, $matches)) {
                return $matches[0];
            }
        } else {
            $load = sys_getloadavg();
            return $load[0];
        }
        return 0;
    }

    private function getMemoryUsage() {
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = "wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value";
            $output = shell_exec($cmd);
            preg_match("/TotalVisibleMemorySize=(\d+)/", $output, $total);
            preg_match("/FreePhysicalMemory=(\d+)/", $output, $free);
            if (isset($total[1]) && isset($free[1])) {
                return round((($total[1] - $free[1]) / $total[1]) * 100, 2);
            }
        } else {
            $free = shell_exec('free');
            $free = (string)trim($free);
            $free_arr = explode("\n", $free);
            $mem = explode(" ", $free_arr[1]);
            $mem = array_filter($mem);
            $mem = array_merge($mem);
            return round($mem[2]/$mem[1]*100, 2);
        }
        return 0;
    }

    private function getDiskUsage() {
        $path = PHP_OS_FAMILY === 'Windows' ? 'C:' : '/';
        $total = disk_total_space($path);
        $free = disk_free_space($path);
        return round(($total - $free) / $total * 100, 2);
    }

    private function storeMetrics($serviceName, $metrics) {
        // Get or create service
        $stmt = $this->db->prepare("INSERT INTO services (name) VALUES (?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
        $stmt->execute([$serviceName]);
        $serviceId = $this->db->lastInsertId();

        // Store metrics
        $stmt = $this->db->prepare("INSERT INTO metrics (service_id, metric_name, metric_value) VALUES (?, ?, ?)");
        foreach ($metrics as $name => $value) {
            $stmt->execute([$serviceId, $name, json_encode($value)]);
        }

        // Broadcast to WebSocket clients if server is available
        if ($this->websocketServer) {
            $this->broadcastMetrics($serviceName, $metrics);
        }
    }

    private function broadcastMetrics($serviceName, $metrics) {
        $data = json_encode([
            'type' => 'metrics',
            'serviceName' => $serviceName,
            'metrics' => $metrics,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        if (method_exists($this->websocketServer, 'broadcast')) {
            $this->websocketServer->broadcast($data);
        }
    }
}