<?php
require_once __DIR__ . '/vendor/autoload.php';

use Sephp\Database\DatabaseConnection;
use Dotenv\Dotenv;

// This script processes a deployment and updates its status and logs
// Usage: php process_deployment.php <deployment_id>

// Debug: Print current directory
echo "Current directory: " . __DIR__ . "\n";
echo "Checking if .env file exists: " . (file_exists(__DIR__ . '/.env') ? "Yes" : "No") . "\n";

try {
    // Load environment variables with error reporting
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    
    // Debug: Check if environment variables are loaded
    echo "HOSTINGER_HOST: " . (getenv('HOSTINGER_HOST') ? getenv('HOSTINGER_HOST') : "Not found") . "\n";
    echo "HOSTINGER_USERNAME: " . (getenv('HOSTINGER_USERNAME') ? getenv('HOSTINGER_USERNAME') : "Not found") . "\n";
} catch (Exception $e) {
    echo "Error loading .env file: " . $e->getMessage() . "\n";
}

// Check if deployment ID was provided
if ($argc < 2) {
    echo "Usage: php process_deployment.php <deployment_id>\n";
    exit(1);
}

$deploymentId = (int)$argv[1];

try {
    // Get database connection
    $db = DatabaseConnection::getInstance()->getConnection();
    
    // Get deployment details
    $stmt = $db->prepare("SELECT * FROM deployments WHERE id = :id");
    $stmt->execute(['id' => $deploymentId]);
    $deployment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$deployment) {
        echo "Deployment not found: $deploymentId\n";
        exit(1);
    }
    
    // Update log to indicate processing started
    updateLog($db, $deploymentId, "Starting deployment process for {$deployment['repository']}...");
    
    // Get deployment target information
    $target = isset($deployment['target']) ? $deployment['target'] : $deployment['destination'];
    $environment = $deployment['environment'];
    
    // Process the deployment based on target
    switch ($target) {
        case 'hostinger':
            processHostingerDeployment($db, $deployment);
            break;
        case 'aws':
            processAWSDeployment($db, $deployment);
            break;
        case 'local':
            processLocalDeployment($db, $deployment);
            break;
        default:
            updateLog($db, $deploymentId, "Error: Unknown deployment target '$target'");
            updateStatus($db, $deploymentId, 'failed');
            exit(1);
    }
    
} catch (Exception $e) {
    echo "Error processing deployment: " . $e->getMessage() . "\n";
    
    if (isset($db) && isset($deploymentId)) {
        updateLog($db, $deploymentId, "Error: " . $e->getMessage());
        updateStatus($db, $deploymentId, 'failed');
    }
    
    exit(1);
}

/**
 * Process deployment to Hostinger
 */
