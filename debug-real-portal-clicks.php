<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DEBUG Real Portal Email Clicks ===\n\n";

// 1. Add detailed logging to API endpoint
$logFile = storage_path('logs/portal-email-debug.log');
file_put_contents($logFile, "\n=== Debug Session Started at " . now() . " ===\n", FILE_APPEND);

echo "1. Debug logging activated to: $logFile\n";
echo "   Run: tail -f $logFile\n\n";

// 2. Check recent API access logs
echo "2. Recent API calls to send-summary endpoint:\n";
$logs = shell_exec("grep 'send-summary' " . storage_path('logs/laravel.log') . " | tail -10");
if ($logs) {
    echo $logs;
} else {
    echo "   No recent calls found\n";
}

// 3. Add temporary debug middleware
$debugMiddleware = <<<'PHP'
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DebugPortalEmail
{
    public function handle(Request $request, Closure $next)
    {
        if (str_contains($request->path(), 'send-summary')) {
            $debug = [
                'timestamp' => now()->toIso8601String(),
                'method' => $request->method(),
                'path' => $request->path(),
                'headers' => $request->headers->all(),
                'body' => $request->all(),
                'session_id' => session()->getId(),
                'auth_user' => auth()->guard('portal')->user() ? auth()->guard('portal')->user()->email : 'not authenticated',
                'csrf_token' => $request->header('X-CSRF-TOKEN'),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];
            
            \Log::channel('single')->info('[PORTAL EMAIL DEBUG]', $debug);
            file_put_contents(storage_path('logs/portal-email-debug.log'), "\n" . json_encode($debug, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
        }
        
        return $next($request);
    }
}
PHP;

file_put_contents(app_path('Http/Middleware/DebugPortalEmail.php'), $debugMiddleware);

echo "3. Debug middleware created\n\n";

// 4. Check if routes are correctly registered
echo "4. Checking route registration:\n";
$routes = app('router')->getRoutes();
$found = false;
foreach ($routes as $route) {
    if (str_contains($route->uri(), 'send-summary')) {
        echo "   ✅ Found: " . $route->methods()[0] . " " . $route->uri() . "\n";
        echo "      Action: " . $route->getActionName() . "\n";
        echo "      Middleware: " . implode(', ', $route->middleware()) . "\n";
        $found = true;
    }
}
if (!$found) {
    echo "   ❌ No send-summary route found!\n";
}

echo "\n=== INSTRUCTIONS ===\n";
echo "1. I've added debug logging. When you click the button, check:\n";
echo "   tail -f storage/logs/portal-email-debug.log\n\n";
echo "2. Open browser console (F12) and go to Network tab\n";
echo "3. Click the send email button in Business Portal\n";
echo "4. Look for the send-summary request:\n";
echo "   - Is it being sent?\n";
echo "   - What's the response status?\n";
echo "   - Any errors in console?\n\n";
echo "5. Share the debug log output with me\n";