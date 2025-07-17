<?php
// Direct Dashboard Test

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Test direct access to Operations Dashboard
echo "=== Testing Operations Dashboard ===\n";

// Check if class exists
if (class_exists('App\Filament\Admin\Pages\OperationsDashboard')) {
    echo "✓ OperationsDashboard class exists\n";
    
    // Check methods
    $class = 'App\Filament\Admin\Pages\OperationsDashboard';
    echo "  - Slug: " . $class::getSlug() . "\n";
    echo "  - URL: " . $class::getUrl() . "\n";
    echo "  - Can Access: " . ($class::canAccess() ? 'true' : 'false') . "\n";
} else {
    echo "✗ OperationsDashboard class NOT found\n";
}

// Check Filament registration
echo "\n=== Filament Panel Check ===\n";
try {
    $panel = filament()->getPanel('admin');
    $pages = $panel->getPages();
    
    echo "Registered pages:\n";
    foreach ($pages as $pageClass) {
        echo "  - " . $pageClass . "\n";
        if ($pageClass === 'App\Filament\Admin\Pages\OperationsDashboard') {
            echo "    ✓ OperationsDashboard is registered!\n";
        }
    }
} catch (Exception $e) {
    echo "Error accessing panel: " . $e->getMessage() . "\n";
}

// Test the route directly
echo "\n=== Direct Route Test ===\n";
try {
    // Create a fake authenticated user
    $user = new \App\Models\User();
    $user->id = 1;
    $user->email = 'test@example.com';
    
    // Set up authentication
    auth()->setUser($user);
    
    $request = Illuminate\Http\Request::create('/admin', 'GET');
    $response = $kernel->handle($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() !== 200) {
        echo "Response Headers:\n";
        foreach ($response->headers->all() as $key => $values) {
            echo "  $key: " . implode(', ', $values) . "\n";
        }
        
        if ($response->getStatusCode() === 302) {
            echo "Redirect Location: " . $response->headers->get('Location') . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}