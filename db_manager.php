<?php
/**
 * Database Management UI
 * Simple web interface for managing MySQL database on port 3308
 */

// Database connection parameters
$db_host = 'localhost';
$db_port = 3308;
$db_user = 'root';
$db_pass = 'admin';

// Start session to store messages
session_start();

// Process form submissions
$message = '';
$error = '';

// Function to get database connection
function getConnection($database = '') {
    global $db_host, $db_port, $db_user, $db_pass;
    return new mysqli($db_host, $db_user, $db_pass, $database, $db_port);
}

// Create database
if (isset($_POST['create_db'])) {
    $db_name = trim($_POST['db_name']);
    
    if (empty($db_name)) {
        $error = "Database name cannot be empty";
    } else {
        try {
            $conn = getConnection();
            
            // Create database
            $query = "CREATE DATABASE IF NOT EXISTS `" . $conn->real_escape_string($db_name) . "`";
            if ($conn->query($query)) {
                $message = "Database '{$db_name}' created successfully!";
            } else {
                $error = "Error creating database: " . $conn->error;
            }
            
            $conn->close();
        } catch (Exception $e) {
            $error = "Connection error: " . $e->getMessage();
        }
    }
}

// Import SQL file
if (isset($_POST['import_sql']) && isset($_FILES['sql_file'])) {
    $db_name = $_POST['import_db'];
    
    if (empty($db_name)) {
        $error = "Please select a database for import";
    } else if ($_FILES['sql_file']['error'] > 0) {
        $error = "Error uploading file: " . $_FILES['sql_file']['error'];
    } else {
        $file_tmp = $_FILES['sql_file']['tmp_name'];
        $file_content = file_get_contents($file_tmp);
        
        if ($file_content === false) {
            $error = "Error reading SQL file";
        } else {
            try {
                $conn = getConnection($db_name);
                
                // Split the SQL file into separate queries
                $queries = explode(';', $file_content);
                $success_count = 0;
                $error_count = 0;
                
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        if ($conn->query($query)) {
                            $success_count++;
                        } else {
                            $error_count++;
                        }
                    }
                }
                
                $message = "Import completed: {$success_count} queries executed successfully, {$error_count} failed.";
                $conn->close();
            } catch (Exception $e) {
                $error = "Connection error: " . $e->getMessage();
            }
        }
    }
}

// Get list of databases
$databases = [];
try {
    $conn = getConnection();
    $result = $conn->query("SHOW DATABASES");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $db_name = $row['Database'];
            // Skip system databases
            if (!in_array($db_name, ['information_schema', 'mysql', 'performance_schema', 'sys'])) {
                $databases[] = $db_name;
            }
        }
    }
    
    $conn->close();
} catch (Exception $e) {
    $error = "Connection error: " . $e->getMessage();
}

// View database tables
$selected_db = isset($_GET['database']) ? $_GET['database'] : '';
$tables = [];

if (!empty($selected_db)) {
    try {
        $conn = getConnection($selected_db);
        $result = $conn->query("SHOW TABLES");
        
        if ($result) {
            while ($row = $result->fetch_array()) {
                $tables[] = $row[0];
            }
        }
        
        $conn->close();
    } catch (Exception $e) {
        $error = "Error listing tables: " . $e->getMessage();
    }
}

// View table data
$selected_table = isset($_GET['table']) ? $_GET['table'] : '';
$table_data = [];
$table_columns = [];

