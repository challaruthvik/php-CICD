<?php
/**
 * Direct Deployment Script
 * This script clones a repository and deploys it directly to Hostinger
 * It uses the .env configuration file for settings
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

function recursiveRmdir($dir) {
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                if (is_dir("$dir/$file")) {
                    recursiveRmdir("$dir/$file");
                } else {
                    @unlink("$dir/$file");
                }
            }
        }
        @rmdir($dir);
    }
}

echo "Starting direct deployment to Hostinger...\n";
echo "=========================================\n";

// FTP connection details
$host = $_ENV['HOSTINGER_HOST'];
$username = $_ENV['HOSTINGER_USERNAME'];
$password = $_ENV['HOSTINGER_PASSWORD'];
$port = (int)$_ENV['HOSTINGER_PORT'];
$remotePath = $_ENV['HOSTINGER_REMOTE_PATH'];

echo "Host: {$host}\n";
echo "Username: {$username}\n";
echo "Port: {$port}\n";
echo "Remote Path: {$remotePath}\n";

// GitHub repository details
$repo = $_ENV['GITHUB_REPO'] ?? 'testing';
$branch = $_ENV['GITHUB_BRANCH'] ?? 'main';
$githubUsername = $_ENV['GITHUB_USERNAME'] ?? 'challaruthvik';

// Step 1: Clone the repository
echo "Step 1: Cloning repository {$repo} branch {$branch}...\n";
$tempDir = sys_get_temp_dir() . "/{$repo}_deployment_" . uniqid();

// Use git clone to get the repository
echo "Executing: git clone...\n";
$cloneCommand = "git clone https://github.com/{$githubUsername}/{$repo}.git {$tempDir} --branch {$branch} --single-branch";
$cloneOutput = shell_exec($cloneCommand);
echo "Clone output: {$cloneOutput}\n";

// Step 2: Connect to FTP server
echo "Step 2: Connecting to FTP server...\n";
echo "Connecting to: {$host}:{$port}\n";

$conn = ftp_connect($host, $port);
if (!$conn) {
    echo "Failed to connect to FTP server\n";
    exit(1);
}

echo "FTP connection established!\n";
echo "Logging in with username: {$username}\n";

if (!ftp_login($conn, $username, $password)) {
    echo "FTP login failed\n";
    ftp_close($conn);
    exit(1);
}

echo "FTP login successful!\n";

// Enable passive mode
echo "Setting passive mode...\n";
ftp_pasv($conn, true);

// List the root directory to verify connection
echo "Listing root directory to verify connection:\n";
$rootContents = ftp_nlist($conn, '/');
foreach ($rootContents as $item) {
    echo "- {$item}\n";
}

// Check if remote path exists, create it if not
echo "Checking if remote path {$remotePath} exists...\n";
if (!@ftp_chdir($conn, $remotePath)) {
    echo "Remote path doesn't exist. Creating it...\n";
    
    // Create directory structure
    $pathParts = explode('/', trim($remotePath, '/'));
    $currentPath = '';
    
    foreach ($pathParts as $part) {
        if (empty($part)) continue;
        
        $currentPath .= '/' . $part;
        
        if (!@ftp_chdir($conn, $currentPath)) {
            if (@ftp_mkdir($conn, $currentPath)) {
                echo "Created directory: {$currentPath}\n";
            } else {
                echo "Failed to create directory: {$currentPath}\n";
                ftp_close($conn);
                exit(1);
            }
        }
        
        ftp_chdir($conn, '/'); // Return to root
    }
    
    // Change to the created remote path
    if (!@ftp_chdir($conn, $remotePath)) {
        echo "Failed to change to remote path after creation\n";
        ftp_close($conn);
        exit(1);
    }
} else {
    echo "Remote path exists\n";
}

// Step 3: Upload files
echo "\nStep 3: Uploading files...\n";

// List repository contents
echo "Contents of cloned repository:\n";
$repoContents = scandir($tempDir);
foreach ($repoContents as $item) {
    if ($item != '.' && $item != '..') {
        echo "- {$item}\n";
    }
}

// Function to upload directory recursively
function uploadDirectory($conn, $localDir, $remoteDir) {
    if (!is_dir($localDir)) {
        return;
    }
    
    // Create remote directory if it doesn't exist
    $oldDir = ftp_pwd($conn);
    
    $remotePathParts = explode('/', trim($remoteDir, '/'));
    $dirName = end($remotePathParts);
    
    if (!@ftp_chdir($conn, $remoteDir)) {
        echo "Creating directory: {$dirName}\n";
        ftp_mkdir($conn, $dirName);
    }
    
    ftp_chdir($conn, $remoteDir);
    
    // Upload all files in the directory
    $contents = scandir($localDir);
    foreach ($contents as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        $localPath = $localDir . '/' . $item;
        $remotePath = $item;
        
        if (is_dir($localPath)) {
            // Create and enter subdirectory
            uploadDirectory($conn, $localPath, $remotePath);
        } else {
            // Upload file
            echo "Uploading file: {$item}\n";
            ftp_put($conn, $remotePath, $localPath, FTP_ASCII);
        }
    }
    
    // Return to previous directory
    ftp_chdir($conn, $oldDir);
}

// Change to the remote directory
if (@ftp_chdir($conn, $remotePath)) {
    // Upload all repository files
    $contents = scandir($tempDir);
    foreach ($contents as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        $localPath = $tempDir . '/' . $item;
        
        if (is_dir($localPath)) {
            uploadDirectory($conn, $localPath, $item);
        } else {
            echo "Uploading file: {$item}\n";
            ftp_put($conn, $item, $localPath, FTP_ASCII);
        }
    }
} else {
    echo "Failed to change to remote directory\n";
}

// Step 4: Verify deployment
echo "\nStep 4: Verifying deployment...\n";
echo "Listing files in remote directory:\n";

// Change to the remote path again to verify
ftp_chdir($conn, $remotePath);
$finalContents = ftp_nlist($conn, '.');
foreach ($finalContents as $item) {
    echo "- {$item}\n";
}

// Close connection
ftp_close($conn);

// Clean up
echo "\nCleaning up temporary files...\n";
recursiveRmdir($tempDir);

echo "\nDeployment completed successfully!\n";
echo "Your website should now be available at: http://rtest.ruthvikchalla.com/\n";