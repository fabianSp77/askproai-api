<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Start session
session_start();

$output = [
    'timestamp' => date('Y-m-d H:i:s'),
    'environment' => app()->environment(),
    'debug_mode' => config('app.debug'),
    'session' => [
        'driver' => config('session.driver'),
        'lifetime' => config('session.lifetime'),
        'domain' => config('session.domain'),
        'secure' => config('session.secure_cookie'),
        'http_only' => config('session.http_only'),
        'same_site' => config('session.same_site'),
        'path' => config('session.path'),
        'current_session_id' => session_id(),
        'session_name' => session_name(),
    ],
    'auth' => [
        'default_guard' => config('auth.defaults.guard'),
        'guards' => array_keys(config('auth.guards')),
        'admin_guard_driver' => config('auth.guards.admin.driver') ?? 'not configured',
        'admin_provider' => config('auth.guards.admin.provider') ?? 'not configured',
    ],
    'filament' => [
        'cache_path_exists' => is_dir(base_path('bootstrap/cache/filament')),
        'cache_files' => [],
        'assets_published' => is_dir(public_path('filament')),
    ],
    'middleware' => [
        'global' => array_values(app(\Illuminate\Contracts\Http\Kernel::class)->getMiddleware()),
        'web_group' => app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareGroups()['web'] ?? [],
    ],
    'routes' => [
        'admin_registered' => app('router')->has('filament.admin.pages.dashboard'),
        'login_registered' => app('router')->has('filament.admin.auth.login'),
    ],
    'database' => [
        'connection' => config('database.default'),
        'can_connect' => false,
    ],
];

// Check Filament cache
if (is_dir(base_path('bootstrap/cache/filament'))) {
    $cacheFiles = glob(base_path('bootstrap/cache/filament') . '/*.php');
    $output['filament']['cache_files'] = array_map('basename', $cacheFiles);
}

// Test database connection
try {
    \DB::connection()->getPdo();
    $output['database']['can_connect'] = true;
    
    // Check if we have admin users
    $adminUserCount = \App\Models\User::count();
    $output['database']['admin_users_count'] = $adminUserCount;
} catch (\Exception $e) {
    $output['database']['error'] = $e->getMessage();
}

// Check current auth state
try {
    $output['current_auth'] = [
        'admin_user' => auth('admin')->check() ? auth('admin')->user()->email : null,
        'portal_user' => auth('portal')->check() ? auth('portal')->user()->email : null,
        'web_user' => auth('web')->check() ? auth('web')->user()->email : null,
    ];
} catch (\Exception $e) {
    $output['current_auth']['error'] = $e->getMessage();
}

// Check for common issues
$output['common_issues'] = [];

if (!is_dir(public_path('filament'))) {
    $output['common_issues'][] = 'Filament assets not published. Run: php artisan filament:assets';
}

if (!is_dir(base_path('bootstrap/cache/filament'))) {
    $output['common_issues'][] = 'Filament components not cached. Run: php artisan filament:cache-components';
}

if (config('session.secure_cookie') && !isset($_SERVER['HTTPS'])) {
    $output['common_issues'][] = 'SESSION_SECURE_COOKIE is true but site is not using HTTPS';
}

if (!file_exists(base_path('.env'))) {
    $output['common_issues'][] = '.env file not found';
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Portal Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        pre { background: white; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .error { color: red; }
        .success { color: green; }
        .warning { color: orange; }
        h2 { margin-top: 30px; }
        .issue { background: #ffe6e6; padding: 10px; margin: 5px 0; border-radius: 3px; }
        .action { background: #e6f3ff; padding: 10px; margin: 10px 0; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔍 Admin Portal Debug Information</h1>
    
    <h2>📊 System Status</h2>
    <pre><?php echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></pre>
    
    <?php if (!empty($output['common_issues'])): ?>
    <h2>⚠️ Common Issues Detected</h2>
    <?php foreach ($output['common_issues'] as $issue): ?>
        <div class="issue"><?php echo htmlspecialchars($issue); ?></div>
    <?php endforeach; ?>
    <?php endif; ?>
    
    <h2>🛠️ Recommended Actions</h2>
    <div class="action">
        <strong>1. Clear all caches:</strong><br>
        <code>php artisan optimize:clear</code>
    </div>
    
    <div class="action">
        <strong>2. Publish Filament assets:</strong><br>
        <code>php artisan filament:assets</code>
    </div>
    
    <div class="action">
        <strong>3. Cache Filament components:</strong><br>
        <code>php artisan filament:cache-components</code>
    </div>
    
    <div class="action">
        <strong>4. Check logs:</strong><br>
        <code>tail -f storage/logs/laravel.log</code>
    </div>
    
    <h2>🔗 Quick Links</h2>
    <ul>
        <li><a href="/admin">Admin Panel</a></li>
        <li><a href="/admin/login">Admin Login</a></li>
        <li><a href="/business">Business Portal</a></li>
        <li><a href="/minimal-dashboard.php?uid=41">Working Minimal Dashboard</a></li>
    </ul>
</body>
</html>