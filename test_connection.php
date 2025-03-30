<?php
// Test direct database connection
try {
    // Connection parameters
    $host = 'localhost';
    $port = 3308;
    $dbname = 'sephp_monitoring'; // Try with and without specifying the database
    $username = 'root';
    $password = 'admin';
    
    echo "Attempting to connect to MySQL on $host:$port with user '$username' and password '$password'...\n";
    
    // Try connecting without specifying database first
    $conn1 = new PDO("mysql:host=$host;port=$port", $username, $password);
    $conn1->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Successfully connected to MySQL server without specifying database!\n";
    
    // Check if the database exists
    $stmt = $conn1->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Available databases:\n";
    foreach ($databases as $db) {
        echo "- $db\n";
    }
    
    if (in_array($dbname, $databases)) {
        echo "Database '$dbname' exists. Trying to connect directly...\n";
        // Now try with the specific database
        $conn2 = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
        $conn2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Successfully connected to database '$dbname'!\n";
    } else {
        echo "Database '$dbname' does not exist. You may need to create it.\n";
    }
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?>