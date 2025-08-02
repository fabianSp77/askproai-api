<?php
// Ultimate Debug - Find the real issue

// Start output buffering to catch any errors
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();

// Don't handle the request yet, we want to debug first
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ultimate Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .error { background: #fee; color: #c00; padding: 10px; margin: 10px 0; }
        .success { background: #efe; color: #060; padding: 10px; margin: 10px 0; }
        .code { background: #333; color: #0f0; padding: 15px; font-family: monospace; overflow: auto; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>🔍 Ultimate Session Debug</h1>
    
    <?php
    // Now handle the request
    $response = $kernel->handle($request);
    
    // Check for any output/errors
    $output = ob_get_clean();
    if ($output) {
        echo '<div class="error">Captured output/errors:<br><pre>' . htmlspecialchars($output) . '</pre></div>';
    }
    ?>
    
    <div class="section">
        <h2>1. Session File System Check</h2>
        <div class="code">
<?php
$sessionPath = storage_path('framework/sessions');
echo "Session Path: $sessionPath\n";
echo "Exists: " . (is_dir($sessionPath) ? 'YES' : 'NO') . "\n";
echo "Writable: " . (is_writable($sessionPath) ? 'YES' : 'NO') . "\n";
echo "Owner: " . posix_getpwuid(fileowner($sessionPath))['name'] . "\n";
echo "Permissions: " . substr(sprintf('%o', fileperms($sessionPath)), -4) . "\n";
echo "\nSession Files:\n";
$files = glob($sessionPath . '/*');
echo "Total files: " . count($files) . "\n";
foreach (array_slice($files, 0, 5) as $file) {
    echo basename($file) . " - " . filesize($file) . " bytes - " . date('Y-m-d H:i:s', filemtime($file)) . "\n";
}
?>
        </div>
    </div>
    
    <div class="section">
        <h2>2. Current Session State</h2>
        <div class="code">
<?php
echo "Laravel Session ID: " . session()->getId() . "\n";
echo "Session Started: " . (session()->isStarted() ? 'YES' : 'NO') . "\n";
echo "Session Handler: " . get_class(session()->getHandler()) . "\n";
echo "Session Name: " . session()->getName() . "\n";
echo "\nSession Config:\n";
print_r(config('session'));
echo "\nSession Data:\n";
print_r(session()->all());
?>
        </div>
    </div>
    
    <div class="section">
        <h2>3. Cookie Analysis</h2>
        <div class="code">
<?php
echo "Expected Cookie Name: " . config('session.cookie') . "\n";
echo "Cookie Domain: " . (config('session.domain') ?: '(not set)') . "\n";
echo "Cookie Path: " . config('session.path') . "\n";
echo "Secure: " . (config('session.secure') ? 'YES' : 'NO') . "\n";
echo "HttpOnly: " . (config('session.http_only') ? 'YES' : 'NO') . "\n";
echo "SameSite: " . config('session.same_site') . "\n";

echo "\nActual Cookies:\n";
foreach ($_COOKIE as $name => $value) {
    echo "$name = " . substr($value, 0, 50) . "...\n";
}

echo "\nHTTP Headers:\n";
foreach (getallheaders() as $name => $value) {
    if (stripos($name, 'cookie') !== false || stripos($name, 'session') !== false) {
        echo "$name: $value\n";
    }
}
?>
        </div>
    </div>
    
    <div class="section">
        <h2>4. Test Actions</h2>
        
        <h3>Test 1: Set Session Value</h3>
        <form method="POST">
            <input type="hidden" name="action" value="set_session">
            <button type="submit">Set Test Value in Session</button>
        </form>
        <?php
        if ($_POST['action'] ?? '' === 'set_session') {
            session()->put('test_value', 'Set at ' . date('Y-m-d H:i:s'));
            session()->put('counter', session('counter', 0) + 1);
            session()->save();
            echo '<div class="success">Session value set! Refresh page to check persistence.</div>';
        }
        ?>
        
        <h3>Test 2: Force Login</h3>
        <form method="POST">
            <input type="hidden" name="action" value="force_login">
            <button type="submit">Force Login as demo@askproai.de</button>
        </form>
        <?php
        if ($_POST['action'] ?? '' === 'force_login') {
            $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
            if ($user) {
                // Try multiple approaches
                auth()->login($user);
                auth()->guard('web')->login($user);
                session()->regenerate();
                session()->put('force_login_marker', 'Logged in at ' . date('Y-m-d H:i:s'));
                session()->save();
                
                echo '<div class="success">Login forced! User: ' . $user->email . '</div>';
                echo '<div class="code">Auth check: ' . (auth()->check() ? 'YES' : 'NO') . '</div>';
            }
        }
        ?>
        
        <h3>Test 3: Check File System</h3>
        <form method="POST">
            <input type="hidden" name="action" value="check_file">
            <button type="submit">Check Session File Content</button>
        </form>
        <?php
        if ($_POST['action'] ?? '' === 'check_file') {
            $sessionId = session()->getId();
            $sessionFile = storage_path('framework/sessions/' . $sessionId);
            echo '<div class="code">';
            echo "Session File: $sessionFile\n";
            if (file_exists($sessionFile)) {
                echo "Content:\n";
                echo htmlspecialchars(file_get_contents($sessionFile));
            } else {
                echo "FILE NOT FOUND!";
            }
            echo '</div>';
        }
        ?>
    </div>
    
    <div class="section">
        <h2>5. Middleware Stack</h2>
        <div class="code">
<?php
$middlewareGroups = app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareGroups();
echo "Web Middleware:\n";
foreach ($middlewareGroups['web'] ?? [] as $middleware) {
    echo "  - $middleware\n";
}

echo "\nGlobal Middleware:\n";
$reflection = new ReflectionClass($kernel);
$property = $reflection->getProperty('middleware');
$property->setAccessible(true);
$globalMiddleware = $property->getValue($kernel);
foreach ($globalMiddleware as $middleware) {
    echo "  - $middleware\n";
}
?>
        </div>
    </div>
    
    <div class="section">
        <h2>6. Final Test</h2>
        <p>After forcing login above, try these links:</p>
        <ul>
            <li><a href="/admin" target="_blank">Open Admin Panel</a></li>
            <li><a href="/admin/calls" target="_blank">Open Calls Page</a></li>
            <li><a href="ultimate-debug.php">Reload This Page</a> (to check if session persists)</li>
        </ul>
    </div>
    
    <?php
    // Terminate properly
    $kernel->terminate($request, $response);
    ?>
</body>
</html>