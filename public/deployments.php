<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\DatabaseConnection;

header('Content-Type: application/json');

$db = DatabaseConnection::getInstance()->getConnection();

try {
    $stmt = $db->query("
        SELECT * FROM deployments 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    
    $deployments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $deployments
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}