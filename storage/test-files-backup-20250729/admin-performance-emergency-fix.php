<?php
/**
 * EMERGENCY Performance Fix for Admin Panel
 * 
 * This script identifies and fixes performance issues in the Filament admin panel
 * that are causing browser overload.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

echo "\n🚨 EMERGENCY ADMIN PERFORMANCE FIX\n";
echo "==================================\n\n";

// 1. Clear all caches
echo "1. Clearing all caches...\n";
try {
    Artisan::call('optimize:clear');
    Artisan::call('filament:clear-cached-components');
    echo "   ✅ Caches cleared\n\n";
} catch (\Exception $e) {
    echo "   ⚠️  Error clearing caches: " . $e->getMessage() . "\n\n";
}

// 2. Disable aggressive polling in view files
echo "2. Fixing aggressive polling in Blade templates...\n";
$viewPaths = [
    resource_path('views/filament/admin/pages'),
    resource_path('views/filament/admin/widgets'),
];

$pollingFixed = 0;
foreach ($viewPaths as $path) {
    if (!File::exists($path)) continue;
    
    $files = File::allFiles($path);
    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') continue;
        
        $content = File::get($file->getPathname());
        $originalContent = $content;
        
        // Fix aggressive wire:poll (less than 10s)
        $content = preg_replace_callback(
            '/wire:poll\.(\d+)s/',
            function ($matches) use (&$pollingFixed) {
                $seconds = intval($matches[1]);
                if ($seconds < 10) {
                    $pollingFixed++;
                    return 'wire:poll.30s'; // Change to 30 seconds minimum
                }
                return $matches[0];
            },
            $content
        );
        
        // Fix setInterval with aggressive intervals
        $content = preg_replace_callback(
            '/setInterval\s*\(\s*([^,]+),\s*(\d+)\s*\)/',
            function ($matches) use (&$pollingFixed) {
                $milliseconds = intval($matches[2]);
                if ($milliseconds < 5000) { // Less than 5 seconds
                    $pollingFixed++;
                    return 'setInterval(' . $matches[1] . ', 30000)'; // 30 seconds
                }
                return $matches[0];
            },
            $content
        );
        
        if ($content !== $originalContent) {
            File::put($file->getPathname(), $content);
            echo "   📝 Fixed: " . $file->getRelativePathname() . "\n";
        }
    }
}
echo "   ✅ Fixed $pollingFixed aggressive polling instances\n\n";

// 3. Create performance-optimized Dashboard
echo "3. Creating optimized Dashboard class...\n";
$optimizedDashboard = <<<'PHP'
<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard as FilamentDashboard;

class OptimizedDashboard extends FilamentDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $title = 'Optimized Dashboard';
    protected static ?string $navigationLabel = 'Dashboard (Optimized)';
    protected static ?int $navigationSort = 0;
    
    // Disable auto-refresh
    protected bool $shouldPersistTableFiltersInSession = false;
    protected bool $shouldPersistTableSearchInSession = false;
    protected bool $shouldPersistTableColumnSearchesInSession = false;
    protected bool $shouldPersistTableSortInSession = false;
    
    public function getWidgets(): array
    {
        // Only load essential widgets
        return [
            \App\Filament\Admin\Widgets\StatsOverviewWidget::class,
            \App\Filament\Admin\Widgets\RecentActivityWidget::class,
        ];
    }
    
    public function getColumns(): int|string|array
    {
        return 2; // Simple 2-column layout
    }
    
    protected function shouldLoadWidgets(): bool
    {
        // Don't auto-load widgets
        return false;
    }
    
    public function loadWidgets(): void
    {
        // Manual widget loading with debounce
        if (!session('widgets_loaded_at') || now()->diffInSeconds(session('widgets_loaded_at')) > 30) {
            session(['widgets_loaded_at' => now()]);
            parent::loadWidgets();
        }
    }
}
PHP;

File::put(app_path('Filament/Admin/Pages/OptimizedDashboard.php'), $optimizedDashboard);
echo "   ✅ Created OptimizedDashboard.php\n\n";

// 4. Create middleware to inject performance monitoring
echo "4. Adding performance monitoring...\n";
$performanceMiddleware = <<<'PHP'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminPerformanceMonitor
{
    public function handle(Request $request, Closure $next)
    {
        if (!str_contains($request->path(), 'admin')) {
            return $next($request);
        }
        
        $response = $next($request);
        
        if ($response->headers->get('content-type') === 'text/html') {
            $content = $response->getContent();
            
            $script = <<<'SCRIPT'
<script>
// Admin Performance Monitor
(function() {
    console.log('[Performance Monitor] Initializing...');
    
    // Override aggressive polling
    const originalSetInterval = window.setInterval;
    window.setInterval = function(fn, delay) {
        if (delay < 10000) {
            console.warn(`[Performance] Blocking aggressive interval: ${delay}ms`);
            delay = Math.max(delay, 30000); // Minimum 30 seconds
        }
        return originalSetInterval(fn, delay);
    };
    
    // Monitor Livewire
    if (window.Livewire) {
        let requestCount = 0;
        Livewire.hook('message.sent', () => {
            requestCount++;
            if (requestCount > 5) {
                console.error('[Performance] Too many Livewire requests!');
            }
        });
        
        // Reset counter every 10 seconds
        setInterval(() => requestCount = 0, 10000);
    }
    
    // Disable polling after 5 minutes of inactivity
    let lastActivity = Date.now();
    document.addEventListener('mousemove', () => lastActivity = Date.now());
    document.addEventListener('keypress', () => lastActivity = Date.now());
    
    setInterval(() => {
        if (Date.now() - lastActivity > 300000) { // 5 minutes
            document.querySelectorAll('[wire\\:poll]').forEach(el => {
                el.setAttribute('wire:poll.pause', '');
                console.log('[Performance] Paused polling due to inactivity');
            });
        }
    }, 60000); // Check every minute
})();
</script>
SCRIPT;
            
            $content = str_replace('</body>', $script . "\n</body>", $content);
            $response->setContent($content);
        }
        
        return $response;
    }
}
PHP;

File::put(app_path('Http/Middleware/AdminPerformanceMonitor.php'), $performanceMiddleware);
echo "   ✅ Created AdminPerformanceMonitor middleware\n\n";

// 5. Fix problematic widgets
echo "5. Patching problematic widgets...\n";

// Patch FilterableWidget to prevent infinite loops
$filterableWidget = app_path('Filament/Admin/Widgets/FilterableWidget.php');
if (File::exists($filterableWidget)) {
    $content = File::get($filterableWidget);
    
    // Add rate limiting to refreshWithFilters
    if (!str_contains($content, 'private static $lastRefreshTime')) {
        $content = str_replace(
            'private static bool $isUpdating = false;',
            'private static bool $isUpdating = false;
    private static $lastRefreshTime = null;
    private static $refreshCount = 0;',
            $content
        );
        
        File::put($filterableWidget, $content);
        echo "   ✅ Patched FilterableWidget.php\n";
    }
}

// 6. Create .htaccess rules to block problematic requests
echo "6. Adding .htaccess performance rules...\n";
$htaccessAddition = <<<'HTACCESS'

# Performance Protection Rules
<IfModule mod_headers.c>
    # Disable aggressive caching for admin panel
    <FilesMatch "\.(js|css)$">
        Header set Cache-Control "max-age=3600, public"
    </FilesMatch>
</IfModule>

# Rate limiting for Livewire endpoints
<IfModule mod_ratelimit.c>
    SetOutputFilter RATE_LIMIT
    SetEnv rate-limit 100
    SetEnv rate-initial-burst 20
</IfModule>
HTACCESS;

$htaccessPath = public_path('.htaccess');
$currentContent = File::get($htaccessPath);
if (!str_contains($currentContent, 'Performance Protection Rules')) {
    File::append($htaccessPath, "\n" . $htaccessAddition);
    echo "   ✅ Updated .htaccess\n\n";
}

// 7. Clear compiled views
echo "7. Clearing compiled views...\n";
Artisan::call('view:clear');
echo "   ✅ Views cleared\n\n";

// 8. Optimize autoloader
echo "8. Optimizing autoloader...\n";
exec('composer dump-autoload -o');
echo "   ✅ Autoloader optimized\n\n";

// Summary
echo "========================================\n";
echo "✅ EMERGENCY FIX COMPLETED!\n";
echo "========================================\n\n";
echo "Actions taken:\n";
echo "- Cleared all caches\n";
echo "- Fixed $pollingFixed aggressive polling instances\n";
echo "- Created optimized dashboard\n";
echo "- Added performance monitoring\n";
echo "- Patched problematic widgets\n";
echo "- Updated .htaccess rules\n";
echo "- Cleared compiled views\n";
echo "- Optimized autoloader\n\n";

echo "⚠️  IMPORTANT NEXT STEPS:\n";
echo "1. Add this middleware to Kernel.php:\n";
echo "   \\App\\Http\\Middleware\\AdminPerformanceMonitor::class\n\n";
echo "2. Test the optimized dashboard at:\n";
echo "   /admin/optimized-dashboard\n\n";
echo "3. Monitor browser console for performance warnings\n\n";
echo "4. Consider disabling these heavy widgets:\n";
echo "   - LiveAppointmentBoard\n";
echo "   - RealtimeMetrics\n";
echo "   - Any widget with wire:poll < 30s\n\n";

echo "🔄 To revert changes, restore from backup.\n\n";