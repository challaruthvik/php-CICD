<?php
require_once __DIR__ . '/../vendor/autoload.php';
header('Content-Type: application/json');

use App\Database\DatabaseConnection;

try {
    $db = DatabaseConnection::getInstance()->getConnection();
    
    // Build the query with filters
    $query = "SELECT e.*, COUNT(c.id) as commit_count 
              FROM github_events e 
              LEFT JOIN (
                  SELECT id, JSON_EXTRACT(details, '$.commits') as commits 
                  FROM github_events 
                  WHERE event_type = 'push'
              ) c ON e.id = c.id 
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($_GET['start_date'])) {
        $query .= " AND DATE(e.created_at) >= ?";
        $params[] = $_GET['start_date'];
    }
    
    if (!empty($_GET['end_date'])) {
        $query .= " AND DATE(e.created_at) <= ?";
        $params[] = $_GET['end_date'];
    }
    
    if (!empty($_GET['type'])) {
        $query .= " AND e.event_type = ?";
        $params[] = $_GET['type'];
    }
    
    if (!empty($_GET['repository'])) {
        $query .= " AND e.repository = ?";
        $params[] = $_GET['repository'];
    }
    
    if (!empty($_GET['author'])) {
        $query .= " AND e.author = ?";
        $params[] = $_GET['author'];
    }
    
    $query .= " GROUP BY e.id ORDER BY e.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process each event to ensure proper JSON formatting
    foreach ($events as &$event) {
        if (isset($event['details'])) {
            $details = json_decode($event['details'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $event['details'] = $details;
            }
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $events,
        'metadata' => [
            'total' => count($events),
            'filtered' => count($events)
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}