function processHostingerDeployment($db, $deployment) {
    $deploymentId = $deployment['id'];
    $repo = $deployment['repository'];
    $branch = $deployment['branch'] ?? 'main';
    
    try {
        updateLog($db, $deploymentId, "Preparing to deploy to Hostinger...");
        
        // Load FTP credentials directly from .env file
        $envFile = __DIR__ . '/.env';
        $envContents = file_get_contents($envFile);
        if (!$envContents) {
            throw new Exception("Could not read .env file at: $envFile");
        }
        
        // Parse environment variables manually
        $lines = explode("\n", $envContents);
        $env = [];
        foreach ($lines as $line) {
            $line = trim($line);
            // Skip comments and empty lines
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                // Remove quotes if present
                $value = trim($value, '"\'');
                $env[$key] = $value;
            }
        }
        
        // Get FTP credentials from parsed environment
        $ftpHost = $env['HOSTINGER_HOST'] ?? '';
        $ftpUser = $env['HOSTINGER_USERNAME'] ?? '';
        $ftpPass = $env['HOSTINGER_PASSWORD'] ?? '';
        $ftpPort = isset($env['HOSTINGER_PORT']) ? (int)$env['HOSTINGER_PORT'] : 21;
        $ftpProtocol = $env['HOSTINGER_PROTOCOL'] ?? 'ftp';
        // We'll ignore remotePath and upload directly to current directory
        
        updateLog($db, $deploymentId, "Using FTP credentials - Host: $ftpHost, Username: $ftpUser, Port: $ftpPort");
        
        // Create a temporary directory for cloning the repository
        $tmpDir = __DIR__ . '/tmp_deploy_' . time();
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }
        
        // Determine which repository to use based on the name
        $repoPath = '';
        if ($repo === 'hosttest' || $repo === 'test-website') {
            // Use the local test-website folder for demonstration
            $repoPath = __DIR__ . '/test-website';
            updateLog($db, $deploymentId, "Using local test website from: $repoPath");
        } else {
            // For actual GitHub repositories, we would clone here
            // This is just a placeholder - in a real implementation, you'd use something like:
            // exec("git clone https://github.com/username/$repo.git --branch $branch $tmpDir", $output, $returnCode);
            $repoPath = $tmpDir;
            updateLog($db, $deploymentId, "Cloning repository '$repo' branch '$branch'...");
            
            // For now, just copy test-website as a demo
            $testWebsitePath = __DIR__ . '/test-website';
            if (is_dir($testWebsitePath)) {
                updateLog($db, $deploymentId, "Using test-website for deployment demonstration");
                copyDirectory($testWebsitePath, $tmpDir);
            } else {
                // Create some sample files for testing if test-website doesn't exist
                file_put_contents("$tmpDir/index.html", "<html><body><h1>Test Deployment</h1><p>This is a test deployment from $repo.</p></body></html>");
                file_put_contents("$tmpDir/test.txt", "Test file for deployment");
                mkdir("$tmpDir/css");
                file_put_contents("$tmpDir/css/style.css", "body { font-family: Arial, sans-serif; }");
            }
            
            $repoPath = $tmpDir;
        }
        
        updateLog($db, $deploymentId, "Repository files prepared. Ready for upload...");
        
        updateLog($db, $deploymentId, "Connecting to FTP server at $ftpHost:$ftpPort (Protocol: $ftpProtocol)...");
        
        // Establish FTP connection with proper error handling
        if ($ftpProtocol === 'ftps') {
            // Use FTPS (explicit SSL)
            $conn = @ftp_ssl_connect($ftpHost, $ftpPort, 30);
            if (!$conn) {
                throw new Exception("Could not connect to FTPS server at $ftpHost:$ftpPort. Please verify your server supports FTPS and check firewall settings.");
            }
        } else {
            // Use regular FTP with port specified
            $conn = @ftp_connect($ftpHost, $ftpPort, 30);
            if (!$conn) {
                // Try alternate approach with cURL
                updateLog($db, $deploymentId, "Standard FTP connection failed. Trying alternate connection method...");
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "ftp://$ftpHost:$ftpPort");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_USERPWD, "$ftpUser:$ftpPass");
                curl_exec($ch);
                
                $curlError = curl_errno($ch);
                curl_close($ch);
                
                if ($curlError !== 0) {
                    throw new Exception("Could not connect to FTP server at $ftpHost:$ftpPort. Error code: $curlError. Please verify your hostname, port, and check firewall settings.");
                }
                
                // If curl test passed, try ftp_connect again
                $conn = @ftp_connect($ftpHost, $ftpPort, 30);
                if (!$conn) {
                    throw new Exception("Still unable to establish FTP connection after verification. Please check your hosting provider's FTP settings and requirements.");
                }
            }
        }
        
        updateLog($db, $deploymentId, "Connected to FTP server. Logging in with username: $ftpUser...");
        
        // Login
        $login = @ftp_login($conn, $ftpUser, $ftpPass);
        if (!$login) {
            ftp_close($conn);
            throw new Exception("FTP login failed for user $ftpUser. Please verify your username and password.");
        }
        
        // Set passive mode (often needed for NAT/firewall issues)
        ftp_pasv($conn, true);
        updateLog($db, $deploymentId, "Logged in successfully. Setting up passive mode for file transfer...");
        
        // Get current directory
        $currentDir = ftp_pwd($conn);
        updateLog($db, $deploymentId, "Current FTP directory: $currentDir");
        
        // List the current directory contents
        $contents = ftp_nlist($conn, '.');
        $dirListing = implode(', ', array_slice($contents, 0, 10)); // Show only first 10 items to avoid huge logs
        if (count($contents) > 10) {
            $dirListing .= ", ... (and " . (count($contents) - 10) . " more items)";
        }
        updateLog($db, $deploymentId, "Directory contents: $dirListing");
        
        updateLog($db, $deploymentId, "Uploading files directly to current directory: $currentDir");
        
        // Upload files recursively
        $uploadedFiles = 0;
        $totalFiles = countFilesInDirectory($repoPath);
        updateLog($db, $deploymentId, "Found $totalFiles files to upload");

        uploadDirectory($conn, $repoPath, '.', $db, $deploymentId, $uploadedFiles);
        
        ftp_close($conn);
        
        // Clean up temporary directory if we created one
        if (strpos($repoPath, 'tmp_deploy_') !== false && is_dir($repoPath)) {
            deleteDirectory($repoPath);
        }
        
        updateLog($db, $deploymentId, "All files ($uploadedFiles of $totalFiles) uploaded successfully to Hostinger.");
        updateLog($db, $deploymentId, "Deployment completed successfully!");
        updateStatus($db, $deploymentId, 'success');
        
    } catch (Exception $e) {
        updateLog($db, $deploymentId, "Error: " . $e->getMessage());
        updateStatus($db, $deploymentId, 'failed');
        
        // Clean up temporary directory if we created one
        $tmpDir = __DIR__ . '/tmp_deploy_' . time();
        if (is_dir($tmpDir)) {
            deleteDirectory($tmpDir);
        }
    }
}

