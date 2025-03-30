<?php
/**
 * Subdomain Directory Checker
 * This script checks common subdomain directory paths on Hostinger
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

echo "Checking Hostinger subdomain directories...\n";
echo "=========================================\n";
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
    
    // Check root directory first
    echo "\nListing root directory contents:\n";
    $rootContents = ftp_nlist($conn, '/');
    if ($rootContents) {
        foreach ($rootContents as $item) {
            echo "- {$item}\n";
        }
    } else {
        echo "Could not list root directory\n";
    }
    
    // Common subdomain directory paths on Hostinger
    $potentialSubdomainPaths = [
        '/subdomains',
        '/domains',
        '/public_html/subdomains',
        '/home/u628082774/domains',
        '/home/u628082774/subdomains',
        '/home/u628082774/public_html/subdomains',
        '/home/u628082774/domains/amskilled.com/subdomains',
        '/home/u628082774/domains/rtest.amskilled.com',
        '/home/u628082774/domains/rtest.amskilled.com/public_html',
    ];
    
    echo "\nChecking potential subdomain paths:\n";
    
    foreach ($potentialSubdomainPaths as $path) {
        echo "\nChecking path: {$path}\n";
        
        if (@ftp_chdir($conn, $path)) {
            echo "Path exists! Contents:\n";
            $contents = ftp_nlist($conn, '.');
            
            if ($contents === false || empty($contents)) {
                echo "  - No files found or error listing directory\n";
            } else {
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
            }
        } else {
            echo "  - Path does not exist\n";
        }
    }
    
    // Try to create a test file in a location that might be accessible from rtest.amskilled.com
    echo "\nTrying to identify the correct location for rtest.amskilled.com subdomain...\n";
    
    $possibleSubdomainRoots = [
        '/public_html',
        '/domains/rtest.amskilled.com/public_html',
        '/home/u628082774/domains/rtest.amskilled.com/public_html',
        '/subdomains/rtest/public_html',
        '/home/u628082774/subdomains/rtest',
    ];
    
    $uploadSuccess = false;
    $successfulPath = '';
    
    foreach ($possibleSubdomainRoots as $path) {
        echo "\nTrying path: {$path}\n";
        
        // Check if directory exists
        if (!@ftp_chdir($conn, $path)) {
            echo "  Directory doesn't exist, attempting to create it...\n";
            
            // Try to create the directory structure
            $pathParts = explode('/', trim($path, '/'));
            $currentPath = '';
            $createSuccess = true;
            
            foreach ($pathParts as $part) {
                if (empty($part)) continue;
                
                $currentPath .= '/' . $part;
                
                if (!@ftp_chdir($conn, $currentPath)) {
                    if (@ftp_mkdir($conn, $currentPath)) {
                        echo "  Created directory: {$currentPath}\n";
                    } else {
                        echo "  Failed to create directory: {$currentPath}\n";
                        $createSuccess = false;
                        break;
                    }
                }
                
                ftp_chdir($conn, '/'); // Return to root to avoid path issues
            }
            
            if (!$createSuccess) {
                echo "  Could not create the directory structure for {$path}\n";
                continue;
            }
            
            // Change to the new directory
            if (!@ftp_chdir($conn, $path)) {
                echo "  Failed to change to the newly created directory\n";
                continue;
            }
        }
        
        // Create a simple test file
        echo "  Creating test file in {$path}...\n";
        $testContent = "This is a test file for rtest.amskilled.com subdomain. Created at " . date('Y-m-d H:i:s');
        $tempFile = tempnam(sys_get_temp_dir(), 'subdomain_test');
        file_put_contents($tempFile, $testContent);
        
        if (ftp_put($conn, 'subdomain_test.html', $tempFile, FTP_ASCII)) {
            echo "  SUCCESS: Test file uploaded to {$path}/subdomain_test.html\n";
            unlink($tempFile);
            $uploadSuccess = true;
            $successfulPath = $path;
            break;
        } else {
            echo "  FAILED: Could not upload test file to {$path}\n";
            unlink($tempFile);
        }
    }
    
    if ($uploadSuccess) {
        echo "\n========================================================\n";
        echo "SUCCESS: Test file uploaded to {$successfulPath}/subdomain_test.html\n";
        echo "Try accessing this file at: http://rtest.amskilled.com/subdomain_test.html\n";
        echo "Or at: http://amskilled.com/rtest/subdomain_test.html\n";
        echo "========================================================\n";
        
        // Now try uploading our website files to this location
        echo "\nUploading test website files to the successful path...\n";
        
        $websiteDir = __DIR__ . '/test-website';
        if (!is_dir($websiteDir)) {
            echo "Test website directory not found at {$websiteDir}\n";
        } else {
            // Change to the successful directory
            ftp_chdir($conn, $successfulPath);
            
            // Upload index.html as a test
            $indexFile = $websiteDir . '/index.html';
            if (file_exists($indexFile)) {
                if (ftp_put($conn, 'index.html', $indexFile, FTP_ASCII)) {
                    echo "Successfully uploaded index.html to {$successfulPath}\n";
                } else {
                    echo "Failed to upload index.html\n";
                }
            } else {
                echo "Index file not found at {$indexFile}\n";
            }
        }
    } else {
        echo "\nCould not find or create a suitable directory for the rtest subdomain.\n";
    }
    
    // Close connection
    ftp_close($conn);
    
    echo "\nSubdomain directory check completed.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    
    if (isset($conn)) {
        ftp_close($conn);
    }
    
    exit(1);
}