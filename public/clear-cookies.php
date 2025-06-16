<?php
// Protect this script - only allow access from admin
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Check if user is authenticated and admin
if (!auth()->check() || auth()->user()->email !== 'admin@askproai.de') {
    die('Unauthorized');
}

// Clear all cookies
foreach ($_COOKIE as $key => $value) {
    setcookie($key, '', time() - 3600, '/');
    setcookie($key, '', time() - 3600, '/', '.askproai.de');
    setcookie($key, '', time() - 3600, '/', 'api.askproai.de');
}

// Clear session
session()->flush();
session()->invalidate();
session()->regenerateToken();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cookies gelöscht</title>
    <style>
        body {
            font-family: system-ui;
            text-align: center;
            padding: 50px;
        }
        .success {
            color: green;
            font-size: 20px;
            margin: 20px 0;
        }
        a {
            color: #3b82f6;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <h1>Cookies wurden gelöscht</h1>
    <p class="success">✓ Alle Cookies und Sessions wurden entfernt</p>
    <p>Sie können sich jetzt neu einloggen:</p>
    <p><a href="/admin/login">Zum Login →</a></p>
    
    <hr style="margin: 40px 0;">
    
    <h3>Debug Info:</h3>
    <pre><?php
    echo "Gelöschte Cookies:\n";
    foreach ($_COOKIE as $key => $value) {
        echo "- $key\n";
    }
    ?></pre>
</body>
</html>