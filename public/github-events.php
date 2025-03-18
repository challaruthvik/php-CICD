<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\DatabaseConnection;

// Get Github events data
$events = [];
$eventTypes = [];
$repositories = [];
$authors = [];
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$filterType = isset($_GET['type']) ? $_GET['type'] : '';
$filterRepo = isset($_GET['repository']) ? $_GET['repository'] : '';
$filterAuthor = isset($_GET['author']) ? $_GET['author'] : '';

try {
    $db = DatabaseConnection::getInstance()->getConnection();
    
    // Build the query with filters
    $query = "SELECT * FROM github_events WHERE 1=1";
    $params = [];
    
    if (!empty($startDate)) {
        $query .= " AND DATE(created_at) >= ?";
        $params[] = $startDate;
    }
    
    if (!empty($endDate)) {
        $query .= " AND DATE(created_at) <= ?";
        $params[] = $endDate;
    }
    
    if (!empty($filterType)) {
        $query .= " AND event_type = ?";
        $params[] = $filterType;
    }
    
    if (!empty($filterRepo)) {
        $query .= " AND repository = ?";
        $params[] = $filterRepo;
    }
    
    if (!empty($filterAuthor)) {
        $query .= " AND author = ?";
        $params[] = $filterAuthor;
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all event types for filter
    $stmt = $db->query("SELECT DISTINCT event_type FROM github_events ORDER BY event_type");
    $eventTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get all repositories for filter
    $stmt = $db->query("SELECT DISTINCT repository FROM github_events ORDER BY repository");
    $repositories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get all authors for filter
    $stmt = $db->query("SELECT DISTINCT author FROM github_events ORDER BY author");
    $authors = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHub Events | Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 0 0 20px 20px;
        }
        .event-card {
            margin-bottom: 20px;
            transition: all 0.2s;
        }
        .event-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .event-badge {
            font-size: 0.8em;
            padding: 0.4em 0.6em;
        }
        .footer {
            margin-top: 50px;
            padding: 20px 0;
            background-color: #f1f1f1;
        }
        pre {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            max-height: 300px;
            overflow-y: auto;
        }
        .filter-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .event-push { border-left: 4px solid #28a745; }
        .event-pull_request { border-left: 4px solid #fd7e14; }
        .event-deployment { border-left: 4px solid #007bff; }
        .event-deployment_status { border-left: 4px solid #6f42c1; }
        .event-create { border-left: 4px solid #20c997; }
        .event-other { border-left: 4px solid #6c757d; }
        
        .commit-list {
            list-style-type: none;
            padding-left: 0;
        }
        .commit-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .commit-list li:last-child {
            border-bottom: none;
        }
        .commit-hash {
            font-family: monospace;
            background: #f1f1f1;
            padding: 2px 5px;
            border-radius: 3px;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="bi bi-github"></i> GitHub Events</h1>
                    <p class="lead">Monitor GitHub repository activity</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="btn-group">
                        <a href="index.php" class="btn btn-outline-light">
                            <i class="bi bi-house"></i> Dashboard
                        </a>
                        <a href="index.html" class="btn btn-light">
                            <i class="bi bi-speedometer2"></i> Monitoring
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Filter Form -->
        <div class="filter-form">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="text" class="form-control datepicker" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                </div>
                <div class="col-md-2">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="text" class="form-control datepicker" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                </div>
                <div class="col-md-2">
                    <label for="type" class="form-label">Event Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">All Types</option>
                        <?php foreach ($eventTypes as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $filterType === $type ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="repository" class="form-label">Repository</label>
                    <select class="form-select" id="repository" name="repository">
                        <option value="">All Repos</option>
                        <?php foreach ($repositories as $repo): ?>
                            <option value="<?php echo htmlspecialchars($repo); ?>" <?php echo $filterRepo === $repo ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($repo); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="author" class="form-label">Author</label>
                    <select class="form-select" id="author" name="author">
                        <option value="">All Authors</option>
                        <?php foreach ($authors as $author): ?>
                            <option value="<?php echo htmlspecialchars($author); ?>" <?php echo $filterAuthor === $author ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($author); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-filter"></i> Apply Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Stats Summary -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo count($events); ?></h2>
                        <p class="card-text text-muted">Total Events</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo count($repositories); ?></h2>
                        <p class="card-text text-muted">Repositories</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo count($authors); ?></h2>
                        <p class="card-text text-muted">Contributors</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="card-title"><?php 
                            $commitCount = 0;
                            foreach ($events as $event) {
                                $commitCount += (int)$event['commit_count'];
                            }
                            echo $commitCount;
                        ?></h2>
                        <p class="card-text text-muted">Total Commits</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Events Display -->
        <?php if (!empty($events)): ?>
            <div class="row">
                <?php foreach ($events as $event): ?>
                    <?php 
                        $eventClass = 'event-other';
                        switch ($event['event_type']) {
                            case 'push':
                                $eventClass = 'event-push';
                                $badgeClass = 'bg-success';
                                $icon = 'bi-arrow-up-circle';
                                break;
                            case 'pull_request':
                                $eventClass = 'event-pull_request';
                                $badgeClass = 'bg-warning';
                                $icon = 'bi-code-square';
                                break;
                            case 'deployment':
                                $eventClass = 'event-deployment';
                                $badgeClass = 'bg-primary';
                                $icon = 'bi-rocket';
                                break;
                            case 'deployment_status':
                                $eventClass = 'event-deployment_status';
                                $badgeClass = 'bg-purple';
                                $icon = 'bi-check2-circle';
                                break;
                            case 'create':
                                $eventClass = 'event-create';
                                $badgeClass = 'bg-info';
                                $icon = 'bi-plus-circle';
                                break;
                            default:
                                $badgeClass = 'bg-secondary';
                                $icon = 'bi-activity';
                        }
                        
                        // Parse the JSON details if available
                        $details = [];
                        if (!empty($event['details'])) {
                            $details = json_decode($event['details'], true);
                        }
                    ?>
                    <div class="col-12 mb-4">
                        <div class="card event-card <?php echo $eventClass; ?>">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi <?php echo $icon; ?>"></i>
                                    <span class="badge <?php echo $badgeClass; ?> event-badge">
                                        <?php echo strtoupper(htmlspecialchars($event['event_type'])); ?>
                                    </span>
                                    <strong class="ms-2"><?php echo htmlspecialchars($event['repository']); ?></strong>
                                    <span class="text-muted ms-2">/ <?php echo htmlspecialchars($event['branch']); ?></span>
                                </div>
                                <small class="text-muted"><?php echo htmlspecialchars($event['created_at']); ?></small>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h5 class="card-title">
                                            <a href="https://github.com/<?php echo htmlspecialchars($event['author']); ?>" target="_blank" class="text-decoration-none">
                                                <?php echo htmlspecialchars($event['author']); ?>
                                            </a>
                                        </h5>
                                        
                                        <?php if ($event['event_type'] === 'push' && isset($details['commits'])): ?>
                                            <h6 class="card-subtitle mb-3 text-muted">
                                                <?php echo count($details['commits']) . ' ' . (count($details['commits']) === 1 ? 'commit' : 'commits'); ?>
                                            </h6>
                                            <ul class="commit-list">
                                                <?php foreach ($details['commits'] as $commit): ?>
                                                    <li>
                                                        <span class="commit-hash"><?php echo substr($commit['id'], 0, 7); ?></span>
                                                        <?php echo htmlspecialchars($commit['message']); ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php elseif ($event['event_type'] === 'pull_request' && isset($details['pull_request'])): ?>
                                            <h6 class="card-subtitle mb-3 text-muted">
                                                Pull Request #<?php echo $details['pull_request']['number']; ?> - 
                                                <?php echo htmlspecialchars($details['pull_request']['title']); ?>
                                            </h6>
                                            <p><?php echo htmlspecialchars($details['pull_request']['body'] ?? 'No description provided.'); ?></p>
                                        <?php elseif ($event['event_type'] === 'deployment' && isset($details['deployment'])): ?>
                                            <h6 class="card-subtitle mb-3 text-muted">
                                                Deployment to <?php echo htmlspecialchars($details['deployment']['environment']); ?>
                                            </h6>
                                            <p><?php echo htmlspecialchars($details['deployment']['description'] ?? 'No description provided.'); ?></p>
                                        <?php else: ?>
                                            <p class="card-text">Event by <?php echo htmlspecialchars($event['author']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex flex-column">
                                            <div class="mb-2">
                                                <strong>Repository:</strong> <?php echo htmlspecialchars($event['repository']); ?>
                                            </div>
                                            <div class="mb-2">
                                                <strong>Branch:</strong> <?php echo htmlspecialchars($event['branch']); ?>
                                            </div>
                                            <div>
                                                <strong>Author:</strong> <?php echo htmlspecialchars($event['author']); ?>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($details)): ?>
                                            <div class="mt-3">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" 
                                                        data-bs-toggle="collapse" 
                                                        data-bs-target="#details-<?php echo $event['id']; ?>" 
                                                        aria-expanded="false">
                                                    Show Raw Details
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($details)): ?>
                                    <div class="collapse mt-3" id="details-<?php echo $event['id']; ?>">
                                        <pre><?php echo htmlspecialchars(json_encode($details, JSON_PRETTY_PRINT)); ?></pre>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No GitHub events found matching your criteria.
            </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="text-muted">Real-time Monitoring System • Version 1.0 • <?php echo date('Y'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize date pickers
            flatpickr('.datepicker', {
                dateFormat: "Y-m-d"
            });
        });
    </script>
</body>
</html>