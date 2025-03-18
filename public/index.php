<?php
require_once __DIR__ . '/../vendor/autoload.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2>System Monitor</h2>
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="metric-card">
                                    <h5>CPU Usage</h5>
                                    <div class="metric-value" id="cpuUsage">-</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="metric-card">
                                    <h5>Memory Usage</h5>
                                    <div class="metric-value" id="memoryUsage">-</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="metric-card">
                                    <h5>Disk Usage</h5>
                                    <div class="metric-value" id="diskUsage">-</div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-12">
                                <canvas id="metricsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let ws;
        let metricsChart;
        const maxDataPoints = 20;
        let chartData = {
            labels: [],
            cpu: [],
            memory: [],
            disk: []
        };

        function initWebSocket() {
            ws = new WebSocket('ws://localhost:8080');
            
            ws.onopen = () => {
                console.log('Connected to WebSocket server');
                requestMetrics();
            };
            
            ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                handleWebSocketMessage(data);
            };
            
            ws.onclose = () => {
                console.log('Disconnected from WebSocket server');
                setTimeout(initWebSocket, 5000); // Reconnect after 5 seconds
            };
            
            ws.onerror = (error) => {
                console.error('WebSocket error:', error);
            };
        }

        function handleWebSocketMessage(data) {
            switch(data.type) {
                case 'metrics':
                    updateMetrics(data.metrics);
                    updateChart(data.metrics);
                    break;
                case 'metrics_history':
                    initializeChartData(data.history);
                    break;
            }
        }

        function requestMetrics() {
            if (ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({ type: 'get_metrics' }));
                ws.send(JSON.stringify({ type: 'get_metrics_history' }));
            }
        }

        function updateMetrics(metrics) {
            document.getElementById('cpuUsage').textContent = `${metrics.cpu_usage}%`;
            document.getElementById('memoryUsage').textContent = `${metrics.memory_usage}%`;
            document.getElementById('diskUsage').textContent = `${metrics.disk_usage}%`;
        }

        function initializeChart() {
            const ctx = document.getElementById('metricsChart').getContext('2d');
            metricsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'CPU Usage',
                            data: [],
                            borderColor: 'rgb(255, 99, 132)',
                            tension: 0.1
                        },
                        {
                            label: 'Memory Usage',
                            data: [],
                            borderColor: 'rgb(54, 162, 235)',
                            tension: 0.1
                        },
                        {
                            label: 'Disk Usage',
                            data: [],
                            borderColor: 'rgb(75, 192, 192)',
                            tension: 0.1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }

        function updateChart(metrics) {
            const timestamp = new Date().toLocaleTimeString();
            
            chartData.labels.push(timestamp);
            chartData.cpu.push(metrics.cpu_usage);
            chartData.memory.push(metrics.memory_usage);
            chartData.disk.push(metrics.disk_usage);

            // Limit the number of data points
            if (chartData.labels.length > maxDataPoints) {
                chartData.labels.shift();
                chartData.cpu.shift();
                chartData.memory.shift();
                chartData.disk.shift();
            }

            metricsChart.data.labels = chartData.labels;
            metricsChart.data.datasets[0].data = chartData.cpu;
            metricsChart.data.datasets[1].data = chartData.memory;
            metricsChart.data.datasets[2].data = chartData.disk;
            
            metricsChart.update();
        }

        function initializeChartData(history) {
            chartData = {
                labels: [],
                cpu: [],
                memory: [],
                disk: []
            };

            history.forEach(metrics => {
                const timestamp = new Date(metrics.timestamp).toLocaleTimeString();
                chartData.labels.push(timestamp);
                chartData.cpu.push(metrics.cpu_usage);
                chartData.memory.push(metrics.memory_usage);
                chartData.disk.push(metrics.disk_usage);
            });

            metricsChart.data.labels = chartData.labels;
            metricsChart.data.datasets[0].data = chartData.cpu;
            metricsChart.data.datasets[1].data = chartData.memory;
            metricsChart.data.datasets[2].data = chartData.disk;
            
            metricsChart.update();
        }

        // Initialize the chart and WebSocket connection when the page loads
        document.addEventListener('DOMContentLoaded', () => {
            initializeChart();
            initWebSocket();
            
            // Set up periodic metrics requests
            setInterval(requestMetrics, 5000);
        });
    </script>

    <style>
        .metric-card {
            padding: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            text-align: center;
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
            color: #0d6efd;
        }
        
        .status-card {
            padding: 1.5rem;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
        }
    </style>
</body>
</html>