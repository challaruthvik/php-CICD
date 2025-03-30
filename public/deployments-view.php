<?php 
require_once __DIR__ . '/../vendor/autoload.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deployments | System Dashboard</title>
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

        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background-color: #f8d7da;
            color: #721c24; 
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-info {
            background-color: #d1ecf1;
            color: #0c5460;
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
            <li><a href="deployments-view.php" class="active"><i class="bi bi-rocket"></i> Deployments</a></li>
            <li><a href="github-events-view.php"><i class="bi bi-github"></i> GitHub Events</a></li>
            <li><a href="run-migrations-view.php"><i class="bi bi-database"></i> Migrations</a></li>
            <li><a href="webhook-view.php"><i class="bi bi-webhook"></i> Webhooks</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Deployments</h4>
                <button id="refreshButton" class="btn-refresh">
                    <i class="bi bi-arrow-clockwise"></i>
                    Refresh
                </button>
            </div>
        </div>
        
        <div class="content">
            <!-- New Deployment Button -->
            <div class="mb-4">
                <button id="newDeploymentBtn" class="btn btn-primary">
                    <i class="bi bi-rocket"></i> New Deployment
                </button>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <table id="deploymentsTable" class="table table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Repository</th>
                                <th>Environment</th>
                                <th>Target</th>
                                <th>Commit</th>
                                <th>Status</th>
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

    <!-- New Deployment Modal -->
    <div class="modal fade" id="deploymentModal" tabindex="-1" aria-labelledby="deploymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deploymentModalLabel">New Deployment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="deploymentForm">
                        <div class="mb-3">
                            <label for="repository" class="form-label">Repository</label>
                            <input type="text" class="form-control" id="repository" name="repository" required>
                        </div>
                        <div class="mb-3">
                            <label for="branch" class="form-label">Branch</label>
                            <input type="text" class="form-control" id="branch" name="branch" value="main" required>
                        </div>
                        <div class="mb-3">
                            <label for="environment" class="form-label">Environment</label>
                            <select class="form-select" id="environment" name="environment" required>
                                <option value="production">Production</option>
                                <option value="staging">Staging</option>
                                <option value="development">Development</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="deploymentTarget" class="form-label">Target</label>
                            <select class="form-select" id="deploymentTarget" name="deploymentTarget" required>
                                <option value="hostinger">Hostinger</option>
                                <option value="aws">AWS EC2</option>
                                <option value="local">Local</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitDeployment">Submit</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Deployment Details Modal -->
    <div class="modal fade" id="deploymentDetailsModal" tabindex="-1" aria-labelledby="deploymentDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deploymentDetailsModalLabel">Deployment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="deploymentDetailsBody">
                    <!-- Content will be dynamically inserted -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="refreshDeploymentDetails">Refresh</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            const deploymentsTable = $('#deploymentsTable').DataTable({
                order: [[0, 'desc']], // Sort by ID descending by default
                lengthMenu: [10, 25, 50, 100],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search deployments..."
                }
            });
            
            // Hide loading overlay once page is ready
            $('#loadingOverlay').fadeOut();
            
            // Variables for tracking auto-refresh
            let currentDeploymentId = null;
            let refreshIntervalId = null;
            
            // Function to load deployments
            function loadDeployments() {
                $('#loadingOverlay').fadeIn();
                
                // Fetch deployments from API
                $.ajax({
                    url: 'deployments.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        // Clear existing table data
                        deploymentsTable.clear();
                        
                        // Add new data
                        if (data && data.length > 0) {
                            data.forEach(function(deployment) {
                                const statusBadge = getStatusBadge(deployment.status);
                                const actionsButtons = `
                                    <button class="btn btn-sm btn-info view-details" data-id="${deployment.id}">Details</button>
                                `;
                                
                                deploymentsTable.row.add([
                                    deployment.id,
                                    deployment.repository,
                                    deployment.environment,
                                    deployment.target,
                                    deployment.commit_hash ? deployment.commit_hash.substring(0, 7) : 'N/A',
                                    statusBadge,
                                    formatDate(deployment.created_at),
                                    actionsButtons
                                ]);
                            });
                            
                            deploymentsTable.draw();
                        }
                        
                        $('#loadingOverlay').fadeOut();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching deployments:', error);
                        $('#loadingOverlay').fadeOut();
                        alert('Failed to load deployments. Please try again.');
                    }
                });
            }
            
            // Helper function to format status badge
            function getStatusBadge(status) {
                let badgeClass;
                
                switch(status.toLowerCase()) {
                    case 'success':
                        badgeClass = 'badge-success';
                        break;
                    case 'failed':
                        badgeClass = 'badge-danger';
                        break;
                    case 'pending':
                        badgeClass = 'badge-warning';
                        break;
                    case 'in_progress':
                        badgeClass = 'badge-warning';
                        break;
                    default:
                        badgeClass = 'badge-info';
                }
                
                return `<span class="badge rounded-pill ${badgeClass}">${status}</span>`;
            }
            
            // Helper function to format date
            function formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleString();
            }
            
            // Function to load deployment details
            function loadDeploymentDetails(deploymentId) {
                // Store current deployment ID for auto-refresh
                currentDeploymentId = deploymentId;
                
                $('#loadingOverlay').fadeIn();
                
                // Fetch deployment details
                $.ajax({
                    url: `deployments.php?id=${deploymentId}`,
                    type: 'GET',
                    dataType: 'json',
                    success: function(deployment) {
                        // Format deployment details
                        let detailsHTML = `
                            <div class="deployment-details">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h6>Repository</h6>
                                        <p>${deployment.repository}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Branch</h6>
                                        <p>${deployment.branch || 'main'}</p>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h6>Environment</h6>
                                        <p>${deployment.environment}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Target</h6>
                                        <p>${deployment.target}</p>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h6>Status</h6>
                                        <p>${getStatusBadge(deployment.status)}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Commit Hash</h6>
                                        <p>${deployment.commit_hash || 'N/A'}</p>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h6>Created At</h6>
                                        <p>${formatDate(deployment.created_at)}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Completed At</h6>
                                        <p>${deployment.completed_at ? formatDate(deployment.completed_at) : 'N/A'}</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <h6>Log</h6>
                                        <pre class="bg-light p-3 rounded" id="deploymentLog">${deployment.log || 'No log available'}</pre>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Update modal content
                        $('#deploymentDetailsBody').html(detailsHTML);
                        $('#deploymentDetailsModal').modal('show');
                        $('#loadingOverlay').fadeOut();
                        
                        // Check if this is an active deployment (pending status)
                        if (deployment.status === 'pending') {
                            startAutoRefresh(deploymentId);
                        } else {
                            stopAutoRefresh();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching deployment details:', error);
                        $('#loadingOverlay').fadeOut();
                        alert('Failed to load deployment details. Please try again.');
                    }
                });
            }
            
            // Function to start auto-refresh of deployment details
            function startAutoRefresh(deploymentId) {
                // Stop any existing refresh interval
                stopAutoRefresh();
                
                // Set up new refresh interval (every 3 seconds)
                refreshIntervalId = setInterval(function() {
                    // Only refresh the log part, not the whole modal
                    $.ajax({
                        url: `deployments.php?id=${deploymentId}`,
                        type: 'GET',
                        dataType: 'json',
                        success: function(deployment) {
                            // Update log content
                            $('#deploymentLog').text(deployment.log || 'No log available');
                            
                            // Update status
                            $('.deployment-details h6:contains("Status") + p').html(getStatusBadge(deployment.status));
                            
                            // Update completed time if available
                            if (deployment.completed_at) {
                                $('.deployment-details h6:contains("Completed At") + p').text(formatDate(deployment.completed_at));
                            }
                            
                            // If status is no longer pending, stop auto-refresh
                            if (deployment.status !== 'pending') {
                                stopAutoRefresh();
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error refreshing deployment details:', error);
                            stopAutoRefresh();
                        }
                    });
                }, 3000);
            }
            
            // Function to stop auto-refresh
            function stopAutoRefresh() {
                if (refreshIntervalId) {
                    clearInterval(refreshIntervalId);
                    refreshIntervalId = null;
                }
            }
            
            // Load deployments on page load
            loadDeployments();
            
            // Refresh button click handler
            $('#refreshButton').on('click', function() {
                loadDeployments();
            });
            
            // New deployment button click handler
            $('#newDeploymentBtn').on('click', function() {
                $('#deploymentModal').modal('show');
            });
            
            // Submit deployment handler
            $('#submitDeployment').on('click', function() {
                const formData = {
                    repository: $('#repository').val(),
                    branch: $('#branch').val(),
                    environment: $('#environment').val(),
                    target: $('#deploymentTarget').val()
                };
                
                // Validate form
                const form = document.getElementById('deploymentForm');
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
                
                $('#loadingOverlay').fadeIn();
                
                // Submit deployment request
                $.ajax({
                    url: 'deployments.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        $('#deploymentModal').modal('hide');
                        $('#deploymentForm').trigger('reset');
                        loadDeployments();
                        
                        // Optionally show deployment details automatically
                        if (response && response.data && response.data.id) {
                            setTimeout(() => {
                                loadDeploymentDetails(response.data.id);
                            }, 500);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error creating deployment:', error);
                        $('#loadingOverlay').fadeOut();
                        alert('Failed to create deployment. Please try again.');
                    }
                });
            });
            
            // View deployment details handler
            $('#deploymentsTable').on('click', '.view-details', function() {
                const deploymentId = $(this).data('id');
                loadDeploymentDetails(deploymentId);
            });
            
            // Manual refresh deployment details button
            $('#refreshDeploymentDetails').on('click', function() {
                if (currentDeploymentId) {
                    loadDeploymentDetails(currentDeploymentId);
                }
            });
            
            // When details modal is closed, stop auto-refresh
            $('#deploymentDetailsModal').on('hidden.bs.modal', function() {
                stopAutoRefresh();
                currentDeploymentId = null;
            });
            
            // Reset form when modal is closed
            $('#deploymentModal').on('hidden.bs.modal', function() {
                $('#deploymentForm').trigger('reset');
            });
        });
    </script>
</body>
</html>