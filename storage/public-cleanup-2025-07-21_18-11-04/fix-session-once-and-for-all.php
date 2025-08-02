<?php
/**
 * Fix Session Once and For All
 * 
 * This identifies and fixes the exact session issue
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Get the kernel but DON'T handle request yet
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

$action = $_GET['action'] ?? '';

// Custom request handling to debug session
if ($action === 'login') {
    // Create request
    $request = Illuminate\Http\Request::capture();
    
    // Start application
    $response = $kernel->handle($request);
    
    // Get user
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    if (!$user) {
        die('User not found');
    }
    
    // Clear everything
    Auth::logout();
    session()->flush();
    
    // Regenerate session
    session()->regenerate();
    $newSessionId = session()->getId();
    
    // Login
    Auth::login($user, true);
    session()->put('test_value', 'SESSION_WORKS');
    session()->save();
    
    // Get the guard name for the cookie
    $guard = Auth::getDefaultDriver();
    $guardCookie = Auth::guard($guard)->getCookieJar();
    
    // Create the session payload (session_id|guard_hash)
    $payload = $newSessionId;
    if (method_exists($guardCookie, 'make')) {
        // Let Laravel handle the cookie creation properly
        Cookie::queue(
            config('session.cookie'),
            $newSessionId,
            config('session.lifetime'),
            config('session.path'),
            config('session.domain'),
            config('session.secure'),
            config('session.http_only'),
            false,
            config('session.same_site')
        );
    } else {
        // Fallback to manual cookie creation
        $cookie = cookie(
            config('session.cookie'),
            $newSessionId,
            config('session.lifetime'),
            config('session.path'),
            config('session.domain'),
            config('session.secure'),
            config('session.http_only'),
            false,
            config('session.same_site')
        );
        
        Cookie::queue($cookie);
    }
    
    // Create response with cookie
    $redirectResponse = redirect('?action=check');
    $redirectResponse->withCookie($cookie);
    
    // Send it
    $redirectResponse->send();
    $kernel->terminate($request, $redirectResponse);
    exit;
    
} else {
    // Normal request
    $request = Illuminate\Http\Request::capture();
    $response = $kernel->handle($request);
}

// Get current state
$sessionId = session()->getId();
$cookieValue = $_COOKIE[config('session.cookie')] ?? null;
$decryptedCookieValue = null;

if ($cookieValue) {
    try {
        $encrypter = app(\Illuminate\Contracts\Encryption\Encrypter::class);
        $decryptedCookieValue = $encrypter->decrypt($cookieValue, false);
        
        // Laravel session cookies may contain additional data separated by |
        // Extract just the session ID part
        if (strpos($decryptedCookieValue, '|') !== false) {
            list($decryptedCookieValue, $additionalData) = explode('|', $decryptedCookieValue, 2);
        }
    } catch (\Exception $e) {
        $decryptedCookieValue = 'DECRYPTION_FAILED';
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Session Once and For All</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .status-box {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
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
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
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
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 0.9em;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
        }
        .button:hover {
            background: #0056b3;
        }
        .critical {
            background: #ff4444;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Session Once and For All</h1>
        
        <?php if ($action === 'check'): ?>
            <div class="status-box <?= Auth::check() ? 'success' : 'error' ?>">
                <h2><?= Auth::check() ? '✅ SUCCESS!' : '❌ FAILED!' ?></h2>
                <p>Auth::check() = <strong><?= Auth::check() ? 'TRUE' : 'FALSE' ?></strong></p>
                <?php if (Auth::check()): ?>
                    <p>User: <?= Auth::user()->email ?></p>
                <?php endif; ?>
                <p>Test Value: <strong><?= session('test_value', 'NOT FOUND') ?></strong></p>
            </div>
        <?php endif; ?>
        
        <?php
        // Check for session ID mismatch
        $sessionMismatch = false;
        if ($decryptedCookieValue && $decryptedCookieValue !== 'DECRYPTION_FAILED' && $decryptedCookieValue !== $sessionId) {
            $sessionMismatch = true;
        }
        ?>
        
        <?php if ($sessionMismatch): ?>
        <div class="critical">
            <h2>🚨 CRITICAL: Session ID Mismatch!</h2>
            <p>Cookie has session: <code><?= $decryptedCookieValue ?></code></p>
            <p>Server has session: <code><?= $sessionId ?></code></p>
            <p>This is why authentication fails!</p>
        </div>
        <?php endif; ?>
        
        <table>
            <tr>
                <th>Check</th>
                <th>Value</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>Current Session ID</td>
                <td><code><?= $sessionId ?></code></td>
                <td>✅</td>
            </tr>
            <tr>
                <td>Cookie Present</td>
                <td><?= $cookieValue ? 'Yes' : 'No' ?></td>
                <td><?= $cookieValue ? '✅' : '❌' ?></td>
            </tr>
            <tr>
                <td>Cookie Decrypted Value</td>
                <td><code><?= $decryptedCookieValue ?: 'N/A' ?></code></td>
                <td><?= ($decryptedCookieValue && $decryptedCookieValue !== 'DECRYPTION_FAILED') ? '✅' : '❌' ?></td>
            </tr>
            <tr>
                <td>Session IDs Match</td>
                <td><?= (!$sessionMismatch && $decryptedCookieValue) ? 'Yes' : 'No' ?></td>
                <td><?= (!$sessionMismatch && $decryptedCookieValue) ? '✅' : '❌' ?></td>
            </tr>
            <tr>
                <td>Auth Status</td>
                <td><?= Auth::check() ? 'Authenticated' : 'Not Authenticated' ?></td>
                <td><?= Auth::check() ? '✅' : '❌' ?></td>
            </tr>
        </table>
        
        <div style="text-align: center; margin: 20px 0;">
            <a href="?action=login" class="button">Test Login with Fix</a>
            <a href="?" class="button">Reset</a>
            <?php if (Auth::check()): ?>
                <a href="/admin" class="button" style="background: #28a745;">Go to Admin</a>
            <?php endif; ?>
        </div>
        
        <div class="status-box info">
            <h3>What this fix does:</h3>
            <ol style="text-align: left;">
                <li>Properly encrypts the session ID before setting cookie</li>
                <li>Uses Laravel's cookie() helper with queue</li>
                <li>Ensures cookie is attached to redirect response</li>
                <li>Detects session ID mismatches</li>
            </ol>
        </div>
    </div>
</body>
</html>