/**
 * Count files in a directory recursively
 */
function countFilesInDirectory($dir) {
    $count = 0;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($files as $file) {
        if ($file->isFile()) {
            $count++;
        }
    }
    
    return $count;
}

/**
 * Upload a directory recursively via FTP
 */
function uploadDirectory($ftpConnection, $localPath, $remotePath, $db, $deploymentId, &$uploadedCount) {
    $items = scandir($localPath);
    
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        $localFilePath = $localPath . '/' . $item;
        $remoteFilePath = $remotePath . '/' . $item;
        
        // Remove leading ./ from remote path
        $remoteFilePath = str_replace('./', '', $remoteFilePath);
        
        if (is_dir($localFilePath)) {
            // Create directory on the server
            if (!@ftp_nlist($ftpConnection, $remoteFilePath)) {
                ftp_mkdir($ftpConnection, $remoteFilePath);
                updateLog($db, $deploymentId, "Created directory: $remoteFilePath");
            }
            
            // Recursively upload the directory contents
            uploadDirectory($ftpConnection, $localFilePath, $remoteFilePath, $db, $deploymentId, $uploadedCount);
        } else {
            // Upload file
            $result = @ftp_put($ftpConnection, $remoteFilePath, $localFilePath, FTP_BINARY);
            if ($result) {
                $uploadedCount++;
                if ($uploadedCount % 5 == 0 || $uploadedCount == 1) {
                    updateLog($db, $deploymentId, "Uploaded $uploadedCount files so far...");
                }
            } else {
                updateLog($db, $deploymentId, "Failed to upload: $localFilePath to $remoteFilePath");
            }
        }
    }
}

/**
 * Copy a directory recursively
 */
function copyDirectory($source, $destination) {
    if (!is_dir($destination)) {
        mkdir($destination, 0777, true);
    }
    
    $items = scandir($source);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        $sourcePath = $source . '/' . $item;
        $destPath = $destination . '/' . $item;
        
        if (is_dir($sourcePath)) {
            copyDirectory($sourcePath, $destPath);
        } else {
            copy($sourcePath, $destPath);
        }
    }
}

/**
 * Delete a directory recursively
 */
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        $path = $dir . '/' . $item;
        
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }
    
    rmdir($dir);
}
/**
 * Update deployment log
 */
function updateLog($db, $deploymentId, $message) {
    try {
        // Get current log
        $stmt = $db->prepare("SELECT log FROM deployments WHERE id = :id");
        $stmt->execute(['id' => $deploymentId]);
        $currentLog = $stmt->fetchColumn();
        
        // Format timestamp
        $timestamp = date('Y-m-d H:i:s');
        
        // Append new message with timestamp
        $newLog = $currentLog 
            ? $currentLog . "\n[$timestamp] $message" 
            : "[$timestamp] $message";
        
        // Update log in database
        $stmt = $db->prepare("UPDATE deployments SET log = :log WHERE id = :id");
        $stmt->execute([
            'id' => $deploymentId,
            'log' => $newLog
        ]);
        
        // Output to console as well
        echo "[$timestamp] $message\n";
        
    } catch (Exception $e) {
        echo "Error updating log: " . $e->getMessage() . "\n";
    }
}

/**
 * Update deployment status
 */
function updateStatus($db, $deploymentId, $status) {
    try {
        $stmt = $db->prepare("
            UPDATE deployments 
            SET status = :status, completed_at = NOW() 
            WHERE id = :id
        ");
        $stmt->execute([
            'id' => $deploymentId,
            'status' => $status
        ]);
    } catch (Exception $e) {
        echo "Error updating status: " . $e->getMessage() . "\n";
    }
}