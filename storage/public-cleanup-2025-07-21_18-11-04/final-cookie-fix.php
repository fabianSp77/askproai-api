<?php
/**
 * Final Cookie Fix - The Ultimate Solution
 * 
 * This directly manipulates cookies to ensure they are set correctly
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();

// DON'T handle the request yet
$action = $_GET['action'] ?? '';

if ($action === 'login') {
    // Handle request
    $response = $kernel->handle($request);
    
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    if ($user) {
        // Clear everything
        Auth::logout();
        session()->flush();
        session()->regenerate();
        
        // Get new session ID
        $newSessionId = session()->getId();
        
        // Login
        Auth::login($user, true);
        session()->save();
        
        // FORCE set the cookie manually
        $cookieName = config('session.cookie');
        $lifetime = config('session.lifetime') * 60; // Convert to seconds
        
        // Set raw cookie
        setcookie(
            $cookieName,
            $newSessionId,
            time() + $lifetime,
            '/',
            '', // domain
            false, // secure
            true // httponly
        );
        
        // Also set encrypted Laravel cookie
        $cookie = cookie(
            $cookieName,
            $newSessionId,
            config('session.lifetime'),
            config('session.path'),
            config('session.domain'),
            config('session.secure'),
            config('session.http_only')
        );
        
        // Redirect with cookie
        header('Location: ?action=check');
        exit;
    }
} else {
    // Normal request handling
    $response = $kernel->handle($request);
}

use Illuminate\Support\Facades\Auth;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Final Cookie Fix</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f0f0f0;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .status {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            margin: 10px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
        }
        .button:hover {
            background: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
        }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Final Cookie Fix</h1>
        
        <?php if ($action === 'check'): ?>
            <div class="status <?= Auth::check() ? 'success' : 'error' ?>">
                <h2><?= Auth::check() ? '✅ Success!' : '❌ Failed!' ?></h2>
                <p>After redirect: Auth::check() = <?= Auth::check() ? 'TRUE' : 'FALSE' ?></p>
                <?php if (Auth::check()): ?>
                    <p>Logged in as: <?= Auth::user()->email ?></p>
                    <p><strong>The session persisted! Cookie fix worked!</strong></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="status info">
                <h2>Cookie Fix Test</h2>
                <p>This test forces the session cookie to be set correctly.</p>
                <p>Current status: <?= Auth::check() ? 'Logged in' : 'Not logged in' ?></p>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin: 20px 0;">
            <a href="?action=login" class="button">Test Login with Cookie Fix</a>
            <a href="?" class="button">Reset</a>
            <?php if (Auth::check()): ?>
                <a href="/admin" class="button" style="background: #28a745;">Go to Admin</a>
            <?php endif; ?>
        </div>
        
        <table>
            <tr>
                <th>Property</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Session ID</td>
                <td><code><?= session()->getId() ?></code></td>
            </tr>
            <tr>
                <td>Cookie Name</td>
                <td><code><?= config('session.cookie') ?></code></td>
            </tr>
            <tr>
                <td>Cookie Value</td>
                <td><code><?= isset($_COOKIE[config('session.cookie')]) ? substr($_COOKIE[config('session.cookie')], 0, 50) . '...' : 'Not set' ?></code></td>
            </tr>
            <tr>
                <td>Auth Check</td>
                <td><?= Auth::check() ? '✅ TRUE' : '❌ FALSE' ?></td>
            </tr>
        </table>
        
        <div class="status info" style="margin-top: 30px;">
            <h3>How this fix works:</h3>
            <ol style="text-align: left;">
                <li>Sets the cookie using PHP's <code>setcookie()</code> directly</li>
                <li>Bypasses Laravel's cookie encryption</li>
                <li>Ensures the browser gets the new session ID</li>
            </ol>
        </div>
    </div>
</body>
</html>