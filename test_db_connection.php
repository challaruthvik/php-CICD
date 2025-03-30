<?php

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

echo "Testing database connection...\n";
echo "----------------------------------------\n";
echo "Host: " . $_ENV['DB_HOST'] . "\n";
echo "Port: " . $_ENV['DB_PORT'] . "\n";
echo "Database: " . $_ENV['DB_NAME'] . "\n";
echo "User: " . $_ENV['DB_USER'] . "\n";
echo "Password: " . (empty($_ENV['DB_PASS']) ? '[empty]' : '[set]') . "\n";
echo "----------------------------------------\n\n";

try {
    // Direct PDO connection test
    $dsn = "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}";
    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Direct PDO connection: SUCCESS\n";
    
    // Test query
    $stmt = $pdo->query("SELECT VERSION() as version");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "MySQL Version: " . $result['version'] . "\n\n";
    
    // Test connection using DatabaseConnection class
    echo "Testing connection via DatabaseConnection class...\n";
    require_once __DIR__ . '/src/Database/DatabaseConnection.php';
    
    $db = \App\Database\DatabaseConnection::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("SHOW TABLES");
    echo "Tables in database:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . current($row) . "\n";
    }
    
    echo "\nAll connection tests: PASSED\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    
    // Suggest solutions
    echo "\nPossible solutions:\n";
    echo "1. Check if MySQL is running on port {$_ENV['DB_PORT']}\n";
    echo "2. Verify the database '{$_ENV['DB_NAME']}' exists\n";
    echo "3. Check username and password\n";
    echo "4. If using a different port, make sure it's configured in XAMPP\n";
    
    exit(1);
}