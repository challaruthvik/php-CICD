<?php
/**
 * Upload Path Info File
 * This script uploads the path_info.php file to your Hostinger server
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

echo "Uploading path_info.php to Hostinger...\n";

// FTP connection details
$host = str_replace('ftp://', '', $_ENV['HOSTINGER_HOST']);
$username = trim($_ENV['HOSTINGER_USERNAME']);
$password = $_ENV['HOSTINGER_PASSWORD'];
$port = (int)$_ENV['HOSTINGER_PORT'];

try {
    // Connect to FTP server
    $conn = ftp_connect($host, $port);
    if (!$conn) {
        throw new Exception("Could not connect to FTP server at {$host}:{$port}");
    }
    
    echo "FTP connection established!\n";
    
    // Login
    if (!ftp_login($conn, $username, $password)) {
        throw new Exception("Login failed with the provided credentials");
    }
    
    echo "FTP login successful!\n";
    
    // Set passive mode
    ftp_pasv($conn, true);
    
    // Try multiple potential paths
    $paths = [
        '/public_html/rtest',
        '/rtest',
        '/home/u628082774/domains/amskilled.com/public_html/rtest',
        '/domains/amskilled.com/public_html/rtest'
    ];
    
    $uploadSucceeded = false;
    
    foreach ($paths as $path) {
        echo "Trying to upload to path: {$path}\n";
        
        // Try to change to the directory
        if (@ftp_chdir($conn, $path)) {
            echo "Directory exists, uploading file...\n";
            
            // Upload the file
            if (ftp_put($conn, 'path_info.php', __DIR__ . '/path_info.php', FTP_ASCII)) {
                echo "SUCCESS! path_info.php uploaded to {$path}\n";
                echo "You can access it at: https://amskilled.com" . str_replace('/public_html', '', $path) . "/path_info.php\n";
                $uploadSucceeded = true;
                break;
            } else {
                echo "Failed to upload to {$path}\n";
            }
        } else {
            echo "Directory {$path} does not exist or is not accessible\n";
        }
    }
    
    // Close the connection
    ftp_close($conn);
    
    if (!$uploadSucceeded) {
        throw new Exception("Could not upload the file to any of the tried paths");
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    
    if (isset($conn)) {
        ftp_close($conn);
    }
    
    exit(1);
}