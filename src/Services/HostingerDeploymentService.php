<?php

namespace App\Services;

use App\Database\DatabaseConnection;
use League\Flysystem\Filesystem;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Ftp\FtpConnectionProvider;

class HostingerDeploymentService {
    private $db;
    private $websocketServer;
    private $filesystem;
    private $deploymentId;
    private $logMessages = [];

    public function __construct($websocketServer = null) {
        $this->db = DatabaseConnection::getInstance()->getConnection();
        $this->websocketServer = $websocketServer;
    }

    public function initDeployment($repository, $branch, $environment, $commitSha, $description = '') {
        try {
            // Create a new deployment record
            $stmt = $this->db->prepare("
                INSERT INTO deployments (
                    repository, 
                    environment, 
                    deployment_target,
                    status, 
                    commit_sha, 
                    description, 
                    deployment_config,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $config = json_encode([
                'host' => $_ENV['HOSTINGER_HOST'] ?? '',
                'username' => $_ENV['HOSTINGER_USERNAME'] ?? '',
                'port' => $_ENV['HOSTINGER_PORT'] ?? 21,
                'protocol' => $_ENV['HOSTINGER_PROTOCOL'] ?? 'ftp',
                'remote_path' => $_ENV['HOSTINGER_REMOTE_PATH'] ?? '',
                'branch' => $branch
            ]);

            $stmt->execute([
                $repository,
                $environment,
                'hostinger',
                'pending',
                $commitSha,
                $description,
                $config
            ]);

            $this->deploymentId = $this->db->lastInsertId();
            $this->log("Deployment initialized with ID: {$this->deploymentId}");
            
            return $this->deploymentId;
        } catch (\Exception $e) {
            $this->log("Error initializing deployment: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    public function startDeployment($deploymentId) {
        $this->deploymentId = $deploymentId;
        
        try {
            // Update status and start time
            $stmt = $this->db->prepare("
                UPDATE deployments 
                SET status = 'running', started_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$deploymentId]);
            
            // Get deployment details
            $deployment = $this->getDeploymentById($deploymentId);
            if (!$deployment) {
                throw new \Exception("Deployment not found: {$deploymentId}");
            }
            
            $this->log("Starting deployment for {$deployment['repository']} to {$deployment['environment']}");
            
            // Configure FTP connection
            $config = json_decode($deployment['deployment_config'], true);
            $this->setupFtpConnection($config);
            
            // Execute deployment steps
            $this->executeDeployment($deployment);
            
            // Mark deployment as successful
            $this->completeDeployment('success');
            
            return true;
        } catch (\Exception $e) {
            $this->log("Deployment error: " . $e->getMessage(), 'error');
            $this->completeDeployment('failed', $e->getMessage());
            return false;
        }
    }
    
    private function setupFtpConnection($config) {
        $this->log("Connecting to FTP server: {$config['host']}");
        
        try {
            $connectionProvider = new FtpConnectionProvider(
                $config['host'],
                $config['username'],
                $_ENV['HOSTINGER_PASSWORD'] ?? '',
                $config['port'],
                $config['protocol'] === 'sftp' ? true : false,
                30,
                false
            );
            
            $adapter = new FtpAdapter(
                $connectionProvider,
                $config['remote_path']
            );
            
            $this->filesystem = new Filesystem($adapter);
            $this->log("FTP connection established successfully");
            
            return true;
        } catch (\Exception $e) {
            $this->log("FTP connection failed: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    private function executeDeployment($deployment) {
        $this->log("Preparing deployment files");
        
        // Create temporary directory for deployment
        $tempDir = sys_get_temp_dir() . '/sephp_deployment_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        try {
            // Clone repository
            $config = json_decode($deployment['deployment_config'], true);
            $branch = $config['branch'] ?? 'main';
            $this->log("Cloning repository {$deployment['repository']} branch {$branch}");
            
            $githubToken = $_ENV['GITHUB_TOKEN'] ?? '';
            $githubUsername = $_ENV['GITHUB_USERNAME'] ?? '';
            $repoUrl = "https://{$githubUsername}:{$githubToken}@github.com/{$githubUsername}/{$deployment['repository']}.git";
            
            $command = "git clone --depth 1 --branch {$branch} {$repoUrl} {$tempDir} 2>&1";
            $output = shell_exec($command);
            $this->log($output);
            
            // Upload files to Hostinger
            $this->log("Uploading files to Hostinger");
            $this->uploadDirectory($tempDir, $config['remote_path']);
            
            // Cleanup
            $this->log("Cleaning up temporary files");
            $this->recursiveRemoveDirectory($tempDir);
            
            $this->log("Deployment completed successfully");
            return true;
        } catch (\Exception $e) {
            // Cleanup
            if (file_exists($tempDir)) {
                $this->recursiveRemoveDirectory($tempDir);
            }
            throw $e;
        }
    }
    
    private function uploadDirectory($sourceDir, $targetPath) {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        $filesUploaded = 0;
        $totalFiles = 0;
        
        // Count total files
        foreach ($iterator as $file) {
            if (!$file->isDir()) {
                $totalFiles++;
            }
        }
        
        $this->log("Uploading {$totalFiles} files");
        
        // Reset iterator
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        // Upload files
        foreach ($iterator as $file) {
            $relativePath = substr($file->getPathname(), strlen($sourceDir) + 1);
            $targetFile = $targetPath . '/' . $relativePath;
            
            if ($file->isDir()) {
                try {
                    $this->filesystem->createDirectory($targetFile);
                } catch (\Exception $e) {
                    // Directory may already exist
                }
            } else {
                try {
                    $stream = fopen($file->getPathname(), 'r');
                    $this->filesystem->writeStream($targetFile, $stream);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                    $filesUploaded++;
                    
                    if ($filesUploaded % 10 == 0 || $filesUploaded == $totalFiles) {
                        $this->log("Progress: {$filesUploaded}/{$totalFiles} files uploaded");
                    }
                } catch (\Exception $e) {
                    $this->log("Error uploading {$relativePath}: " . $e->getMessage(), 'error');
                }
            }
        }
        
        $this->log("Upload completed: {$filesUploaded}/{$totalFiles} files");
        return true;
    }
    
    private function completeDeployment($status, $errorMessage = null) {
        try {
            $stmt = $this->db->prepare("
                UPDATE deployments 
                SET status = ?, 
                    completed_at = NOW(), 
                    deployment_log = ? 
                WHERE id = ?
            ");
            
            $logText = implode("\n", $this->logMessages);
            if ($errorMessage) {
                $logText .= "\n\nERROR: " . $errorMessage;
            }
            
            $stmt->execute([$status, $logText, $this->deploymentId]);
            
            // Broadcast deployment status update
            if ($this->websocketServer) {
                $deployment = $this->getDeploymentById($this->deploymentId);
                $this->broadcastDeploymentUpdate($deployment);
            }
            
            return true;
        } catch (\Exception $e) {
            error_log("Error completing deployment: " . $e->getMessage());
            return false;
        }
    }
    
    public function getDeploymentById($deploymentId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM deployments WHERE id = ?");
            $stmt->execute([$deploymentId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error fetching deployment: " . $e->getMessage());
            return null;
        }
    }
    
    public function getDeployments($limit = 20) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM deployments 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->bindParam(1, $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error fetching deployments: " . $e->getMessage());
            return [];
        }
    }
    
    private function log($message, $level = 'info') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp][$level] $message";
        $this->logMessages[] = $logMessage;
        error_log("Deployment #{$this->deploymentId}: $message");
        
        // Broadcast log message if websocket server is available
        if ($this->websocketServer && method_exists($this->websocketServer, 'broadcast')) {
            $this->broadcastLogMessage($message, $level);
        }
    }
    
    private function broadcastLogMessage($message, $level = 'info') {
        $data = json_encode([
            'type' => 'deployment_log',
            'deployment_id' => $this->deploymentId,
            'message' => $message,
            'level' => $level,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        $this->websocketServer->broadcast($data);
    }
    
    private function broadcastDeploymentUpdate($deployment) {
        $data = json_encode([
            'type' => 'deployment_update',
            'deployment' => $deployment,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        $this->websocketServer->broadcast($data);
    }
    
    private function recursiveRemoveDirectory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->recursiveRemoveDirectory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
}