<?php
// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Include the database migrations file
    require_once __DIR__ . '/../src/Database/migrations.php';
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Database migrations completed successfully.'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error running migrations: ' . $e->getMessage()
    ]);
}