<?php

namespace App\Services;

/**
 * SystemMetricsCollector - Collects real system metrics from the server
 */
class SystemMetricsCollector 
{
    /**
     * Get current system metrics (CPU, Memory, Disk)
     * 
     * @return array Array containing system metrics
     */
    public function collectMetrics(): array
    {
        return [
            'cpu_usage' => $this->getCpuUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'load_average' => $this->getLoadAverage(),
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get real CPU usage
     * 
     * @return float CPU usage as percentage
     */
    public function getCpuUsage(): float 
    {
        // Windows implementation
        if (PHP_OS_FAMILY === 'Windows') {
            // Use Windows Management Instrumentation (WMI) to get CPU usage
            $cmd = 'wmic cpu get LoadPercentage /value';
            $output = [];
            exec($cmd, $output);
            
            foreach ($output as $line) {
                if (strpos($line, 'LoadPercentage') !== false) {
                    return (float) trim(explode('=', $line)[1]);
                }
            }
            return 0;
        } 
        // Linux/Unix implementation
        else {
            $load = sys_getloadavg();
            $cores = $this->getCpuCores();
            // Convert load average to percentage, considering the number of cores
            return $cores > 0 ? min(round(($load[0] / $cores) * 100, 2), 100) : 0;
        }
    }

    /**
     * Get number of CPU cores
     * 
     * @return int Number of CPU cores
     */
    private function getCpuCores(): int 
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = 'wmic cpu get NumberOfCores';
            $output = [];
            exec($cmd, $output);
            
            if (isset($output[1])) {
                return (int) trim($output[1]);
            }
            return 1;
        } else {
            return (int) shell_exec('nproc') ?: 1;
        }
    }

    /**
     * Get real memory usage
     * 
     * @return float Memory usage as percentage
     */
    public function getMemoryUsage(): float 
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Get memory information using Windows Management Instrumentation (WMI)
            $cmd = 'wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value';
            $output = [];
            exec($cmd, $output);
            
            $freeMemory = 0;
            $totalMemory = 0;
            
            foreach ($output as $line) {
                if (strpos($line, 'FreePhysicalMemory') !== false) {
                    $freeMemory = (float) trim(explode('=', $line)[1]);
                } else if (strpos($line, 'TotalVisibleMemorySize') !== false) {
                    $totalMemory = (float) trim(explode('=', $line)[1]);
                }
            }
            
            if ($totalMemory > 0) {
                return round(100 - (($freeMemory / $totalMemory) * 100), 2);
            }
            return 0;
        } else {
            $memInfo = file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+) kB/i', $memInfo, $totalMatches);
            preg_match('/MemAvailable:\s+(\d+) kB/i', $memInfo, $availableMatches);
            
            if (!empty($totalMatches) && !empty($availableMatches)) {
                $total = (int) $totalMatches[1];
                $available = (int) $availableMatches[1];
                
                if ($total > 0) {
                    return round(100 - (($available / $total) * 100), 2);
                }
            }
            return 0;
        }
    }

    /**
     * Get real disk usage of the main drive
     * 
     * @return float Disk usage as percentage
     */
    public function getDiskUsage(): float 
    {
        // Get disk usage of root drive
        $drive = PHP_OS_FAMILY === 'Windows' ? 'C:' : '/';
        
        $total = disk_total_space($drive);
        $free = disk_free_space($drive);
        
        if ($total > 0) {
            return round(100 - (($free / $total) * 100), 2);
        }
        
        return 0;
    }

    /**
     * Get load average
     * 
     * @return array Load average for 1, 5 and 15 minutes
     */
    public function getLoadAverage(): array 
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows doesn't have a direct equivalent to load average
            // Use CPU usage as an approximation
            $cpuUsage = $this->getCpuUsage();
            return [$cpuUsage, $cpuUsage, $cpuUsage];
        } else {
            return sys_getloadavg();
        }
    }
}