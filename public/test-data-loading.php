<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\User;
use App\Filament\Admin\Pages\EventAnalyticsDashboard;

// Login as admin
$admin = User::where('email', 'fabian@askproai.de')->first();
if (!$admin) {
    die("User not found");
}
auth()->login($admin);

// Create page instance
$page = new EventAnalyticsDashboard();

// Simulate mount
$page->dateFrom = date('Y-m-01');
$page->dateTo = date('Y-m-31');
$page->companyId = null;
$page->viewMode = 'combined';

echo "<!DOCTYPE html><html><head><title>Data Loading Test</title></head><body>";
echo "<h1>Testing Data Loading</h1>";

echo "<h2>Before loadAnalytics():</h2>";
echo "<pre>";
echo "companyId: " . var_export($page->companyId, true) . "\n";
echo "stats: " . (empty($page->stats) ? "EMPTY" : "Has data") . "\n";
echo "callMetrics: " . (empty($page->callMetrics) ? "EMPTY" : "Has data") . "\n";
echo "</pre>";

// Load analytics
$page->loadAnalytics();

echo "<h2>After loadAnalytics():</h2>";
echo "<pre>";
echo "companyId: " . var_export($page->companyId, true) . "\n";
echo "stats: " . (empty($page->stats) ? "EMPTY" : "Has " . count($page->stats) . " items") . "\n";
if (!empty($page->stats)) {
    echo "Stats content:\n";
    print_r($page->stats);
}
echo "\ncallMetrics: " . (empty($page->callMetrics) ? "EMPTY" : "Has data") . "\n";
if (!empty($page->callMetrics)) {
    echo "CallMetrics keys: " . implode(", ", array_keys($page->callMetrics)) . "\n";
}
echo "\ncompanyComparison: " . (empty($page->companyComparison) ? "EMPTY" : "Has " . count($page->companyComparison) . " companies") . "\n";
echo "\nleadFunnelData: " . (empty($page->leadFunnelData) ? "EMPTY" : "Has data") . "\n";
echo "</pre>";

// Check what methods are being called
echo "<h2>Testing loadAllCompaniesOverview directly:</h2>";
$reflection = new ReflectionClass($page);
$method = $reflection->getMethod('loadAllCompaniesOverview');
$method->setAccessible(true);
$method->invoke($page);

echo "<pre>";
echo "After loadAllCompaniesOverview():\n";
echo "stats: " . (empty($page->stats) ? "EMPTY" : "Has " . count($page->stats) . " items") . "\n";
if (!empty($page->stats)) {
    echo "Stats content:\n";
    print_r($page->stats);
}
echo "</pre>";

echo "</body></html>";