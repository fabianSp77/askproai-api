<?php
// Minimal Filament Test
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Bootstrap the console kernel to ensure all providers are loaded
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Minimal Filament Test ===\n\n";

// Step 1: Check if Filament is installed
echo "1. Checking Filament installation...\n";
if (!class_exists('Filament\Panel')) {
    die("✗ Filament not installed\n");
}
echo "✓ Filament is installed\n\n";

// Step 2: Check AdminPanelProvider
echo "2. Checking AdminPanelProvider...\n";
$providerClass = 'App\Providers\Filament\AdminPanelProvider';
if (!class_exists($providerClass)) {
    die("✗ AdminPanelProvider not found\n");
}
echo "✓ AdminPanelProvider exists\n\n";

// Step 3: Try to get the admin panel
echo "3. Getting admin panel...\n";
try {
    $panel = \Filament\Facades\Filament::getPanel('admin');
    echo "✓ Admin panel retrieved\n";
    echo "  - ID: " . $panel->getId() . "\n";
    echo "  - Path: " . $panel->getPath() . "\n";
    echo "  - Has login: " . ($panel->hasLogin() ? 'Yes' : 'No') . "\n\n";
} catch (Exception $e) {
    die("✗ Error getting panel: " . $e->getMessage() . "\n");
}

// Step 4: Check middleware stack
echo "4. Checking middleware...\n";
try {
    // Create minimal request
    $request = \Illuminate\Http\Request::create('/admin', 'GET');
    
    // Get route
    $routes = app('router')->getRoutes();
    $route = $routes->match($request);
    
    if ($route) {
        echo "✓ Route found: " . $route->uri() . "\n";
        $middleware = $route->gatherMiddleware();
        echo "  Middleware count: " . count($middleware) . "\n";
        
        // Show first few middleware
        foreach (array_slice($middleware, 0, 5) as $m) {
            echo "  - " . (is_string($m) ? $m : get_class($m)) . "\n";
        }
        
        if (count($middleware) > 5) {
            echo "  ... and " . (count($middleware) - 5) . " more\n";
        }
    } else {
        echo "✗ No route found for /admin\n";
    }
} catch (Exception $e) {
    echo "✗ Error checking route: " . $e->getMessage() . "\n";
}

echo "\n5. Testing minimal panel setup...\n";

// Create a test file with minimal Filament setup
$testPanelCode = <<<'PHP'
<?php
namespace App\Test;

use Filament\Panel;
use Filament\PanelProvider;

class MinimalPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('test')
            ->path('test-admin')
            ->login();
    }
}
PHP;

file_put_contents('/tmp/MinimalPanelProvider.php', $testPanelCode);
echo "✓ Created minimal panel provider\n";

// Try creating a minimal page
$testPageCode = <<<'PHP'
<?php
namespace App\Test;

use Filament\Pages\Page;

class TestDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'test.dashboard';
    
    public function mount(): void
    {
        // Minimal mount
    }
}
PHP;

file_put_contents('/tmp/TestDashboard.php', $testPageCode);
echo "✓ Created minimal dashboard page\n";

// Create minimal view
$testViewCode = <<<'BLADE'
<div>
    <h1>Test Dashboard - Minimal Filament Setup</h1>
    <p>If you see this, the minimal setup works!</p>
</div>
BLADE;

$viewPath = resource_path('views/test');
if (!is_dir($viewPath)) {
    mkdir($viewPath, 0755, true);
}
file_put_contents($viewPath . '/dashboard.blade.php', $testViewCode);
echo "✓ Created minimal view\n";

echo "\n6. Debugging the actual error...\n";

// Try to handle a request to admin without full HTTP kernel
try {
    // Get HTTP kernel
    $httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Create request
    $adminRequest = \Illuminate\Http\Request::create('/admin', 'GET');
    
    // Try to handle - this is where it might fail
    echo "Attempting to handle /admin request...\n";
    
    // Temporarily capture output
    ob_start();
    $response = $httpKernel->handle($adminRequest);
    $output = ob_get_clean();
    
    echo "Response status: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() === 500) {
        echo "✗ Got 500 error\n";
        
        // Try to find the actual error by checking logs
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);
            $lines = explode("\n", $logContent);
            $recentLines = array_slice($lines, -20);
            
            echo "\nRecent log entries:\n";
            foreach ($recentLines as $line) {
                if (strpos($line, 'ERROR') !== false || strpos($line, 'Exception') !== false) {
                    echo $line . "\n";
                }
            }
        }
    }
    
} catch (Throwable $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "  Type: " . get_class($e) . "\n";
}

echo "\n=== Test Complete ===\n";
echo "\nSuggestions:\n";
echo "1. Use admin-enhanced.php as a working alternative\n";
echo "2. Check APP_ENV and APP_DEBUG in .env\n";
echo "3. Clear all caches: php artisan optimize:clear\n";
echo "4. Check storage/logs/laravel.log for detailed errors\n";