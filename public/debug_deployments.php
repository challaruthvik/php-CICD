<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Sephp\Database\DatabaseConnection;

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Output as HTML for better readability
header('Content-Type: text/html');

echo "<h1>Deployments Table Debug</h1>";

try {
    // Get database connection
    $db = DatabaseConnection::getInstance()->getConnection();
    
    // Get table structure
    echo "<h2>Table Structure:</h2>";
    $result = $db->query('DESCRIBE deployments');
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show sample data
    echo "<h2>Sample Data:</h2>";
    $result = $db->query('SELECT * FROM deployments LIMIT 3');
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rows) > 0) {
        echo "<table border='1'><tr>";
        foreach (array_keys($rows[0]) as $key) {
            echo "<th>{$key}</th>";
        }
        echo "</tr>";
        
        foreach ($rows as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . (is_null($value) ? "NULL" : htmlspecialchars($value)) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data in deployments table.</p>";
    }
    
    // Test INSERT statement simulation
    echo "<h2>Insert Statement Test:</h2>";
    
    // Sample data
    $sampleData = [
        'repository' => 'test-repo',
        'branch' => 'main',
        'environment' => 'development',
        'target' => 'local'
    ];
    
    // Check if the table has target or destination column
    $stmt = $db->query("DESCRIBE `deployments`");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    
    $hasTarget = in_array('target', $columns);
    $hasDestination = in_array('destination', $columns);
    
    echo "<p>Has 'target' column: " . ($hasTarget ? "Yes" : "No") . "</p>";
    echo "<p>Has 'destination' column: " . ($hasDestination ? "Yes" : "No") . "</p>";
    
    // Build the SQL based on existing columns
    $sql = "INSERT INTO deployments (repository, branch, ";
    
    if (in_array('environment', $columns)) {
        $sql .= "environment, ";
    }
    
    if ($hasTarget) {
        $sql .= "target, ";
    } else if ($hasDestination) {
        $sql .= "destination, ";
    }
    
    $sql .= "status";
    
    if (in_array('deployment_config', $columns)) {
        $sql .= ", deployment_config";
    }
    
    if (in_array('created_at', $columns)) {
        $sql .= ", created_at";
    }
    
    $sql .= ") VALUES (:repository, :branch, ";
    
    if (in_array('environment', $columns)) {
        $sql .= ":environment, ";
    }
    
    if ($hasTarget || $hasDestination) {
        $sql .= ":target_dest, ";
    }
    
    $sql .= ":status";
    
    if (in_array('deployment_config', $columns)) {
        $sql .= ", :deployment_config";
    }
    
    if (in_array('created_at', $columns)) {
        $sql .= ", NOW()";
    }
    
    $sql .= ")";
    
    echo "<p><strong>Generated SQL:</strong> " . htmlspecialchars($sql) . "</p>";
    
    // Parameters that would be used
    $params = [
        'repository' => $sampleData['repository'],
        'branch' => $sampleData['branch'],
        'status' => 'in_progress',
    ];
    
    if (in_array('environment', $columns)) {
        $params['environment'] = $sampleData['environment'];
    }
    
    if ($hasTarget || $hasDestination) {
        $params['target_dest'] = $sampleData['target'];
    }
    
    if (in_array('deployment_config', $columns)) {
        $params['deployment_config'] = json_encode(['branch' => $sampleData['branch']]);
    }
    
    echo "<p><strong>Parameters:</strong></p>";
    echo "<pre>" . print_r($params, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<div style='color:red'>";
    echo "<h2>Error:</h2>";
    echo "<p>{$e->getMessage()}</p>";
    echo "<p>File: {$e->getFile()}</p>";
    echo "<p>Line: {$e->getLine()}</p>";
    echo "</div>";
}