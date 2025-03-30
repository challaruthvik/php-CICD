<?php 
require_once __DIR__ . '/../vendor/autoload.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migrations | System Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-bg: #f8f9fa;
            --sidebar-bg: #212529;
            --card-bg: #ffffff;
            --accent-color: #0d6efd;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
        }
        
        body {
            background-color: var(--primary-bg);
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
            display: flex;
        }
        
        .sidebar {
            background-color: var(--sidebar-bg);
            width: 250px;
            min-height: 100vh;
            color: white;
            transition: all 0.3s;
            position: fixed;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h3 {
            margin: 0;
            font-size: 1.3rem;
        }
        
        .sidebar-nav {
            padding: 0;
            list-style: none;
            margin-top: 1rem;
        }
        
        .sidebar-nav li {
            width: 100%;
        }
        
        .sidebar-nav a {
            display: block;
            padding: 0.8rem 1.5rem;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            transition: all 0.2s;
            display: flex;
            align-items: center;
        }
        
        .sidebar-nav a:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        
        .sidebar-nav a.active {
            color: white;
            background-color: var(--accent-color);
        }
        
        .sidebar-nav i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            transition: all 0.3s;
            width: calc(100% - 250px);
        }
        
        .content-header {
            background: var(--card-bg);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .content {
            padding: 2rem;
        }
        
        .card {
            border-radius: 0.5rem;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .btn-primary {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
        
        .btn-refresh {
            padding: 0.4rem 1rem;
            border-radius: 50px;
            background-color: var(--accent-color);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .btn-refresh:hover {
            background-color: #0b5ed7;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
        }
        
        .migration-status {
            padding: 0.35rem 0.65rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .migration-status.applied {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .migration-status.pending {
            background-color: #fff3cd;
            color: #664d03;
        }
        
        .console-output {
            background-color: #212529;
            color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.375rem;
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            height: 300px;
            overflow-y: auto;
            margin-bottom: 1rem;
        }
        
        .console-line {
            margin: 0;
            line-height: 1.5;
        }
        
        .console-success {
            color: #50fa7b;
        }
        
        .console-error {
            color: #ff5555;
        }
        
        .console-warning {
            color: #f1fa8c;
        }
        
        .console-info {
            color: #8be9fd;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            .sidebar.active {
                margin-left: 0;
            }
            .main-content {
                width: 100%;
                margin-left: 0;
            }
            .main-content.active {
                margin-left: 250px;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner-border text-primary loading-spinner" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="bi bi-speedometer me-2"></i>SePHP Monitor</h3>
        </div>
        <ul class="sidebar-nav">
            <li><a href="index.html"><i class="bi bi-house-door"></i> Dashboard</a></li>
            <li><a href="deployments-view.php"><i class="bi bi-rocket"></i> Deployments</a></li>
            <li><a href="github-events-view.php"><i class="bi bi-github"></i> GitHub Events</a></li>
            <li><a href="run-migrations-view.php" class="active"><i class="bi bi-database"></i> Migrations</a></li>
            <li><a href="webhook-view.php"><i class="bi bi-webhook"></i> Webhooks</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Database Migrations</h4>
                <button id="refreshButton" class="btn-refresh">
                    <i class="bi bi-arrow-clockwise"></i>
                    Refresh
                </button>
            </div>
        </div>
        
        <div class="content">
            <div class="row">
                <div class="col-md-8">
                    <!-- Database Connection Status -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title">Database Connection</h5>
                                <span id="dbStatus" class="migration-status">Checking...</span>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="mb-0">
                                        <span class="text-muted">Host:</span>
                                        <div id="dbHost" class="fw-medium">-</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-0">
                                        <span class="text-muted">Database:</span>
                                        <div id="dbName" class="fw-medium">-</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-0">
                                        <span class="text-muted">User:</span>
                                        <div id="dbUser" class="fw-medium">-</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-0">
                                        <span class="text-muted">Tables:</span>
                                        <div id="dbTables" class="fw-medium">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Migration List -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Available Migrations</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Migration</th>
                                            <th>Status</th>
                                            <th>Run Date</th>
                                        </tr>
                                    </thead>
                                    <tbody id="migrationsTable">
                                        <tr>
                                            <td colspan="3" class="text-center">Loading migrations...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <!-- Actions Panel -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Migration Actions</h5>
                            <div class="d-grid gap-2">
                                <button id="runAllMigrations" class="btn btn-primary">
                                    <i class="bi bi-play-fill"></i> Run All Migrations
                                </button>
                                <button id="rollbackLastMigration" class="btn btn-outline-danger">
                                    <i class="bi bi-arrow-counterclockwise"></i> Rollback Last Migration
                                </button>
                                <button id="resetMigrations" class="btn btn-outline-warning">
                                    <i class="bi bi-arrow-repeat"></i> Reset & Rerun All
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Console Output Panel -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title">Console Output</h5>
                                <button id="clearConsole" class="btn btn-sm btn-outline-secondary">Clear</button>
                            </div>
                            <div id="consoleOutput" class="console-output"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Migration Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmationMessage">Are you sure you want to proceed?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmAction" class="btn btn-primary">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        class MigrationsUI {
            constructor() {
                this.setupEventListeners();
                this.confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
                this.pendingAction = null;
                this.loadDbStatus();
                this.loadMigrations();
            }

            setupEventListeners() {
                document.getElementById('refreshButton').addEventListener('click', () => {
                    this.loadDbStatus();
                    this.loadMigrations();
                });
                
                document.getElementById('runAllMigrations').addEventListener('click', () => {
                    this.showConfirmation(
                        'Run all pending migrations?',
                        () => this.runMigrations('migrate')
                    );
                });
                
                document.getElementById('rollbackLastMigration').addEventListener('click', () => {
                    this.showConfirmation(
                        'Rollback the last migration? This may result in data loss.',
                        () => this.runMigrations('rollback')
                    );
                });
                
                document.getElementById('resetMigrations').addEventListener('click', () => {
                    this.showConfirmation(
                        'Reset and rerun all migrations? This will drop all tables and recreate them.',
                        () => this.runMigrations('reset')
                    );
                });
                
                document.getElementById('confirmAction').addEventListener('click', () => {
                    this.confirmationModal.hide();
                    if (this.pendingAction) {
                        this.pendingAction();
                        this.pendingAction = null;
                    }
                });
                
                document.getElementById('clearConsole').addEventListener('click', () => {
                    document.getElementById('consoleOutput').innerHTML = '';
                });
            }

            showConfirmation(message, callback) {
                document.getElementById('confirmationMessage').textContent = message;
                this.pendingAction = callback;
                this.confirmationModal.show();
            }

            loadDbStatus() {
                this.showLoading();
                
                fetch('run_migrations.php?action=status')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            this.updateDbStatus(data.data);
                        } else {
                            console.error('Error loading database status:', data.message);
                            this.logToConsole('Error loading database status: ' + data.message, 'error');
                        }
                        this.hideLoading();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.logToConsole('Failed to connect to server: ' + error.message, 'error');
                        this.hideLoading();
                    });
            }

            loadMigrations() {
                this.showLoading();
                
                fetch('run_migrations.php?action=list')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            this.updateMigrationsList(data.data);
                        } else {
                            console.error('Error loading migrations:', data.message);
                            this.logToConsole('Error loading migrations: ' + data.message, 'error');
                        }
                        this.hideLoading();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.logToConsole('Failed to connect to server: ' + error.message, 'error');
                        this.hideLoading();
                    });
            }

            runMigrations(action) {
                this.showLoading();
                this.logToConsole(`Running migration action: ${action}...`, 'info');
                
                fetch(`run_migrations.php?action=${action}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            this.logToConsole(`Migration ${action} completed successfully!`, 'success');
                            if (data.messages && Array.isArray(data.messages)) {
                                data.messages.forEach(msg => {
                                    this.logToConsole(msg, 'info');
                                });
                            }
                            // Reload migrations after successful action
                            this.loadMigrations();
                            this.loadDbStatus();
                        } else {
                            console.error(`Error running ${action}:`, data.message);
                            this.logToConsole(`Error running ${action}: ${data.message}`, 'error');
                            this.hideLoading();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.logToConsole(`Failed to run ${action}: ${error.message}`, 'error');
                        this.hideLoading();
                    });
            }

            updateDbStatus(data) {
                const dbStatus = document.getElementById('dbStatus');
                
                if (data.connected) {
                    dbStatus.textContent = 'Connected';
                    dbStatus.className = 'migration-status applied';
                    
                    document.getElementById('dbHost').textContent = data.host;
                    document.getElementById('dbName').textContent = data.database;
                    document.getElementById('dbUser').textContent = data.user;
                    document.getElementById('dbTables').textContent = data.tables || 0;
                    
                    this.logToConsole('Successfully connected to database', 'success');
                } else {
                    dbStatus.textContent = 'Disconnected';
                    dbStatus.className = 'migration-status pending';
                    
                    document.getElementById('dbHost').textContent = '-';
                    document.getElementById('dbName').textContent = '-';
                    document.getElementById('dbUser').textContent = '-';
                    document.getElementById('dbTables').textContent = '-';
                    
                    this.logToConsole('Failed to connect to database: ' + (data.error || 'Unknown error'), 'error');
                }
            }

            updateMigrationsList(migrations) {
                const tableBody = document.getElementById('migrationsTable');
                tableBody.innerHTML = '';
                
                if (!migrations || migrations.length === 0) {
                    const row = document.createElement('tr');
                    row.innerHTML = '<td colspan="3" class="text-center">No migrations found</td>';
                    tableBody.appendChild(row);
                    return;
                }
                
                migrations.forEach(migration => {
                    const row = document.createElement('tr');
                    
                    const nameCell = document.createElement('td');
                    nameCell.textContent = migration.name;
                    
                    const statusCell = document.createElement('td');
                    const statusSpan = document.createElement('span');
                    statusSpan.className = `migration-status ${migration.applied ? 'applied' : 'pending'}`;
                    statusSpan.textContent = migration.applied ? 'Applied' : 'Pending';
                    statusCell.appendChild(statusSpan);
                    
                    const dateCell = document.createElement('td');
                    dateCell.textContent = migration.applied_at || '-';
                    
                    row.appendChild(nameCell);
                    row.appendChild(statusCell);
                    row.appendChild(dateCell);
                    
                    tableBody.appendChild(row);
                });
            }

            logToConsole(message, type = 'info') {
                const consoleOutput = document.getElementById('consoleOutput');
                const line = document.createElement('p');
                line.className = `console-line console-${type}`;
                
                const timestamp = new Date().toLocaleTimeString();
                line.textContent = `[${timestamp}] ${message}`;
                
                consoleOutput.appendChild(line);
                consoleOutput.scrollTop = consoleOutput.scrollHeight;
            }

            showLoading() {
                document.getElementById('loadingOverlay').style.display = 'flex';
            }

            hideLoading() {
                document.getElementById('loadingOverlay').style.display = 'none';
            }
        }

        // Initialize dashboard when page loads
        document.addEventListener('DOMContentLoaded', () => {
            window.migrationsUI = new MigrationsUI();
        });
    </script>
</body>
</html>