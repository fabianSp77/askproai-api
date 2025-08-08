<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\User;
use App\Filament\Admin\Pages\EventAnalyticsDashboard;

// Login as super admin
$admin = User::where('email', 'fabian@askproai.de')->first();
if (!$admin) {
    die("Admin user not found");
}

auth()->login($admin);

// Create dashboard instance
$dashboard = new EventAnalyticsDashboard();
$dashboard->companyId = 1; // Select Krückeberg Servicegruppe
$dashboard->dateFrom = date('Y-m-d', strtotime('-30 days'));
$dashboard->dateTo = date('Y-m-d');
$dashboard->viewMode = 'combined';
$dashboard->loadAnalytics();

// Debug output
?>
<!DOCTYPE html>
<html>
<head>
    <title>Analytics Dashboard Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .section { margin: 20px 0; padding: 10px; border: 1px solid #0f0; }
        .label { color: #ff0; }
        .value { color: #0ff; }
        .error { color: #f00; }
        .success { color: #0f0; }
        pre { overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Analytics Dashboard Debug</h1>
    
    <div class="section">
        <h2 class="label">User & Company Context</h2>
        <p>User: <span class="value"><?= $admin->email ?></span></p>
        <p>Is Super Admin: <span class="value"><?= $admin->hasRole(['Super Admin', 'super_admin']) ? 'YES' : 'NO' ?></span></p>
        <p>Company ID: <span class="value"><?= $dashboard->companyId ?? 'NULL' ?></span></p>
        <p>Date Range: <span class="value"><?= $dashboard->dateFrom ?> to <?= $dashboard->dateTo ?></span></p>
    </div>
    
    <div class="section">
        <h2 class="label">Data Check</h2>
        <p>Stats: <span class="<?= !empty($dashboard->stats) ? 'success' : 'error' ?>"><?= count($dashboard->stats) ?> items</span></p>
        <p>Chart Data: <span class="<?= !empty($dashboard->chartData) ? 'success' : 'error' ?>"><?= !empty($dashboard->chartData) ? 'Available' : 'MISSING' ?></span></p>
        <?php if (!empty($dashboard->chartData)): ?>
            <ul>
                <li>Labels: <?= count($dashboard->chartData['labels'] ?? []) ?> days</li>
                <li>Appointments: <?= array_sum($dashboard->chartData['appointments'] ?? []) ?> total</li>
                <li>Revenue: €<?= number_format(array_sum($dashboard->chartData['revenue'] ?? []), 2) ?> total</li>
                <li>Calls: <?= array_sum($dashboard->chartData['calls'] ?? []) ?> total</li>
            </ul>
        <?php endif; ?>
        <p>Heatmap Data: <span class="<?= !empty($dashboard->heatmapData) ? 'success' : 'error' ?>"><?= !empty($dashboard->heatmapData) ? count($dashboard->heatmapData) . ' days' : 'MISSING' ?></span></p>
        <p>Call Metrics: <span class="<?= !empty($dashboard->callMetrics) ? 'success' : 'error' ?>"><?= !empty($dashboard->callMetrics) ? 'Available' : 'MISSING' ?></span></p>
    </div>
    
    <div class="section">
        <h2 class="label">JavaScript Variable Check</h2>
        <p>This is what will be available to JavaScript:</p>
        <pre>
const companyId = <?= json_encode($dashboard->companyId) ?>;
const chartData = <?= json_encode($dashboard->chartData, JSON_PRETTY_PRINT) ?>;
const heatmapData = <?= json_encode(array_slice($dashboard->heatmapData ?? [], 0, 2), JSON_PRETTY_PRINT) ?>; // First 2 days only
const callMetrics = <?= json_encode($dashboard->callMetrics, JSON_PRETTY_PRINT) ?>;
        </pre>
    </div>
    
    <div class="section">
        <h2 class="label">Chart Container Elements Check (what JavaScript looks for)</h2>
        <ul>
            <li>document.getElementById('<span class="value">appointmentsChart</span>') - Bar chart for appointments</li>
            <li>document.getElementById('<span class="value">revenueChart</span>') - Line chart for revenue</li>
            <li>document.getElementById('<span class="value">callDistributionChart</span>') - Doughnut chart for calls</li>
            <li>document.getElementById('<span class="value">callsTimelineChart</span>') - Line chart for call timeline</li>
            <li>document.getElementById('<span class="value">heatmap</span>') - ApexCharts heatmap</li>
        </ul>
    </div>
    
    <div class="section">
        <h2 class="label">Possible Issues</h2>
        <?php
        $issues = [];
        
        if (empty($dashboard->chartData)) {
            $issues[] = "❌ Chart data is empty - charts won't render";
        } else {
            if (empty($dashboard->chartData['labels'])) $issues[] = "❌ No labels in chart data";
            if (empty($dashboard->chartData['appointments'])) $issues[] = "❌ No appointments data";
            if (empty($dashboard->chartData['revenue'])) $issues[] = "❌ No revenue data";
            if (empty($dashboard->chartData['calls'])) $issues[] = "❌ No calls data";
        }
        
        if (empty($dashboard->heatmapData)) {
            $issues[] = "❌ Heatmap data is empty";
        }
        
        if (empty($dashboard->callMetrics)) {
            $issues[] = "❌ Call metrics are empty";
        } elseif (empty($dashboard->callMetrics['inbound']) && empty($dashboard->callMetrics['outbound'])) {
            $issues[] = "❌ No inbound or outbound call data";
        }
        
        if (empty($issues)) {
            echo '<p class="success">✅ All data looks good!</p>';
        } else {
            foreach ($issues as $issue) {
                echo '<p class="error">' . $issue . '</p>';
            }
        }
        ?>
    </div>
    
    <div class="section">
        <h2 class="label">Test JavaScript Execution</h2>
        <button onclick="testChartLibraries()" style="padding: 10px; background: #0f0; color: #000; border: none; cursor: pointer;">Test Chart Libraries</button>
        <div id="test-result" style="margin-top: 10px;"></div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
    <script>
        function testChartLibraries() {
            const result = document.getElementById('test-result');
            let html = '';
            
            // Test Chart.js
            if (typeof Chart !== 'undefined') {
                html += '<p class="success">✅ Chart.js loaded (version ' + Chart.version + ')</p>';
            } else {
                html += '<p class="error">❌ Chart.js NOT loaded</p>';
            }
            
            // Test ApexCharts
            if (typeof ApexCharts !== 'undefined') {
                html += '<p class="success">✅ ApexCharts loaded</p>';
            } else {
                html += '<p class="error">❌ ApexCharts NOT loaded</p>';
            }
            
            // Test data availability
            const chartData = <?= json_encode($dashboard->chartData) ?>;
            if (chartData && chartData.labels) {
                html += '<p class="success">✅ Chart data available with ' + chartData.labels.length + ' data points</p>';
            } else {
                html += '<p class="error">❌ Chart data NOT available</p>';
            }
            
            result.innerHTML = html;
        }
    </script>
</body>
</html>