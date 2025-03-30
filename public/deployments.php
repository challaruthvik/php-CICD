<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Sephp\Database\DatabaseConnection;

// Temporarily enable error reporting to debug the issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Check if this is a request for a specific deployment
if (isset($_GET['id'])) {
    getDeploymentDetails((int)$_GET['id']);
} 
// Check if this is creating a new deployment
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    createDeployment($_POST);
} 
// Otherwise, list all deployments
else {
    listDeployments();
}

/**
 * Get list of all deployments
 */
function listDeployments() {
    try {
        $db = DatabaseConnection::getInstance()->getConnection();
        
        $stmt = $db->query("
            SELECT * FROM deployments 
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        
        $deployments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format branch information from deployment_config if available
        foreach ($deployments as &$deployment) {
            // Format branch information
            if (isset($deployment['deployment_config']) && !empty($deployment['deployment_config'])) {
                $config = json_decode($deployment['deployment_config'], true);
                if (isset($config['branch'])) {
                    $deployment['branch'] = $config['branch'];
                } else {
                    $deployment['branch'] = 'main';
                }
            } else {
                $deployment['branch'] = 'main';
            }
            
            // Map destination to target if needed for frontend consistency
            if (isset($deployment['destination']) && !isset($deployment['target'])) {
                $deployment['target'] = $deployment['destination'];
            }
        }
        
        // Return the deployments directly (not wrapped in a 'data' property)
        echo json_encode($deployments);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Get details of a specific deployment
 */
function getDeploymentDetails($id) {
    try {
        $db = DatabaseConnection::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM deployments WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        $deployment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$deployment) {
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'message' => 'Deployment not found'
            ]);
            return;
        }
        
        // Format branch information
        if (isset($deployment['deployment_config']) && !empty($deployment['deployment_config'])) {
            $config = json_decode($deployment['deployment_config'], true);
            if (isset($config['branch'])) {
                $deployment['branch'] = $config['branch'];
            } else {
                $deployment['branch'] = 'main';
            }
        } else {
            $deployment['branch'] = 'main';
        }
        
        // Map destination to target if needed for frontend consistency
        if (isset($deployment['destination']) && !isset($deployment['target'])) {
            $deployment['target'] = $deployment['destination'];
        }
        
        echo json_encode($deployment);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Create a new deployment
 */
function createDeployment($data) {
    try {
        // Validate required fields
        $requiredFields = ['repository', 'environment', 'target'];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => "Missing required field: $field"
                ]);
                return;
            }
        }
        
        // Get database connection
        $db = DatabaseConnection::getInstance()->getConnection();
        
        // Get table columns
        $stmt = $db->query("DESCRIBE `deployments`");
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }
        
        // Prepare parameters based on existing columns
        $params = [];
        $fields = [];
        $placeholders = [];
        
        // Handle basic fields
        if (in_array('repository', $columns)) {
            $fields[] = 'repository';
            $placeholders[] = ':repository';
            $params['repository'] = $data['repository'];
        }
        
        if (in_array('branch', $columns)) {
            $fields[] = 'branch';
            $placeholders[] = ':branch';
            $params['branch'] = isset($data['branch']) ? $data['branch'] : 'main';
        }
        
        if (in_array('environment', $columns)) {
            $fields[] = 'environment';
            $placeholders[] = ':environment';
            $params['environment'] = $data['environment'];
        }
        
        // Handle target and destination fields separately
        if (in_array('target', $columns)) {
            $fields[] = 'target';
            $placeholders[] = ':target';
            $params['target'] = $data['target'];
        }
        
        if (in_array('destination', $columns)) {
            $fields[] = 'destination';
            $placeholders[] = ':destination';
            // For now, just use the same value for both target and destination
            $params['destination'] = isset($data['target']) ? $data['target'] : 'rtest.ruthvikchalla.com';
        }
        
        if (in_array('status', $columns)) {
            $fields[] = 'status';
            $placeholders[] = ':status';
            $params['status'] = 'pending';
        }
        
        // Handle commit_hash
        if (in_array('commit_hash', $columns)) {
            $fields[] = 'commit_hash';
            $placeholders[] = ':commit_hash';
            $params['commit_hash'] = isset($data['commit_hash']) ? $data['commit_hash'] : '1a2b3c4d5e6f7g8h9i0j';
        }
        
        // Handle deployment_config
        if (in_array('deployment_config', $columns)) {
            $config = [
                'branch' => isset($data['branch']) ? $data['branch'] : 'main'
            ];
            $fields[] = 'deployment_config';
            $placeholders[] = ':deployment_config';
            $params['deployment_config'] = json_encode($config);
        }
        
        // Add initial log message
        if (in_array('log', $columns)) {
            $timestamp = date('Y-m-d H:i:s');
            $fields[] = 'log';
            $placeholders[] = ':log';
            $params['log'] = "[$timestamp] Deployment created and queued for processing...";
        }
        
        // Handle created_at if it doesn't have a default value
        if (in_array('created_at', $columns)) {
            $fields[] = 'created_at';
            $placeholders[] = 'NOW()';
        }
        
        // Build the SQL query
        $sql = "INSERT INTO deployments (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        // Execute the query
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        $deploymentId = $db->lastInsertId();
        
        // Return the newly created deployment
        $stmt = $db->prepare("SELECT * FROM deployments WHERE id = :id");
        $stmt->execute(['id' => $deploymentId]);
        $deployment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Map destination to target if needed for frontend consistency
        if (isset($deployment['destination']) && !isset($deployment['target'])) {
            $deployment['target'] = $deployment['destination'];
        }
        
        // Launch background process to handle the deployment
        triggerBackgroundDeployment($deploymentId);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Deployment created successfully and processing started',
            'data' => $deployment
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()
        ]);
    }
}

/**
 * Trigger background deployment process
 */
function triggerBackgroundDeployment($deploymentId) {
    // Path to the deployment processor script
    $scriptPath = dirname(__DIR__) . '/process_deployment.php';
    
    // Command to run the script in the background
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows version
        $cmd = "start /B php \"$scriptPath\" $deploymentId > nul 2>&1";
        pclose(popen($cmd, "r"));
    } else {
        // Linux/Unix version
        $cmd = "php \"$scriptPath\" $deploymentId > /dev/null 2>&1 &";
        exec($cmd);
    }
}