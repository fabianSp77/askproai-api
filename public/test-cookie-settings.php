<?php
// Test cookie settings from browser
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cookie Settings Test</title>
</head>
<body>
    <h1>Cookie Settings Test</h1>
    
    <h2>Current Cookies:</h2>
    <pre><?php 
    foreach ($_COOKIE as $name => $value) {
        echo htmlspecialchars($name) . ': ' . htmlspecialchars(substr($value, 0, 50)) . "...\n";
    }
    ?></pre>
    
    <h2>Server Info:</h2>
    <pre>
Request Scheme: <?php echo $_SERVER['REQUEST_SCHEME'] ?? 'not set'; ?>

HTTPS: <?php echo $_SERVER['HTTPS'] ?? 'not set'; ?>

HTTP_X_FORWARDED_PROTO: <?php echo $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'not set'; ?>

Server Port: <?php echo $_SERVER['SERVER_PORT']; ?>

Is Secure: <?php echo (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'YES' : 'NO'; ?>
    </pre>
    
    <h2>Set Test Cookie:</h2>
    <?php
    // Try to set a test cookie
    setcookie('test_cookie_basic', 'value1', time() + 3600, '/');
    setcookie('test_cookie_secure', 'value2', time() + 3600, '/', '.askproai.de', true, true);
    setcookie('test_cookie_lax', 'value3', [
        'expires' => time() + 3600,
        'path' => '/',
        'domain' => '.askproai.de',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    ?>
    <p>Test cookies set. Refresh the page to see if they appear above.</p>
    
    <h2>Laravel Session Info:</h2>
    <pre>
<?php
require_once __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "Session Driver: " . config('session.driver') . "\n";
echo "Session Cookie: " . config('session.cookie') . "\n";
echo "Session Domain: " . config('session.domain') . "\n";
echo "Session Secure: " . (config('session.secure') ? 'YES' : 'NO') . "\n";
echo "Session Same Site: " . config('session.same_site') . "\n";
echo "\n";
echo "Portal Session Cookie: " . config('session_portal.cookie') . "\n";
echo "Portal Session Domain: " . config('session_portal.domain') . "\n";
echo "Portal Session Secure: " . (config('session_portal.secure') ? 'YES' : 'NO') . "\n";
echo "Portal Session Same Site: " . config('session_portal.same_site') . "\n";

$kernel->terminate($request, $response);
?>
    </pre>
</body>
</html>