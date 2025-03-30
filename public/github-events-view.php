<?php 
require_once __DIR__ . '/../vendor/autoload.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHub Events | System Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">
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
        
        .event-pill {
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.85rem;
            display: inline-block;
        }
        
        .event-pill.push { background-color: #d1e7dd; color: #0f5132; }
        .event-pill.pull_request { background-color: #cff4fc; color: #055160; }
        .event-pill.issues { background-color: #fff3cd; color: #664d03; }
        .event-pill.release { background-color: #e2e3e5; color: #41464b; }
        .event-pill.workflow_run { background-color: #d3d3ff; color: #3a3a7b; }
        
        .json-viewer {
            background-color: #f8f9fa;
            border-radius: 0.375rem;
            padding: 1rem;
            max-height: 400px;
            overflow-y: auto;
            font-family: monospace;
            white-space: pre-wrap;
            font-size: 0.875rem;
        }

        .file-change {
            padding: 8px 12px;
            margin-bottom: 8px;
            border-radius: 4px;
            background: #f8f9fa;
            border-left: 4px solid #6c757d;
            font-family: monospace;
            font-size: 0.875rem;
        }

        .file-change.added {
            border-left-color: #198754;
            background: #d1e7dd;
        }

        .file-change.modified {
            border-left-color: #0d6efd;
            background: #cfe2ff;
        }

        .file-change.removed {
            border-left-color: #dc3545;
            background: #f8d7da;
        }

        .file-change.renamed {
            border-left-color: #6f42c1;
            background: #e2d9f3;
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
            <li><a href="github-events-view.php" class="active"><i class="bi bi-github"></i> GitHub Events</a></li>
            <li><a href="run-migrations-view.php"><i class="bi bi-database"></i> Migrations</a></li>
            <li><a href="webhook-view.php"><i class="bi bi-webhook"></i> Webhooks</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">GitHub Events</h4>
                <button id="refreshButton" class="btn-refresh">
                    <i class="bi bi-arrow-clockwise"></i>
                    Refresh
                </button>
            </div>
        </div>
        
        <div class="content">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Event Statistics</h5>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3 id="totalEvents" class="mb-0">-</h3>
                                    <small class="text-muted">Total Events</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3 id="pushEvents" class="mb-0">-</h3>
                                    <small class="text-muted">Push Events</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3 id="prEvents" class="mb-0">-</h3>
                                    <small class="text-muted">Pull Requests</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3 id="otherEvents" class="mb-0">-</h3>
                                    <small class="text-muted">Other Events</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <table id="eventsTable" class="table table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Repository</th>
                                <th>Event Type</th>
                                <th>Author</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Event Details Modal -->
    <div class="modal fade" id="eventDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Event Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Event ID:</strong> <span id="modalEventId"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Event Type:</strong> <span id="modalEventType"></span>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <strong>Repository:</strong> <span id="modalRepository"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Author:</strong> <span id="modalAuthor"></span>
                        </div>
                    </div>
                    <h6 class="mb-3">Changed Files:</h6>
                    <div id="fileChanges" class="mb-4">
                        <!-- File changes will be populated by JavaScript -->
                    </div>
                    <h6>Payload Data:</h6>
                    <div id="payloadJson" class="json-viewer"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
    <script>
        class GitHubEventsUI {
            constructor() {
                this.table = null;
                this.eventDetails = {};
                this.eventCounts = {
                    push: 0,
                    pull_request: 0,
                    other: 0
                };
                this.setupEventListeners();
                this.initializeTable();
                this.loadEvents();
                this.eventDetailsModal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
            }

            setupEventListeners() {
                document.getElementById('refreshButton').addEventListener('click', () => {
                    this.loadEvents();
                });
            }

            initializeTable() {
                this.table = $('#eventsTable').DataTable({
                    columns: [
                        { data: 'id' },
                        { data: 'repository' },
                        { 
                            data: 'event_type',
                            render: (data) => {
                                return `<span class="event-pill ${data}">${this.formatEventType(data)}</span>`;
                            }
                        },
                        { data: 'author' },  // Changed from 'actor' to 'author'
                        { 
                            data: 'created_at',
                            render: (data) => {
                                const date = new Date(data);
                                return date.toLocaleString();
                            }
                        },
                        {
                            data: null,
                            render: (data) => {
                                return `
                                    <button class="btn btn-sm btn-outline-primary view-details" data-id="${data.id}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                `;
                            }
                        }
                    ],
                    order: [[4, 'desc']], // Sort by date descending
                    responsive: true
                });

                // Setup detail view event
                $('#eventsTable').on('click', '.view-details', (e) => {
                    const id = $(e.currentTarget).data('id');
                    this.showEventDetails(id);
                });
            }

            loadEvents() {
                this.showLoading();
                
                // Get current filter values
                const params = new URLSearchParams(window.location.search);
                
                fetch('github-events-api.php?' + params.toString())
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Reset counters
                            this.eventCounts = {
                                push: 0,
                                pull_request: 0,
                                other: 0
                            };
                            
                            // Process events
                            data.data.forEach(event => {
                                // Store event details for modal
                                this.eventDetails[event.id] = event;
                                
                                // Count event types
                                if (event.event_type === 'push') {
                                    this.eventCounts.push++;
                                } else if (event.event_type === 'pull_request') {
                                    this.eventCounts.pull_request++;
                                } else {
                                    this.eventCounts.other++;
                                }
                            });
                            
                            // Update UI
                            this.updateEventStats();
                            this.table.clear();
                            this.table.rows.add(data.data).draw();
                        } else {
                            console.error('Error loading GitHub events:', data.message);
                            alert('Failed to load GitHub events. Please try again.');
                        }
                        this.hideLoading();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to load GitHub events. Please try again.');
                        this.hideLoading();
                    });
            }

            updateEventStats() {
                const totalEvents = this.eventCounts.push + this.eventCounts.pull_request + this.eventCounts.other;
                
                document.getElementById('totalEvents').textContent = totalEvents;
                document.getElementById('pushEvents').textContent = this.eventCounts.push;
                document.getElementById('prEvents').textContent = this.eventCounts.pull_request;
                document.getElementById('otherEvents').textContent = this.eventCounts.other;
            }

            showEventDetails(id) {
                const event = this.eventDetails[id];
                if (!event) return;
                
                // Populate modal
                document.getElementById('modalEventId').textContent = event.id;
                document.getElementById('modalEventType').textContent = this.formatEventType(event.event_type);
                document.getElementById('modalRepository').textContent = event.repository;
                document.getElementById('modalAuthor').textContent = event.author;
                
                // Format the JSON details
                try {
                    // The details are already parsed by the API
                    const details = typeof event.details === 'string' ? JSON.parse(event.details) : event.details;
                    
                    // Display file changes
                    const fileChangesDiv = document.getElementById('fileChanges');
                    fileChangesDiv.innerHTML = ''; // Clear existing content
                    
                    if (event.event_type === 'push' && details.commits) {
                        // Get unique files from all commits
                        const fileChanges = new Map();
                        details.commits.forEach(commit => {
                            commit.added?.forEach(file => fileChanges.set(file, 'added'));
                            commit.modified?.forEach(file => {
                                // Only set as modified if not already marked as added
                                if (!fileChanges.has(file)) fileChanges.set(file, 'modified');
                            });
                            commit.removed?.forEach(file => fileChanges.set(file, 'removed'));
                        });

                        if (fileChanges.size > 0) {
                            fileChanges.forEach((status, file) => {
                                const div = document.createElement('div');
                                div.className = `file-change ${status}`;
                                div.textContent = `${status.charAt(0).toUpperCase() + status.slice(1)}: ${file}`;
                                fileChangesDiv.appendChild(div);
                            });
                        } else {
                            fileChangesDiv.innerHTML = '<div class="text-muted">No files changed</div>';
                        }
                    } else if (event.event_type === 'pull_request') {
                        const pr = details.pull_request;
                        if (pr.changed_files > 0) {
                            const div = document.createElement('div');
                            div.className = 'text-muted';
                            div.textContent = `${pr.changed_files} files modified with ${pr.additions} additions and ${pr.deletions} deletions`;
                            fileChangesDiv.appendChild(div);
                        } else {
                            fileChangesDiv.innerHTML = '<div class="text-muted">No files changed</div>';
                        }
                    } else {
                        fileChangesDiv.innerHTML = '<div class="text-muted">No file changes for this event type</div>';
                    }
                    
                    // Display full payload
                    document.getElementById('payloadJson').textContent = JSON.stringify(details, null, 2);
                } catch (e) {
                    console.error('Error formatting details:', e);
                    document.getElementById('fileChanges').innerHTML = '<div class="text-muted">No file changes available</div>';
                    document.getElementById('payloadJson').textContent = 'No payload data available';
                }
                
                // Show the modal
                this.eventDetailsModal.show();
            }

            formatEventType(type) {
                return type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
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
            window.githubEventsUI = new GitHubEventsUI();
        });
    </script>
</body>
</html>