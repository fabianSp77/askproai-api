<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = \Illuminate\Http\Request::capture()
);

use App\Filament\Admin\Pages\EventAnalyticsDashboard;
use App\Models\User;

// Login as super admin
$user = User::where('email', 'fabian@askproai.de')->first();
if (!$user) {
    $user = User::where('is_admin', true)->first();
}
auth()->login($user);

// Create instance of the dashboard
$dashboard = new EventAnalyticsDashboard();

// Set a company
$dashboard->companyId = 1;
$dashboard->dateFrom = now()->subDays(30)->format('Y-m-d');
$dashboard->dateTo = now()->format('Y-m-d');

// Load analytics
$dashboard->loadAnalytics();

// Check what data was loaded
echo "Company ID: " . $dashboard->companyId . "\n";
echo "Stats loaded: " . (count($dashboard->stats) > 0 ? 'Yes' : 'No') . "\n";
echo "Chart data loaded: " . (count($dashboard->chartData) > 0 ? 'Yes' : 'No') . "\n";

if (count($dashboard->chartData) > 0) {
    echo "\nChart data structure:\n";
    echo "- Labels: " . count($dashboard->chartData['labels'] ?? []) . " days\n";
    echo "- Appointments: " . array_sum($dashboard->chartData['appointments'] ?? []) . " total\n";
    echo "- Revenue: €" . array_sum($dashboard->chartData['revenue'] ?? []) . " total\n";
    echo "- Calls: " . array_sum($dashboard->chartData['calls'] ?? []) . " total\n";
    
    echo "\nSample data (first 5 days):\n";
    for ($i = 0; $i < min(5, count($dashboard->chartData['labels'] ?? [])); $i++) {
        echo $dashboard->chartData['labels'][$i] . ": ";
        echo $dashboard->chartData['appointments'][$i] . " appointments, ";
        echo "€" . $dashboard->chartData['revenue'][$i] . " revenue, ";
        echo $dashboard->chartData['calls'][$i] . " calls\n";
    }
}

echo "\nHeatmap data loaded: " . (count($dashboard->heatmapData) > 0 ? 'Yes' : 'No') . "\n";
echo "Call metrics loaded: " . (count($dashboard->callMetrics) > 0 ? 'Yes' : 'No') . "\n";