<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

echo "Testing FTP Connection to Hostinger...\n";
echo "=====================================\n";

// Remove the "ftp://" prefix if present
$host = str_replace('ftp://', '', $_ENV['HOSTINGER_HOST']);
$username = trim($_ENV['HOSTINGER_USERNAME']); // Trim any whitespace
$password = $_ENV['HOSTINGER_PASSWORD'];
$port = (int)$_ENV['HOSTINGER_PORT'];
$remotePath = $_ENV['HOSTINGER_REMOTE_PATH'];

echo "Host: " . $host . "\n";
echo "Username: " . $username . "\n";
echo "Port: " . $port . "\n";
echo "Remote Path: " . $remotePath . "\n\n";

try {
    echo "Connecting to FTP server...\n";
    
    // Create FTP connection
    $conn = ftp_connect($host, $port, 30);
    
    if (!$conn) {
        throw new Exception("Could not connect to FTP server at $host");
    }
    
    echo "Connection established successfully!\n";
    
    // Try to login
    echo "Attempting login...\n";
    $login = ftp_login($conn, $username, $password);
    
    if (!$login) {
        throw new Exception("Login failed with the provided credentials");
    }
    
    echo "Login successful!\n\n";
    
    // Set passive mode
    echo "Setting passive mode...\n";
    ftp_pasv($conn, true);
    
    // Try to change to the specified directory
    echo "Checking if remote path exists: $remotePath...\n";
    
    if (@ftp_chdir($conn, $remotePath)) {
        echo "Successfully changed to directory: $remotePath\n";
    } else {
        echo "Remote directory doesn't exist. Attempting to create it...\n";
        
        // Try to create the directory
        $pathParts = explode('/', trim($remotePath, '/'));
        $currentPath = '';
        
        foreach ($pathParts as $part) {
            if (empty($part)) continue;
            
            $currentPath .= '/' . $part;
            
            if (!@ftp_chdir($conn, $currentPath)) {
                if (@ftp_mkdir($conn, $currentPath)) {
                    echo "Created directory: $currentPath\n";
                    ftp_chdir($conn, $currentPath);
                } else {
                    throw new Exception("Failed to create directory: $currentPath");
                }
            }
        }
        
        echo "Directory structure created successfully!\n";
    }
    
    // List contents to verify
    echo "\nListing directory contents:\n";
    $contents = ftp_nlist($conn, '.');
    if (empty($contents)) {
        echo "Directory is empty.\n";
    } else {
        foreach ($contents as $item) {
            echo "- $item\n";
        }
    }
    
    // Try to create a test file
    echo "\nCreating a test file...\n";
    $testContent = "This is a test file created on " . date('Y-m-d H:i:s') . "\n";
    $tempFile = tempnam(sys_get_temp_dir(), 'ftp_test');
    file_put_contents($tempFile, $testContent);
    
    if (ftp_put($conn, 'test_connection.txt', $tempFile, FTP_ASCII)) {
        echo "Test file uploaded successfully!\n";
    } else {
        throw new Exception("Failed to upload test file");
    }
    
    // Clean up
    unlink($tempFile);
    
    // Close the connection
    ftp_close($conn);
    echo "\nFTP test completed successfully! Your credentials work.\n";
    echo "You can now use the Hostinger deployment feature.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    if (isset($conn) && $conn) {
        ftp_close($conn);
    }
    echo "FTP test failed. Please check your credentials and try again.\n";
    exit(1);
}
