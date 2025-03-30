<?php 
require_once __DIR__ . '/../vendor/autoload.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webhooks | System Dashboard</title>
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

        .webhook-log {
            background-color: #f8f9fa;
            border-radius: 0.375rem;
            padding: 1rem;
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            height: 500px;
            overflow-y: auto;
            white-space: pre-wrap;
            font-size: 0.875rem;
            color: #212529;
        }
        
        .hook-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .hook-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .hook-status {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        
        .hook-status.active {
            background-color: #28a745;
        }
        
        .hook-status.inactive {
            background-color: #dc3545;
        }
        
        .hook-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .hook-badge.github {
            background-color: #f6f8fa;
            color: #24292e;
        }
        
        .hook-badge.custom {
            background-color: #e1bee7;
            color: #4a148c;
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
            <li><a href="run-migrations-view.php"><i class="bi bi-database"></i> Migrations</a></li>
            <li><a href="webhook-view.php" class="active"><i class="bi bi-webhook"></i> Webhooks</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Webhooks</h4>
                <button id="refreshButton" class="btn-refresh">
                    <i class="bi bi-arrow-clockwise"></i>
                    Refresh
                </button>
            </div>
        </div>
        
        <div class="content">
            <div class="row">
                <div class="col-md-8">
                    <!-- Webhook Log Viewer -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Webhook Log</h5>
                                <div>
                                    <button id="clearLogBtn" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i> Clear Log
                                    </button>
                                    <button id="downloadLogBtn" class="btn btn-sm btn-outline-secondary ms-2">
                                        <i class="bi bi-download"></i> Download
                                    </button>
                                </div>
                            </div>
                            <div id="webhookLog" class="webhook-log">Loading webhook logs...</div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <!-- Webhook Status -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Active Webhooks</h5>
                            
                            <!-- GitHub Webhook -->
                            <div class="card hook-card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <span id="githubHookStatus" class="hook-status active"></span>
                                            <span class="hook-badge github">
                                                <i class="bi bi-github"></i> GitHub
                                            </span>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="githubHookSwitch" checked>
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mb-2">Endpoint: /webhook.php</small>
                                    <button id="updateGithubHook" class="btn btn-sm btn-outline-primary w-100">
                                        <i class="bi bi-gear-fill"></i> Configure
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Custom Webhook -->
                            <div class="card hook-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <span id="customHookStatus" class="hook-status inactive"></span>
                                            <span class="hook-badge custom">
                                                <i class="bi bi-code-slash"></i> Custom
                                            </span>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="customHookSwitch">
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mb-2">Endpoint: /custom-webhook.php</small>
                                    <button id="updateCustomHook" class="btn btn-sm btn-outline-primary w-100">
                                        <i class="bi bi-gear-fill"></i> Configure
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Webhook Stats -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Statistics</h5>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="border rounded p-3 text-center">
                                        <h3 id="totalRequests" class="mb-1">-</h3>
                                        <small class="text-muted">Total Requests</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-3 text-center">
                                        <h3 id="todayRequests" class="mb-1">-</h3>
                                        <small class="text-muted">Today</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-3 text-center">
                                        <h3 id="successRate" class="mb-1">-%</h3>
                                        <small class="text-muted">Success Rate</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-3 text-center">
                                        <h3 id="avgResponse" class="mb-1">-ms</h3>
                                        <small class="text-muted">Avg. Response</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Configure GitHub Webhook Modal -->
    <div class="modal fade" id="githubWebhookModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Configure GitHub Webhook</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="githubWebhookForm">
                        <div class="mb-3">
                            <label for="githubSecret" class="form-label">Webhook Secret</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="githubSecret" placeholder="Enter webhook secret">
                                <button class="btn btn-outline-secondary" type="button" id="toggleSecret">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">Secret key used to validate webhook requests.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Events to Track</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="trackPush" checked>
                                <label class="form-check-label" for="trackPush">Push</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="trackPR" checked>
                                <label class="form-check-label" for="trackPR">Pull Requests</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="trackIssues">
                                <label class="form-check-label" for="trackIssues">Issues</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="trackReleases" checked>
                                <label class="form-check-label" for="trackReleases">Releases</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="webhookURL" class="form-label">Webhook URL</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="webhookURL" value="https://example.com/webhook.php" readonly>
                                <button class="btn btn-outline-secondary" type="button" id="copyURL">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">Use this URL in GitHub webhook settings.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="saveGithubWebhook" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        class WebhookUI {
            constructor() {
                this.githubWebhookModal = new bootstrap.Modal(document.getElementById('githubWebhookModal'));
                this.setupEventListeners();
                this.loadWebhookLog();
                this.loadWebhookStats();
            }

            setupEventListeners() {
                document.getElementById('refreshButton').addEventListener('click', () => {
                    this.loadWebhookLog();
                    this.loadWebhookStats();
                });
                
                document.getElementById('clearLogBtn').addEventListener('click', () => {
                    if (confirm('Are you sure you want to clear the webhook log?')) {
                        this.clearWebhookLog();
                    }
                });
                
                document.getElementById('downloadLogBtn').addEventListener('click', () => {
                    this.downloadWebhookLog();
                });
                
                document.getElementById('updateGithubHook').addEventListener('click', () => {
                    this.githubWebhookModal.show();
                });
                
                document.getElementById('toggleSecret').addEventListener('click', () => {
                    const secretInput = document.getElementById('githubSecret');
                    const toggleBtn = document.getElementById('toggleSecret');
                    
                    if (secretInput.type === 'password') {
                        secretInput.type = 'text';
                        toggleBtn.innerHTML = '<i class="bi bi-eye-slash"></i>';
                    } else {
                        secretInput.type = 'password';
                        toggleBtn.innerHTML = '<i class="bi bi-eye"></i>';
                    }
                });
                
                document.getElementById('copyURL').addEventListener('click', () => {
                    const urlInput = document.getElementById('webhookURL');
                    urlInput.select();
                    document.execCommand('copy');
                    
                    // Show feedback
                    const copyBtn = document.getElementById('copyURL');
                    copyBtn.innerHTML = '<i class="bi bi-check-lg"></i>';
                    setTimeout(() => {
                        copyBtn.innerHTML = '<i class="bi bi-clipboard"></i>';
                    }, 2000);
                });
                
                document.getElementById('saveGithubWebhook').addEventListener('click', () => {
                    this.saveGithubWebhookSettings();
                });
                
                document.getElementById('githubHookSwitch').addEventListener('change', (e) => {
                    this.updateHookStatus('github', e.target.checked);
                });
                
                document.getElementById('customHookSwitch').addEventListener('change', (e) => {
                    this.updateHookStatus('custom', e.target.checked);
                });
            }

            loadWebhookLog() {
                this.showLoading();
                
                fetch('webhook.log')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Failed to fetch webhook log');
                        }
                        return response.text();
                    })
                    .then(data => {
                        document.getElementById('webhookLog').textContent = data || 'No webhook logs found.';
                        this.hideLoading();
                    })
                    .catch(error => {
                        console.error('Error loading webhook log:', error);
                        document.getElementById('webhookLog').textContent = 'Error loading webhook log.';
                        this.hideLoading();
                    });
            }

            loadWebhookStats() {
                // In a real application, this would fetch data from a server endpoint
                // Here we'll simulate some statistics
                
                // Simulate a small delay for loading effect
                setTimeout(() => {
                    document.getElementById('totalRequests').textContent = '247';
                    document.getElementById('todayRequests').textContent = '18';
                    document.getElementById('successRate').textContent = '98%';
                    document.getElementById('avgResponse').textContent = '312ms';
                }, 500);
            }

            clearWebhookLog() {
                this.showLoading();
                
                // In a real application, this would call an API endpoint
                // For demo purposes, we'll just clear the log display
                setTimeout(() => {
                    document.getElementById('webhookLog').textContent = 'Log cleared.';
                    this.hideLoading();
                }, 500);
            }

            downloadWebhookLog() {
                const logContent = document.getElementById('webhookLog').textContent;
                const blob = new Blob([logContent], { type: 'text/plain' });
                const url = window.URL.createObjectURL(blob);
                
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = 'webhook-log-' + new Date().toISOString().split('T')[0] + '.txt';
                
                document.body.appendChild(a);
                a.click();
                
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            }

            saveGithubWebhookSettings() {
                this.showLoading();
                
                // Collect form data
                const secret = document.getElementById('githubSecret').value;
                const events = {
                    push: document.getElementById('trackPush').checked,
                    pull_request: document.getElementById('trackPR').checked,
                    issues: document.getElementById('trackIssues').checked,
                    release: document.getElementById('trackReleases').checked
                };
                
                // In a real application, this would send data to a server endpoint
                console.log('Saving webhook settings:', { secret, events });
                
                // Simulate saving
                setTimeout(() => {
                    this.githubWebhookModal.hide();
                    alert('GitHub webhook settings saved successfully!');
                    this.hideLoading();
                }, 500);
            }

            updateHookStatus(type, isActive) {
                const statusElement = document.getElementById(`${type}HookStatus`);
                
                if (isActive) {
                    statusElement.className = 'hook-status active';
                } else {
                    statusElement.className = 'hook-status inactive';
                }
                
                // In a real application, this would update server settings
                console.log(`${type} webhook ${isActive ? 'activated' : 'deactivated'}`);
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
            window.webhookUI = new WebhookUI();
        });
    </script>
</body>
</html>