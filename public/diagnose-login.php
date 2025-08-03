<?php
/**
 * Diagnose Login Issues
 */

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Route;

$data = [
    'timestamp' => now()->toDateTimeString(),
    'auth_check' => Auth::guard('portal')->check(),
    'auth_user' => Auth::guard('portal')->user() ? [
        'id' => Auth::guard('portal')->id(),
        'email' => Auth::guard('portal')->user()->email,
    ] : null,
    'session_id' => session()->getId(),
    'session_data' => session()->all(),
    'cookies' => $_COOKIE,
    'routes' => [],
    'login_test' => null,
];

// Get business routes
try {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if (str_starts_with($route->uri(), 'business')) {
            $data['routes'][] = [
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'methods' => $route->methods(),
                'middleware' => $route->middleware(),
            ];
        }
    }
} catch (\Exception $e) {
    $data['routes_error'] = $e->getMessage();
}

// Test login if requested
if (isset($_GET['test_login'])) {
    $user = PortalUser::withoutGlobalScope(\App\Scopes\CompanyScope::class)
        ->where('email', 'demo@askproai.de')
        ->first();
        
    if ($user) {
        Auth::guard('portal')->login($user);
        session()->regenerate();
        
        $data['login_test'] = [
            'user_found' => true,
            'login_executed' => true,
            'auth_check_after' => Auth::guard('portal')->check(),
            'session_id_after' => session()->getId(),
            'redirect_url' => route('business.dashboard', [], true),
        ];
    } else {
        $data['login_test'] = ['user_found' => false];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Diagnostics</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #0f0; padding: 20px; }
        pre { background: #000; padding: 15px; border: 1px solid #0f0; overflow: auto; }
        .section { margin: 20px 0; }
        .title { color: #ff0; font-weight: bold; margin-bottom: 10px; }
        a { color: #0ff; }
        .error { color: #f00; }
        .success { color: #0f0; }
        .button { display: inline-block; padding: 10px 20px; background: #333; border: 1px solid #0f0; color: #0f0; text-decoration: none; margin: 5px; }
        .button:hover { background: #555; }
    </style>
</head>
<body>
    <h1>🔍 Login System Diagnostics</h1>
    
    <div class="section">
        <div class="title">SYSTEM STATUS</div>
        <pre><?php 
            echo "Timestamp: " . $data['timestamp'] . "\n";
            echo "Auth Check: " . ($data['auth_check'] ? '✅ AUTHENTICATED' : '❌ NOT AUTHENTICATED') . "\n";
            if ($data['auth_user']) {
                echo "User: " . $data['auth_user']['email'] . " (ID: " . $data['auth_user']['id'] . ")\n";
            }
            echo "Session ID: " . substr($data['session_id'], 0, 16) . "...\n";
        ?></pre>
    </div>
    
    <div class="section">
        <div class="title">SESSION DATA</div>
        <pre><?php print_r($data['session_data']); ?></pre>
    </div>
    
    <div class="section">
        <div class="title">COOKIES</div>
        <pre><?php 
            foreach ($data['cookies'] as $name => $value) {
                echo "$name: " . substr($value, 0, 32) . "...\n";
            }
        ?></pre>
    </div>
    
    <?php if ($data['login_test']): ?>
    <div class="section">
        <div class="title">LOGIN TEST RESULT</div>
        <pre><?php print_r($data['login_test']); ?></pre>
    </div>
    <?php endif; ?>
    
    <div class="section">
        <div class="title">BUSINESS ROUTES</div>
        <pre><?php 
            if (isset($data['routes_error'])) {
                echo "ERROR: " . $data['routes_error'] . "\n";
            } else {
                foreach ($data['routes'] as $route) {
                    echo sprintf("%-40s %s %s\n", 
                        $route['uri'], 
                        implode('|', $route['methods']),
                        $route['name'] ?? 'no-name'
                    );
                }
            }
        ?></pre>
    </div>
    
    <div class="section">
        <div class="title">ACTIONS</div>
        <a href="?test_login=1" class="button">Test Login as demo@askproai.de</a>
        <a href="/business/login" class="button">Go to Login Page</a>
        <a href="/business/dashboard" class="button">Try Dashboard</a>
        <a href="/fix-login-session.php" class="button">Reset All Sessions</a>
    </div>
    
    <div class="section">
        <div class="title">TROUBLESHOOTING STEPS</div>
        <pre>
1. Click "Reset All Sessions" to clear everything
2. Click "Test Login" to login as demo user
3. If authenticated, click "Try Dashboard"
4. If not working, check Laravel logs:
   tail -f /var/www/api-gateway/storage/logs/laravel.log
        </pre>
    </div>
</body>
</html>