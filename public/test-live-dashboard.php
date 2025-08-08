<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\User;

// Login as admin
$admin = User::where('email', 'fabian@askproai.de')->first();
auth()->login($admin);

// Simulate accessing the dashboard page with company selected
$_GET['companyId'] = 1;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Live Test</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .status { background: #f0f0f0; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .error { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
        .chart-container { background: white; padding: 20px; margin: 20px 0; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>🔍 Live Dashboard Test</h1>
    
    <div class="status">
        <h2>1. Checking what JavaScript sees on the page:</h2>
        <div id="element-check"></div>
    </div>
    
    <div class="status">
        <h2>2. Testing if charts can be created:</h2>
        <div class="chart-container">
            <canvas id="testChart" width="400" height="200"></canvas>
        </div>
        <div id="chart-result"></div>
    </div>
    
    <div class="status">
        <h2>3. Checking for JavaScript errors:</h2>
        <div id="error-log"></div>
    </div>

    <script>
        // Capture console errors
        window.errors = [];
        window.addEventListener('error', function(e) {
            window.errors.push(e.message + ' at ' + e.filename + ':' + e.lineno);
            document.getElementById('error-log').innerHTML += '<p class="error">' + e.message + '</p>';
        });

        document.addEventListener('DOMContentLoaded', function() {
            // 1. Check for elements
            const elementCheck = document.getElementById('element-check');
            const elements = [
                'appointmentsChart',
                'revenueChart', 
                'callDistributionChart',
                'callsTimelineChart',
                'heatmap'
            ];
            
            let html = '<ul>';
            elements.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    html += '<li class="success">✅ Found: #' + id + '</li>';
                } else {
                    html += '<li class="error">❌ Missing: #' + id + '</li>';
                }
            });
            html += '</ul>';
            
            // Check for Chart.js and ApexCharts
            html += '<h3>Libraries:</h3><ul>';
            if (typeof Chart !== 'undefined') {
                html += '<li class="success">✅ Chart.js loaded (v' + Chart.version + ')</li>';
            } else {
                html += '<li class="error">❌ Chart.js NOT loaded</li>';
            }
            
            if (typeof ApexCharts !== 'undefined') {
                html += '<li class="success">✅ ApexCharts loaded</li>';
            } else {
                html += '<li class="error">❌ ApexCharts NOT loaded</li>';
            }
            
            // Check for Livewire
            if (typeof Livewire !== 'undefined') {
                html += '<li class="success">✅ Livewire loaded</li>';
            } else {
                html += '<li class="error">❌ Livewire NOT loaded</li>';
            }
            html += '</ul>';
            
            elementCheck.innerHTML = html;
            
            // 2. Try to create a test chart
            const chartResult = document.getElementById('chart-result');
            try {
                const ctx = document.getElementById('testChart').getContext('2d');
                const testChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Test 1', 'Test 2', 'Test 3'],
                        datasets: [{
                            label: 'Test Data',
                            data: [12, 19, 3],
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
                chartResult.innerHTML = '<p class="success">✅ Test chart created successfully!</p>';
            } catch (e) {
                chartResult.innerHTML = '<p class="error">❌ Failed to create test chart: ' + e.message + '</p>';
            }
            
            // 3. Check for any console errors
            if (window.errors.length === 0) {
                document.getElementById('error-log').innerHTML = '<p class="success">✅ No JavaScript errors detected</p>';
            }
        });
    </script>
    
    <div class="status">
        <h2>4. Checking the actual dashboard page:</h2>
        <iframe src="/admin/event-analytics-dashboard" width="100%" height="600" style="border: 1px solid #ddd;"></iframe>
    </div>
</body>
</html>