if (!empty($selected_db) && !empty($selected_table)) {
    try {
        $conn = getConnection($selected_db);
        
        // Get columns
        $result = $conn->query("DESCRIBE `{$selected_table}`");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $table_columns[] = $row;
            }
        }
        
        // Get data
        $result = $conn->query("SELECT * FROM `{$selected_table}` LIMIT 100");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $table_data[] = $row;
            }
        }
        
        $conn->close();
    } catch (Exception $e) {
        $error = "Error fetching table data: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SePHP Database Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding-top: 20px; }
        .container { max-width: 1200px; }
        .db-panel { margin-bottom: 30px; }
        .table-container { margin-top: 20px; overflow-x: auto; }
        .nav-tabs { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">SePHP Database Manager</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <ul class="nav nav-tabs" id="dbTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="manage-tab" data-bs-toggle="tab" data-bs-target="#manage" type="button" role="tab">Manage Databases</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="import-tab" data-bs-toggle="tab" data-bs-target="#import" type="button" role="tab">Import SQL</button>
            </li>
            <?php if (!empty($selected_db)): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tables-tab" data-bs-toggle="tab" data-bs-target="#tables" type="button" role="tab">Database: <?= htmlspecialchars($selected_db) ?></button>
                </li>
            <?php endif; ?>
            <?php if (!empty($selected_table)): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="data-tab" data-bs-toggle="tab" data-bs-target="#data" type="button" role="tab">Table: <?= htmlspecialchars($selected_table) ?></button>
                </li>
            <?php endif; ?>
        </ul>
        
        <div class="tab-content" id="dbTabsContent">
            <!-- Manage Databases Tab -->
            <div class="tab-pane fade show active" id="manage" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card db-panel">
                            <div class="card-header">Create Database</div>
                            <div class="card-body">
                                <form method="post" action="">
                                    <div class="mb-3">
                                        <label for="db_name" class="form-label">Database Name</label>
                                        <input type="text" class="form-control" id="db_name" name="db_name" required>
                                    </div>
                                    <button type="submit" name="create_db" class="btn btn-primary">Create Database</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card db-panel">
                            <div class="card-header">Existing Databases</div>
                            <div class="card-body">
                                <?php if (empty($databases)): ?>
                                    <p>No user databases found.</p>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach ($databases as $db): ?>
                                            <a href="?database=<?= urlencode($db) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                <?= htmlspecialchars($db) ?>
                                                <span class="badge bg-primary rounded-pill">View</span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Import SQL Tab -->
            <div class="tab-pane fade" id="import" role="tabpanel">
                <div class="card db-panel">
                    <div class="card-header">Import SQL File</div>
                    <div class="card-body">
                        <form method="post" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="import_db" class="form-label">Select Database</label>
                                <select class="form-select" id="import_db" name="import_db" required>
                                    <option value="">-- Select Database --</option>
                                    <?php foreach ($databases as $db): ?>
                                        <option value="<?= htmlspecialchars($db) ?>"><?= htmlspecialchars($db) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="sql_file" class="form-label">SQL File</label>
                                <input type="file" class="form-control" id="sql_file" name="sql_file" accept=".sql" required>
                            </div>
                            <button type="submit" name="import_sql" class="btn btn-primary">Import SQL</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Tables Tab -->
            <?php if (!empty($selected_db)): ?>
                <div class="tab-pane fade" id="tables" role="tabpanel">
                    <div class="card db-panel">
                        <div class="card-header">
                            Tables in Database: <?= htmlspecialchars($selected_db) ?>
                        </div>
                        <div class="card-body">
                            <?php if (empty($tables)): ?>
                                <p>No tables found in this database.</p>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($tables as $table): ?>
                                        <a href="?database=<?= urlencode($selected_db) ?>&table=<?= urlencode($table) ?>" 
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                            <?= htmlspecialchars($table) ?>
                                            <span class="badge bg-primary rounded-pill">View Data</span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Table Data Tab -->
            <?php if (!empty($selected_db) && !empty($selected_table)): ?>
                <div class="tab-pane fade" id="data" role="tabpanel">
                    <div class="card db-panel">
                        <div class="card-header">
                            Data for Table: <?= htmlspecialchars($selected_table) ?> 
                            <small class="text-muted">(showing up to 100 rows)</small>
                        </div>
                        <div class="card-body">
                            <div class="table-container">
                                <?php if (empty($table_data)): ?>
                                    <p>No data found in this table.</p>
                                <?php else: ?>
                                    <table class="table table-bordered table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <?php foreach (array_keys($table_data[0]) as $column): ?>
                                                    <th><?= htmlspecialchars($column) ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($table_data as $row): ?>
                                                <tr>
                                                    <?php foreach ($row as $value): ?>
                                                        <td><?= htmlspecialchars($value) ?></td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activate the correct tab based on URL parameters
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.has('table')) {
                const dataTab = document.getElementById('data-tab');
                if (dataTab) {
                    const tabTrigger = new bootstrap.Tab(dataTab);
                    tabTrigger.show();
                }
            } else if (urlParams.has('database')) {
                const tablesTab = document.getElementById('tables-tab');
                if (tablesTab) {
                    const tabTrigger = new bootstrap.Tab(tablesTab);
                    tabTrigger.show();
                }
            }
        });
    </script>
</body>
</html>