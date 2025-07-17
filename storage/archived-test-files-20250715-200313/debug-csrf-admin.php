<?php
// Debug CSRF Issue for Admin Portal

session_start();

// Get current session info
$sessionId = session_id();
$sessionToken = $_SESSION['_token'] ?? 'NOT SET';
$cookieToken = $_COOKIE['XSRF-TOKEN'] ?? 'NOT SET';
$laravelSession = $_COOKIE['askproai_session'] ?? 'NOT SET';

// Check if we have any admin cookies
$adminCookies = array_filter($_COOKIE, function($key) {
    return strpos($key, 'admin') !== false || strpos($key, 'filament') !== false;
}, ARRAY_FILTER_USE_KEY);

?>
<!DOCTYPE html>
<html>
<head>
    <title>CSRF Debug - Admin Portal</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        .section { margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 5px; }
        .good { color: green; }
        .bad { color: red; }
        .info { color: blue; }
        pre { background: #eee; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>CSRF Debug Information - Admin Portal</h1>
    
    <div class="section">
        <h2>Session Information</h2>
        <p>Session ID: <span class="info"><?php echo htmlspecialchars($sessionId); ?></span></p>
        <p>Session Token: <span class="<?php echo $sessionToken !== 'NOT SET' ? 'good' : 'bad'; ?>">
            <?php echo htmlspecialchars(substr($sessionToken, 0, 20)); ?>...
        </span></p>
        <p>Session Cookie: <span class="<?php echo $laravelSession !== 'NOT SET' ? 'good' : 'bad'; ?>">
            <?php echo $laravelSession !== 'NOT SET' ? 'SET' : 'NOT SET'; ?>
        </span></p>
    </div>

    <div class="section">
        <h2>CSRF Tokens</h2>
        <p>XSRF-TOKEN Cookie: <span class="<?php echo $cookieToken !== 'NOT SET' ? 'good' : 'bad'; ?>">
            <?php echo $cookieToken !== 'NOT SET' ? 'SET' : 'NOT SET'; ?>
        </span></p>
    </div>

    <div class="section">
        <h2>Admin/Filament Cookies</h2>
        <?php if (empty($adminCookies)): ?>
            <p class="bad">No admin/filament specific cookies found</p>
        <?php else: ?>
            <pre><?php print_r($adminCookies); ?></pre>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>All Cookies</h2>
        <pre><?php print_r($_COOKIE); ?></pre>
    </div>

    <div class="section">
        <h2>Test CSRF Token Generation</h2>
        <?php
        // Try to load Laravel
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            $app = require_once __DIR__ . '/../bootstrap/app.php';
            $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
            $response = $kernel->handle(
                $request = Illuminate\Http\Request::capture()
            );
            
            $token = csrf_token();
            echo '<p class="good">Laravel CSRF Token: ' . substr($token, 0, 20) . '...</p>';
            
            // Check if admin routes are accessible
            $adminRoutes = app('router')->getRoutes();
            $hasAdminRoutes = false;
            foreach ($adminRoutes as $route) {
                if (strpos($route->uri(), 'admin') !== false) {
                    $hasAdminRoutes = true;
                    break;
                }
            }
            echo '<p>Admin routes registered: <span class="' . ($hasAdminRoutes ? 'good' : 'bad') . '">' . 
                 ($hasAdminRoutes ? 'YES' : 'NO') . '</span></p>';
            
        } catch (Exception $e) {
            echo '<p class="bad">Error loading Laravel: ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>Recommendations</h2>
        <ol>
            <li>Clear browser cookies and cache</li>
            <li>Try incognito/private browsing mode</li>
            <li>Check if session files are writable: <code>ls -la storage/framework/sessions/</code></li>
            <li>Run: <code>php artisan optimize:clear</code></li>
            <li>Check logs: <code>tail -f storage/logs/laravel.log</code></li>
        </ol>
    </div>

    <div class="section">
        <h2>Quick Test Form</h2>
        <form method="POST" action="/admin/login">
            <input type="hidden" name="_token" value="<?php echo $token ?? 'NO_TOKEN'; ?>">
            <p>This form would submit with token: <?php echo isset($token) ? substr($token, 0, 20) . '...' : 'NO TOKEN'; ?></p>
            <button type="submit">Test POST to /admin/login</button>
        </form>
    </div>
</body>
</html>