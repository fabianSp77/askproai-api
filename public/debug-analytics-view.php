<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\User;

// Login as admin
$admin = User::where('email', 'dev@askproai.de')->first() ?? User::find(5);
auth()->login($admin);

// Check what the page actually renders
$page = new App\Filament\Admin\Pages\EventAnalyticsDashboard();

// Set test data
$page->dateFrom = date('Y-m-01');
$page->dateTo = date('Y-m-31');
$page->companyId = null; // Important: NULL to see aggregate view
$page->viewMode = 'combined';

// Load analytics
$page->loadAnalytics();

echo "<h1>Analytics Dashboard Debug</h1>";
echo "<h2>Data Check:</h2>";
echo "<pre>";
echo "Company ID: " . var_export($page->companyId, true) . " (should be NULL)\n";
echo "View Mode: " . $page->viewMode . "\n";
echo "Date Range: " . $page->dateFrom . " to " . $page->dateTo . "\n\n";

echo "Stats loaded: " . (empty($page->stats) ? "NO" : "YES") . "\n";
if (!empty($page->stats)) {
    echo "Stats content:\n";
    print_r($page->stats);
}

echo "\nCompany Comparison loaded: " . (empty($page->companyComparison) ? "NO" : "YES") . "\n";
if (!empty($page->companyComparison)) {
    echo "Companies in comparison: " . count($page->companyComparison) . "\n";
}

echo "\nCall Metrics loaded: " . (empty($page->callMetrics) ? "NO" : "YES") . "\n";
if (!empty($page->callMetrics)) {
    echo "Inbound metrics: " . (isset($page->callMetrics['inbound']) ? "YES" : "NO") . "\n";
    echo "Outbound metrics: " . (isset($page->callMetrics['outbound']) ? "YES" : "NO") . "\n";
}

echo "</pre>";

// Check view file
$viewPath = resource_path('views/filament/admin/pages/event-analytics-dashboard.blade.php');
$viewContent = file_get_contents($viewPath);

echo "<h2>View File Check:</h2>";
echo "<pre>";
echo "View file size: " . strlen($viewContent) . " bytes\n";
echo "Contains 'Gesamt-Übersicht': " . (strpos($viewContent, 'Gesamt-Übersicht') !== false ? "YES" : "NO") . "\n";
echo "Contains '@if(!$companyId': " . (strpos($viewContent, '@if(!$companyId') !== false ? "YES" : "NO") . "\n";
echo "Contains 'Eingehende Anrufe': " . (strpos($viewContent, 'Eingehende Anrufe') !== false ? "YES" : "NO") . "\n";
echo "</pre>";

// Show first part of condition
echo "<h2>View Condition Check (first 500 chars after form):</h2>";
$formEnd = strpos($viewContent, '</div>', strpos($viewContent, '{{ $this->form }}'));
if ($formEnd !== false) {
    echo "<pre style='background: #f0f0f0; padding: 10px;'>";
    echo htmlspecialchars(substr($viewContent, $formEnd, 500));
    echo "</pre>";
}

echo "<h2>Direct Link to Dashboard:</h2>";
echo "<a href='/admin/event-analytics-dashboard' target='_blank' style='font-size: 20px;'>Open Analytics Dashboard</a>";