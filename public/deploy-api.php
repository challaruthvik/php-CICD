<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\DatabaseConnection;
use App\Services\HostingerDeploymentService;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

header('Content-Type: application/json');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

// Get input data
$inputData = json_decode(file_get_contents('php://input'), true);
if (!$inputData) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid JSON input'
    ]);
    exit;
}

// Initialize database connection
$db = DatabaseConnection::getInstance()->getConnection();

// Process the request
try {
    $action = $inputData['action'] ?? '';
    
    switch ($action) {
        case 'init_deployment':
            // Required fields
            $requiredFields = ['repository', 'branch', 'environment', 'commit_sha'];
            foreach ($requiredFields as $field) {
                if (empty($inputData[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // Initialize the deployment
            $deploymentService = new HostingerDeploymentService();
            $deploymentId = $deploymentService->initDeployment(
                $inputData['repository'],
                $inputData['branch'],
                $inputData['environment'],
                $inputData['commit_sha'],
                $inputData['description'] ?? ''
            );
            
            if (!$deploymentId) {
                throw new Exception("Failed to initialize deployment");
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Deployment initialized',
                'deployment_id' => $deploymentId
            ]);
            
            // Execute deployment in the background
            if ($inputData['start_now'] ?? true) {
                // Use a fire-and-forget approach to run deployment in background
                $command = "php -f " . __DIR__ . "/../run_deployment.php {$deploymentId} > /dev/null 2>&1 &";
                if (PHP_OS_FAMILY === 'Windows') {
                    pclose(popen("start /B " . $command, "r"));
                } else {
                    exec($command);
                }
            }
            break;
            
        case 'start_deployment':
            if (empty($inputData['deployment_id'])) {
                throw new Exception("Missing required field: deployment_id");
            }
            
            $deploymentId = $inputData['deployment_id'];
            
            // Start the deployment in the background
            $command = "php -f " . __DIR__ . "/../run_deployment.php {$deploymentId} > /dev/null 2>&1 &";
            if (PHP_OS_FAMILY === 'Windows') {
                pclose(popen("start /B " . $command, "r"));
            } else {
                exec($command);
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Deployment started',
                'deployment_id' => $deploymentId
            ]);
            break;
            
        case 'get_deployment':
            if (empty($inputData['deployment_id'])) {
                throw new Exception("Missing required field: deployment_id");
            }
            
            $deploymentService = new HostingerDeploymentService();
            $deployment = $deploymentService->getDeploymentById($inputData['deployment_id']);
            
            if (!$deployment) {
                throw new Exception("Deployment not found");
            }
            
            echo json_encode([
                'status' => 'success',
                'deployment' => $deployment
            ]);
            break;
            
        case 'list_deployments':
            $limit = $inputData['limit'] ?? 20;
            $deploymentService = new HostingerDeploymentService();
            $deployments = $deploymentService->getDeployments($limit);
            
            echo json_encode([
                'status' => 'success',
                'deployments' => $deployments
            ]);
            break;
            
        default:
            throw new Exception("Unknown action: $action");
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}