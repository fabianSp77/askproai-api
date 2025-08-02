<?php
/**
 * Simple Session Fix
 * 
 * This uses the simplest possible approach to fix sessions
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    if ($user) {
        // Simple login
        Auth::login($user, true);
        session()->put('test_marker', 'LOGGED_IN');
        session()->save();
        
        // Redirect
        header('Location: ?action=check');
        exit;
    }
} elseif ($action === 'logout') {
    Auth::logout();
    session()->flush();
    header('Location: ?');
    exit;
}

$sessionId = session()->getId();
$authCheck = Auth::check();
$testMarker = session('test_marker');

// Check session file
$sessionFile = storage_path('framework/sessions/' . $sessionId);
$sessionData = null;
if (file_exists($sessionFile)) {
    $content = file_get_contents($sessionFile);
    $sessionData = @unserialize($content);
}

// Get cookie info
$cookieName = config('session.cookie');
$cookieValue = $_COOKIE[$cookieName] ?? null;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Session Fix</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            max-width: 800px;
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
        .status {
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
        }
        .debug {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Simple Session Fix</h1>
        
        <?php if ($action === 'check'): ?>
            <div class="status <?= $authCheck ? 'success' : 'error' ?>">
                <h2><?= $authCheck ? '✅ Authentication Persisted!' : '❌ Authentication Lost!' ?></h2>
                <p>After redirect:</p>
                <p>Auth::check() = <strong><?= $authCheck ? 'TRUE' : 'FALSE' ?></strong></p>
                <p>Test Marker = <strong><?= $testMarker ?: 'NOT FOUND' ?></strong></p>
                <?php if ($authCheck): ?>
                    <p>User: <?= Auth::user()->email ?></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="status info">
                <h2>Current Status</h2>
                <p>Auth::check() = <strong><?= $authCheck ? 'TRUE' : 'FALSE' ?></strong></p>
                <?php if ($authCheck): ?>
                    <p>Logged in as: <?= Auth::user()->email ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin: 20px 0;">
            <a href="?action=login" class="button">🔑 Test Login</a>
            <a href="?" class="button">🔄 Refresh</a>
            <?php if ($authCheck): ?>
                <a href="?action=logout" class="button" style="background: #dc3545;">🚪 Logout</a>
                <a href="/admin" class="button" style="background: #28a745;">📊 Go to Admin</a>
            <?php endif; ?>
        </div>
        
        <table>
            <tr>
                <th>Check</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Session ID</td>
                <td><code><?= $sessionId ?></code></td>
            </tr>
            <tr>
                <td>Cookie Present</td>
                <td><?= $cookieValue ? 'Yes' : 'No' ?></td>
            </tr>
            <tr>
                <td>Session File Exists</td>
                <td><?= file_exists($sessionFile) ? 'Yes' : 'No' ?></td>
            </tr>
            <tr>
                <td>Auth Status</td>
                <td><?= $authCheck ? 'Authenticated' : 'Not Authenticated' ?></td>
            </tr>
            <tr>
                <td>Test Marker</td>
                <td><?= $testMarker ?: 'Not Set' ?></td>
            </tr>
        </table>
        
        <?php if ($sessionData && is_array($sessionData)): ?>
        <div class="debug">
            <h3>Session Data Keys:</h3>
            <?php
            $keys = array_keys($sessionData);
            $authKeys = array_filter($keys, function($k) { return strpos($k, 'login_') === 0; });
            ?>
            <p>Total keys: <?= count($keys) ?></p>
            <p>Auth keys: <?= implode(', ', $authKeys) ?: 'None' ?></p>
            <p>Has _token: <?= isset($sessionData['_token']) ? 'Yes' : 'No' ?></p>
            <p>Has test_marker: <?= isset($sessionData['test_marker']) ? 'Yes (' . $sessionData['test_marker'] . ')' : 'No' ?></p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>