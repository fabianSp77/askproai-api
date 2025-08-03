<?php
/**
 * ULTRATHINK Login Analysis - Comprehensive Login Problem Detection
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
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// Collect all diagnostic data
$diagnostics = [
    'timestamp' => now()->toDateTimeString(),
    'issues' => [],
    'warnings' => [],
    'info' => [],
];

// 1. Check demo user
$demoUser = PortalUser::where('email', 'demo@askproai.de')->first();
if ($demoUser) {
    $diagnostics['demo_user'] = [
        'exists' => true,
        'id' => $demoUser->id,
        'role' => $demoUser->role,
        'is_active' => $demoUser->is_active,
        'requires_2fa' => $demoUser->requires2FA(),
        'has_2fa_secret' => !empty($demoUser->two_factor_secret),
        'two_factor_enforced' => $demoUser->two_factor_enforced,
    ];
    
    if ($demoUser->role === 'admin' || $demoUser->role === 'owner') {
        $diagnostics['issues'][] = "🔴 Demo user has '{$demoUser->role}' role which requires 2FA";
    }
    if ($demoUser->requires2FA()) {
        $diagnostics['issues'][] = "🔴 Demo user requires 2FA but 2FA routes are missing";
    }
} else {
    $diagnostics['issues'][] = "🔴 Demo user (demo@askproai.de) not found!";
}

// 2. Check routes
$routes = Route::getRoutes();
$requiredRoutes = [
    'business.login' => false,
    'business.login.post' => false,
    'business.dashboard' => false,
    'business.two-factor.setup' => false,
    'business.two-factor.challenge' => false,
];

foreach ($routes as $route) {
    $name = $route->getName();
    if (isset($requiredRoutes[$name])) {
        $requiredRoutes[$name] = true;
    }
}

$diagnostics['routes'] = $requiredRoutes;
foreach ($requiredRoutes as $route => $exists) {
    if (!$exists && str_contains($route, 'two-factor')) {
        $diagnostics['issues'][] = "🔴 Missing critical route: $route";
    } elseif (!$exists) {
        $diagnostics['warnings'][] = "⚠️ Missing route: $route";
    }
}

// 3. Check session configuration
$diagnostics['session'] = [
    'driver' => config('session.driver'),
    'domain' => config('session.domain'),
    'cookie' => config('session.cookie'),
    'secure' => config('session.secure'),
    'same_site' => config('session.same_site'),
    'current_id' => session()->getId(),
];

// 4. Check middleware stack
$kernel = app(\Illuminate\Contracts\Http\Kernel::class);
$middlewareGroups = $kernel->getMiddlewareGroups();
$diagnostics['middleware'] = [
    'business-portal' => $middlewareGroups['business-portal'] ?? [],
    'portal.auth' => class_exists(\App\Http\Middleware\PortalAuth::class),
];

// 5. Check authentication status
$diagnostics['auth'] = [
    'web_guard' => Auth::guard('web')->check(),
    'portal_guard' => Auth::guard('portal')->check(),
    'web_user' => Auth::guard('web')->user() ? Auth::guard('web')->user()->email : null,
    'portal_user' => Auth::guard('portal')->user() ? Auth::guard('portal')->user()->email : null,
];

if (Auth::guard('web')->check() && !Auth::guard('portal')->check()) {
    $diagnostics['warnings'][] = "⚠️ Logged into Admin Portal but not Business Portal";
}

// 6. Database connectivity
try {
    DB::connection()->getPdo();
    $diagnostics['database'] = 'Connected';
} catch (\Exception $e) {
    $diagnostics['issues'][] = "🔴 Database connection failed: " . $e->getMessage();
}

// 7. Check logs for recent errors
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lastPos = strrpos($logs, '[' . date('Y-m-d'));
    if ($lastPos !== false) {
        $recentLogs = substr($logs, $lastPos);
        if (str_contains($recentLogs, 'two-factor.setup')) {
            $diagnostics['issues'][] = "🔴 Recent logs show attempts to redirect to missing 2FA routes";
        }
    }
}

// Test login action
if (isset($_GET['test_login'])) {
    try {
        Auth::guard('web')->logout();
        Auth::guard('portal')->logout();
        
        if ($demoUser) {
            Auth::guard('portal')->login($demoUser);
            $diagnostics['test_result'] = [
                'status' => 'success',
                'message' => 'Login successful!',
                'auth_check' => Auth::guard('portal')->check(),
            ];
        }
    } catch (\Exception $e) {
        $diagnostics['test_result'] = [
            'status' => 'error',
            'message' => $e->getMessage(),
        ];
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>ULTRATHINK Login Analysis</title>
    <style>
        body {
            font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
            background: #0a0a0a;
            color: #00ff00;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #00ff00;
            text-shadow: 0 0 10px #00ff00;
            border-bottom: 2px solid #00ff00;
            padding-bottom: 10px;
        }
        .section {
            background: #1a1a1a;
            border: 1px solid #00ff00;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .critical {
            background: #2a0a0a;
            border-color: #ff0000;
            color: #ff4444;
        }
        .warning {
            background: #1a1a0a;
            border-color: #ffff00;
            color: #ffff44;
        }
        .success {
            background: #0a2a0a;
            border-color: #00ff00;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        th {
            color: #ffff00;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #00ff00;
            color: #000;
            text-decoration: none;
            border-radius: 3px;
            margin: 5px;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #00cc00;
            box-shadow: 0 0 10px #00ff00;
        }
        pre {
            background: #0a0a0a;
            padding: 10px;
            overflow: auto;
            border: 1px solid #333;
        }
        .issue-item {
            padding: 5px 0;
        }
        .ascii-art {
            color: #00ff00;
            font-size: 10px;
            line-height: 1;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <pre class="ascii-art">
╔╦╗╦  ╔╦╗╦═╗╔═╗╔╦╗╦ ╦╦╔╗╔╦╔═  ╦  ╔═╗╔═╗╦╔╗╔  ╔═╗╔╗╔╔═╗╦  ╦ ╦╔═╗╦╔═╗
║ ║║   ║ ╠╦╝╠═╣ ║ ╠═╣║║║║╠╩╗  ║  ║ ║║ ╦║║║║  ╠═╣║║║╠═╣║  ╚╦╝╚═╗║╚═╗
╚═╝╩═╝ ╩ ╩╚═╩ ╩ ╩ ╩ ╩╩╝╚╝╩ ╩  ╩═╝╚═╝╚═╝╩╝╚╝  ╩ ╩╝╚╝╩ ╩╩═╝ ╩ ╚═╝╩╚═╝
        </pre>
        
        <?php if (!empty($diagnostics['issues'])): ?>
        <div class="section critical">
            <h2>🚨 CRITICAL ISSUES FOUND</h2>
            <?php foreach ($diagnostics['issues'] as $issue): ?>
                <div class="issue-item"><?php echo $issue; ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($diagnostics['warnings'])): ?>
        <div class="section warning">
            <h2>⚠️ WARNINGS</h2>
            <?php foreach ($diagnostics['warnings'] as $warning): ?>
                <div class="issue-item"><?php echo $warning; ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="grid">
            <div class="section">
                <h3>Demo User Status</h3>
                <table>
                    <?php if (isset($diagnostics['demo_user'])): ?>
                        <?php foreach ($diagnostics['demo_user'] as $key => $value): ?>
                        <tr>
                            <th><?php echo str_replace('_', ' ', $key); ?></th>
                            <td><?php echo is_bool($value) ? ($value ? 'YES' : 'NO') : $value; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">Demo user not found!</td></tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <div class="section">
                <h3>Route Status</h3>
                <table>
                    <?php foreach ($diagnostics['routes'] as $route => $exists): ?>
                    <tr>
                        <th><?php echo $route; ?></th>
                        <td style="color: <?php echo $exists ? '#00ff00' : '#ff4444'; ?>">
                            <?php echo $exists ? '✓ EXISTS' : '✗ MISSING'; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        
        <div class="section">
            <h3>Authentication Status</h3>
            <pre><?php print_r($diagnostics['auth']); ?></pre>
        </div>
        
        <div class="section">
            <h3>Session Configuration</h3>
            <pre><?php print_r($diagnostics['session']); ?></pre>
        </div>
        
        <div class="section">
            <h3>Quick Actions</h3>
            <a href="/fix-demo-user-2fa.php" class="btn">🔧 Fix Demo User 2FA</a>
            <a href="?test_login=1" class="btn">🧪 Test Login</a>
            <a href="/business/login" class="btn">🔑 Go to Login</a>
            <a href="/portal-auth-fix.php" class="btn">🔐 Auth Manager</a>
        </div>
        
        <?php if (isset($diagnostics['test_result'])): ?>
        <div class="section <?php echo $diagnostics['test_result']['status'] === 'success' ? 'success' : 'critical'; ?>">
            <h3>Test Result</h3>
            <pre><?php print_r($diagnostics['test_result']); ?></pre>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <h3>Recommended Solutions</h3>
            <ol>
                <li>Visit <a href="/fix-demo-user-2fa.php" style="color: #00ff00;">Fix Demo User 2FA</a> and click "Both: Change Role + Disable 2FA"</li>
                <li>Clear all browser cookies for askproai.de domain</li>
                <li>Try login again at <a href="/business/login" style="color: #00ff00;">Business Portal Login</a></li>
                <li>If still failing, check Laravel logs: <code>tail -f storage/logs/laravel.log</code></li>
            </ol>
        </div>
    </div>
</body>
</html>