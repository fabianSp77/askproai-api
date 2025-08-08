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

// Mount the page (this should set everything up)
$page->mount();

echo "<!DOCTYPE html><html><head><title>Render Debug Test</title></head><body>";
echo "<h1>Debug Render Test</h1>";

echo "<h2>After mount():</h2>";
echo "<pre>";
echo "User: " . auth()->user()->email . "\n";
echo "Is Super Admin: " . (auth()->user()->hasRole(['Super Admin', 'super_admin']) ? 'YES' : 'NO') . "\n";
echo "companyId: " . var_export($page->companyId, true) . "\n";
echo "viewMode: " . $page->viewMode . "\n";
echo "stats array has: " . count($page->stats) . " items\n";
echo "callMetrics has: " . (isset($page->callMetrics) ? count($page->callMetrics) : 0) . " keys\n";
echo "companyComparison has: " . count($page->companyComparison) . " items\n";
echo "</pre>";

// Check what the view would receive
$viewData = [
    'companyId' => $page->companyId,
    'viewMode' => $page->viewMode,
    'stats' => $page->stats,
    'callMetrics' => $page->callMetrics,
    'companyComparison' => $page->companyComparison,
];

echo "<h2>View Data Check:</h2>";
echo "<pre>";
echo "Variables passed to view:\n";
foreach ($viewData as $key => $value) {
    if (is_array($value)) {
        echo "  $key: " . (empty($value) ? "EMPTY ARRAY" : "Array with " . count($value) . " items") . "\n";
        if ($key === 'stats' && !empty($value)) {
            echo "    Stats keys: " . implode(", ", array_keys($value)) . "\n";
        }
    } else {
        echo "  $key: " . var_export($value, true) . "\n";
    }
}
echo "</pre>";

// Test the actual condition from the blade template
echo "<h2>Blade Condition Test:</h2>";
echo "<pre>";
$condition = !$page->companyId && auth()->user()->hasRole(['Super Admin', 'super_admin']);
echo "Condition (!companyId && isSuperAdmin): " . ($condition ? "TRUE - Should show overview" : "FALSE - Should not show") . "\n";
echo "</pre>";

// Try to render the actual view
echo "<h2>Attempting View Render:</h2>";
try {
    $viewPath = 'filament.admin.pages.event-analytics-dashboard';
    $view = view($viewPath, $viewData);
    $rendered = $view->render();
    
    // Check if the overview section is in the rendered output
    if (strpos($rendered, 'Gesamt-Übersicht aller Unternehmen') !== false) {
        echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb;'>";
        echo "✅ SUCCESS: The 'Gesamt-Übersicht aller Unternehmen' section IS in the rendered output!";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb;'>";
        echo "❌ PROBLEM: The 'Gesamt-Übersicht aller Unternehmen' section is NOT in the rendered output!";
        echo "</div>";
        
        // Check what IS in the output
        echo "<h3>What's actually rendered:</h3>";
        if (strpos($rendered, 'companyId = NULL') !== false) {
            echo "<p>✓ Debug info is present</p>";
        }
        if (strpos($rendered, 'Kein Unternehmen ausgewählt') !== false) {
            echo "<p>✓ 'No company selected' message is shown</p>";
        }
        
        // Show a snippet of the rendered content
        echo "<h3>First 1000 chars of rendered output:</h3>";
        echo "<pre style='background: #f0f0f0; padding: 10px; overflow-x: auto;'>";
        echo htmlspecialchars(substr($rendered, 0, 1000));
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px;'>";
    echo "Error rendering view: " . $e->getMessage();
    echo "</div>";
}

echo "</body></html>";