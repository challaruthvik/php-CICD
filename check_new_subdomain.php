<?php
/**
 * Check New Subdomain Path
 * This script checks if the new subdomain path exists and creates it if needed
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
$remotePath = $_ENV['HOSTINGER_REMOTE_PATH'];

echo "Checking new subdomain path on Hostinger...\n";
echo "=========================================\n";
echo "Host: {$host}\n";
echo "Username: {$username}\n";
echo "Port: {$port}\n";
echo "Remote Path: {$remotePath}\n\n";

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
    
    // Check if the remote path exists
    echo "\nChecking if the configured remote path exists: {$remotePath}\n";
    
    if (@ftp_chdir($conn, $remotePath)) {
        echo "Success! The path exists.\n";
        echo "Contents of {$remotePath}:\n";
        
        $contents = ftp_nlist($conn, '.');
        if ($contents) {
            foreach ($contents as $item) {
                if ($item == '.' || $item == '..') continue;
                echo "- {$item}\n";
            }
        } else {
            echo "The directory appears to be empty.\n";
        }
    } else {
        echo "The path does not exist. Attempting to create it...\n";
        
        // Create the directory structure
        $pathParts = explode('/', trim($remotePath, '/'));
        $currentPath = '';
        $success = true;
        
        foreach ($pathParts as $part) {
            if (empty($part)) continue;
            
            $currentPath .= '/' . $part;
            
            if (!@ftp_chdir($conn, $currentPath)) {
                echo "Creating directory: {$currentPath}\n";
                
                if (@ftp_mkdir($conn, $currentPath)) {
                    echo "- Created successfully\n";
                    ftp_chdir($conn, '/'); // Return to root
                } else {
                    echo "- Failed to create\n";
                    $success = false;
                    break;
                }
            } else {
                echo "Directory already exists: {$currentPath}\n";
                ftp_chdir($conn, '/'); // Return to root
            }
        }
        
        if ($success) {
            echo "Successfully created the directory structure: {$remotePath}\n";
        } else {
            echo "Failed to create the complete directory structure\n";
        }
    }
    
    // Create a test HTML file
    echo "\nCreating a test HTML file...\n";
    $testHtmlContent = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Subdomain Test - rtest.ruthvikchalla.com</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        h1 { color: #2c3e50; }
        .success { color: #27ae60; font-weight: bold; }
        .info { margin-top: 30px; background: #f8f9fa; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Subdomain Test for rtest.ruthvikchalla.com</h1>
    <p class="success">If you can see this page, your subdomain is working correctly!</p>
    
    <div class="info">
        <p><strong>Server Information:</strong></p>
        <ul>
            <li>Test file created: <?php echo date('Y-m-d H:i:s'); ?></li>
            <li>Path: <?php echo $remotePath; ?></li>
        </ul>
    </div>
    
    <p>This is a test file uploaded by the SePHP monitoring system.</p>
</body>
</html>
HTML;
    
    $tempFile = tempnam(sys_get_temp_dir(), 'subdomain_test');
    file_put_contents($tempFile, $testHtmlContent);
    
    // Attempt to change to the remote path
    if (@ftp_chdir($conn, $remotePath)) {
        echo "Changed to remote path successfully\n";
        
        // Upload the test file
        if (ftp_put($conn, 'index.html', $tempFile, FTP_ASCII)) {
            echo "Test file uploaded successfully as index.html\n";
            echo "\nYou should be able to access your subdomain at: http://rtest.ruthvikchalla.com/\n";
        } else {
            echo "Failed to upload the test file\n";
        }
    } else {
        echo "Failed to change to the remote path\n";
    }
    
    // Clean up
    unlink($tempFile);
    
    // Close connection
    ftp_close($conn);
    
    echo "\nSubdomain check completed.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    
    if (isset($conn)) {
        ftp_close($conn);
    }
    
    exit(1);
}