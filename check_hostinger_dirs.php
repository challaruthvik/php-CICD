<?php
/**
 * FTP Directory Checker
 * This script checks all possible directories on your Hostinger server
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// FTP connection details
$host = str_replace('ftp://', '', $_ENV['HOSTINGER_HOST']);
$username = trim($_ENV['HOSTINGER_USERNAME']);
$password = $_ENV['HOSTINGER_PASSWORD'];
$port = (int)$_ENV['HOSTINGER_PORT'];

echo "Checking Hostinger directories...\n";
echo "==============================\n";
echo "Host: {$host}\n";
echo "Username: {$username}\n";
echo "Port: {$port}\n\n";

try {
    echo "Connecting to FTP server...\n";
    
    // Create FTP connection
    $conn = ftp_connect($host, $port, 30);
    if (!$conn) {
        throw new Exception("Could not connect to FTP server at {$host}:{$port}");
    }
    
    echo "Connection established!\n";
    
    // Login to FTP
    echo "Logging in...\n";
    if (!ftp_login($conn, $username, $password)) {
        throw new Exception("Login failed with the provided credentials");
    }
    
    echo "Login successful!\n";
    
    // Set passive mode
    ftp_pasv($conn, true);
    
    // Function to list directories recursively
    function listDirs($conn, $path = '/') {
        echo "Checking path: {$path}\n";
        
        try {
            // Try to change to directory
            if (!@ftp_chdir($conn, $path)) {
                echo "  - Could not access directory: {$path}\n";
                return;
            }
            
            // List contents
            $contents = ftp_nlist($conn, '.');
            
            if ($contents === false) {
                echo "  - Error listing directory\n";
                return;
            }
            
            if (empty($contents)) {
                echo "  - Directory is empty\n";
                return;
            }
            
            echo "  Contents:\n";
            foreach ($contents as $item) {
                if ($item == '.' || $item == '..') continue;
                
                // Check if it's a directory
                $wasDir = @ftp_chdir($conn, $item);
                if ($wasDir) {
                    echo "  - DIR: {$item}/\n";
                    ftp_cdup($conn); // Go back up
                } else {
                    echo "  - FILE: {$item}\n";
                }
            }

            // Check for specific directories we want to explore deeper
            $dirsToCheck = ['public_html', 'domains', 'rtest', 'www'];
            foreach ($dirsToCheck as $dir) {
                if (in_array($dir, $contents) && $path !== "/{$dir}") {
                    $newPath = $path;
                    if (substr($newPath, -1) !== '/') {
                        $newPath .= '/';
                    }
                    $newPath .= $dir;
                    
                    // Check this subdirectory
                    listDirs($conn, $newPath);
                }
            }
        } catch (Exception $e) {
            echo "  Error: " . $e->getMessage() . "\n";
        }
        
        // Return to root
        ftp_chdir($conn, '/');
    }
    
    // Start checking from root
    listDirs($conn, '/');
    
    // Check specific paths that might contain the files
    $pathsToCheck = [
        '/public_html',
        '/public_html/rtest',
        '/home',
        '/home/u628082774',
        '/home/u628082774/domains',
        '/home/u628082774/domains/amskilled.com',
        '/home/u628082774/domains/amskilled.com/public_html',
        '/home/u628082774/domains/amskilled.com/public_html/rtest',
        '/domains',
        '/domains/amskilled.com',
        '/domains/amskilled.com/public_html',
        '/domains/amskilled.com/public_html/rtest',
        '/www',
        '/htdocs',
        '/rtest'
    ];
    
    echo "\nChecking specific paths:\n";
    echo "======================\n";
    
    foreach ($pathsToCheck as $path) {
        echo "\nChecking path: {$path}\n";
        
        if (@ftp_chdir($conn, $path)) {
            echo "Path exists! Contents:\n";
            $contents = ftp_nlist($conn, '.');
            
            if ($contents === false || empty($contents)) {
                echo "  - No files found or error listing directory\n";
            } else {
                foreach ($contents as $item) {
                    if ($item == '.' || $item == '..') continue;
                    echo "  - {$item}\n";
                }
            }
        } else {
            echo "  - Path does not exist\n";
        }
    }
    
    // Close connection
    ftp_close($conn);
    
    echo "\nHostinger directory check completed.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    
    if (isset($conn)) {
        ftp_close($conn);
    }
    
    exit(1);
}