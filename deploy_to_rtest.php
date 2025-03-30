<?php
/**
 * Simple Deployment to rtest Directory
 * This script deploys files to the rtest directory on your Hostinger server
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

echo "Starting deployment to rtest directory...\n";
echo "======================================\n\n";

// FTP connection details
$host = str_replace('ftp://', '', $_ENV['HOSTINGER_HOST']);
$username = trim($_ENV['HOSTINGER_USERNAME']);
$password = $_ENV['HOSTINGER_PASSWORD'];
$port = (int)$_ENV['HOSTINGER_PORT'];
$remotePath = $_ENV['HOSTINGER_REMOTE_PATH'];

echo "Host: {$host}\n";
echo "Username: {$username}\n";
echo "Port: {$port}\n";
echo "Remote Path: {$remotePath}\n\n";

try {
    // Step 1: Connect to FTP server
    echo "Step 1: Connecting to FTP server...\n";
    
    $conn = ftp_connect($host, $port);
    if (!$conn) {
        throw new Exception("Could not connect to FTP server at {$host}:{$port}");
    }
    
    echo "FTP connection established!\n";
    
    // Login to FTP
    echo "Logging in to FTP server...\n";
    if (!ftp_login($conn, $username, $password)) {
        throw new Exception("Login failed with the provided credentials");
    }
    
    echo "FTP login successful!\n";
    
    // Set passive mode
    ftp_pasv($conn, true);
    
    // List root directory
    echo "\nListing root directory contents:\n";
    $rootContents = ftp_nlist($conn, '/');
    if ($rootContents) {
        foreach ($rootContents as $item) {
            echo "- {$item}\n";
        }
    } else {
        echo "Could not list root directory\n";
    }
    
    // Step 2: Check if 'rtest' directory exists
    echo "\nStep 2: Checking if '{$remotePath}' directory exists...\n";
    
    if (@ftp_chdir($conn, "/{$remotePath}")) {
        echo "Directory '{$remotePath}' exists!\n";
    } else {
        echo "Directory '{$remotePath}' does not exist. Creating it...\n";
        if (@ftp_mkdir($conn, "/{$remotePath}")) {
            echo "Created '{$remotePath}' directory successfully.\n";
        } else {
            throw new Exception("Failed to create '{$remotePath}' directory");
        }
    }
    
    // Step 3: Upload files from test-website
    echo "\nStep 3: Uploading files from test-website directory...\n";
    
    $websiteDir = __DIR__ . '/test-website';
    if (!is_dir($websiteDir)) {
        throw new Exception("Test website directory not found at {$websiteDir}");
    }
    
    // Change to the rtest directory
    ftp_chdir($conn, "/{$remotePath}");
    
    // Function to upload directory recursively
    function uploadDirectory($conn, $localDir, $remoteDir = '.') {
        $oldDir = ftp_pwd($conn);
        
        if ($remoteDir != '.') {
            // Create directory if it doesn't exist
            if (!@ftp_chdir($conn, $remoteDir)) {
                echo "Creating directory: {$remoteDir}\n";
                ftp_mkdir($conn, $remoteDir);
                ftp_chdir($conn, $remoteDir);
            } else {
                ftp_chdir($conn, $oldDir); // Go back
            }
        }
        
        $contents = scandir($localDir);
        foreach ($contents as $item) {
            if ($item == '.' || $item == '..') continue;
            
            $localPath = $localDir . '/' . $item;
            
            if (is_dir($localPath)) {
                echo "Processing directory: {$item}\n";
                
                // Create and navigate to subdirectory
                if ($remoteDir == '.') {
                    uploadDirectory($conn, $localPath, $item);
                } else {
                    ftp_chdir($conn, $remoteDir);
                    uploadDirectory($conn, $localPath, $item);
                }
            } else {
                echo "Uploading file: {$item}\n";
                
                if ($remoteDir != '.') {
                    ftp_chdir($conn, $remoteDir);
                }
                
                if (!ftp_put($conn, $item, $localPath, FTP_ASCII)) {
                    echo "- Failed to upload {$item}\n";
                }
                
                if ($remoteDir != '.') {
                    ftp_chdir($conn, $oldDir);
                }
            }
        }
        
        ftp_chdir($conn, $oldDir);
    }
    
    // Upload all files from test-website
    uploadDirectory($conn, $websiteDir);
    
    // Step 4: Verify upload
    echo "\nStep 4: Verifying uploaded files...\n";
    ftp_chdir($conn, "/{$remotePath}");
    
    $uploadedFiles = ftp_nlist($conn, '.');
    if ($uploadedFiles) {
        echo "Files in '{$remotePath}' directory:\n";
        foreach ($uploadedFiles as $file) {
            echo "- {$file}\n";
        }
    } else {
        echo "Could not list files in '{$remotePath}' directory\n";
    }
    
    // Close connection
    ftp_close($conn);
    
    echo "\nDeployment completed successfully!\n";
    echo "Your website should now be available at: http://rtest.ruthvikchalla.com/\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    
    if (isset($conn) && $conn) {
        ftp_close($conn);
    }
    
    exit(1);
}