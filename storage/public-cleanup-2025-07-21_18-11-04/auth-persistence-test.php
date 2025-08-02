<?php
/**
 * Authentication Persistence Test
 * 
 * This test definitively shows whether authentication persists across requests
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;

$action = $_GET['action'] ?? '';

// Handle login action
if ($action === 'login') {
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    if ($user) {
        // Clear everything first
        Auth::logout();
        session()->flush();
        session()->regenerate();
        
        // Login
        Auth::login($user, true);
        session()->put('test_marker', 'LOGIN_SUCCESSFUL');
        session()->save();
        
        // Force the response to include session cookie
        $kernel->terminate($request, $response);
        
        header('Location: ?action=check');
        exit;
    }
} elseif ($action === 'logout') {
    Auth::logout();
    session()->flush();
    header('Location: ?');
    exit;
}

// Get session details
$sessionId = session()->getId();
$sessionFile = storage_path('framework/sessions/' . $sessionId);
$sessionData = file_exists($sessionFile) ? unserialize(file_get_contents($sessionFile)) : null;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Auth Persistence Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
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
            margin-bottom: 30px;
            text-align: center;
        }
        .status-box {
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
        .buttons {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            margin: 0 10px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: background 0.2s;
        }
        .button:hover {
            background: #0056b3;
        }
        .button-success {
            background: #28a745;
        }
        .button-success:hover {
            background: #218838;
        }
        .button-danger {
            background: #dc3545;
        }
        .button-danger:hover {
            background: #c82333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        .big-status {
            font-size: 48px;
            margin: 20px 0;
        }
        .session-data {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            overflow-x: auto;
        }
        pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Authentication Persistence Test</h1>
        
        <?php if ($action === 'check'): ?>
            <!-- After redirect check -->
            <div class="status-box <?= Auth::check() ? 'success' : 'error' ?>">
                <div class="big-status"><?= Auth::check() ? '✅' : '❌' ?></div>
                <h2><?= Auth::check() ? 'Authentication Persisted!' : 'Authentication Lost!' ?></h2>
                <p>After redirect: Auth::check() = <strong><?= Auth::check() ? 'TRUE' : 'FALSE' ?></strong></p>
                <?php if (Auth::check()): ?>
                    <p>Logged in as: <strong><?= Auth::user()->email ?></strong></p>
                    <p>User ID: <strong><?= Auth::user()->id ?></strong></p>
                <?php endif; ?>
                <p>Test Marker: <strong><?= session('test_marker', 'NOT FOUND') ?></strong></p>
            </div>
        <?php else: ?>
            <!-- Normal status -->
            <div class="status-box <?= Auth::check() ? 'info' : 'warning' ?>">
                <h2>Current Status</h2>
                <p>Auth::check() = <strong><?= Auth::check() ? 'TRUE' : 'FALSE' ?></strong></p>
                <?php if (Auth::check()): ?>
                    <p>Logged in as: <strong><?= Auth::user()->email ?></strong></p>
                <?php else: ?>
                    <p>Not logged in</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="buttons">
            <a href="?action=login" class="button">🔑 Test Login</a>
            <a href="?" class="button">🔄 Refresh</a>
            <?php if (Auth::check()): ?>
                <a href="?action=logout" class="button button-danger">🚪 Logout</a>
                <a href="/admin" class="button button-success">📊 Go to Admin</a>
            <?php endif; ?>
        </div>
        
        <h2>📊 Session Details</h2>
        <table>
            <tr>
                <th>Property</th>
                <th>Value</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>Session ID</td>
                <td><code><?= $sessionId ?></code></td>
                <td>✅</td>
            </tr>
            <tr>
                <td>Session Cookie Name</td>
                <td><code><?= config('session.cookie') ?></code></td>
                <td>✅</td>
            </tr>
            <tr>
                <td>Cookie in Browser</td>
                <td><code><?= isset($_COOKIE[config('session.cookie')]) ? 'Present' : 'Missing' ?></code></td>
                <td><?= isset($_COOKIE[config('session.cookie')]) ? '✅' : '❌' ?></td>
            </tr>
            <tr>
                <td>Session File Exists</td>
                <td><code><?= file_exists($sessionFile) ? 'Yes' : 'No' ?></code></td>
                <td><?= file_exists($sessionFile) ? '✅' : '❌' ?></td>
            </tr>
            <tr>
                <td>Session Driver</td>
                <td><code><?= config('session.driver') ?></code></td>
                <td>✅</td>
            </tr>
            <tr>
                <td>Session Domain</td>
                <td><code><?= config('session.domain') ?: '(empty - good!)' ?></code></td>
                <td><?= empty(config('session.domain')) ? '✅' : '⚠️' ?></td>
            </tr>
            <tr>
                <td>Secure Cookie</td>
                <td><code><?= config('session.secure') ? 'true' : 'false' ?></code></td>
                <td><?= !config('session.secure') ? '✅' : '⚠️' ?></td>
            </tr>
        </table>
        
        <?php if ($sessionData): ?>
        <div class="session-data">
            <h3>📦 Session Data</h3>
            <pre><?php
            $filtered = [];
            foreach ($sessionData as $key => $value) {
                if (strpos($key, 'login_') === 0 || $key === '_token' || $key === 'test_marker') {
                    $filtered[$key] = is_string($value) ? substr($value, 0, 50) . '...' : gettype($value);
                }
            }
            echo json_encode($filtered, JSON_PRETTY_PRINT);
            ?></pre>
        </div>
        <?php endif; ?>
        
        <div class="status-box info" style="margin-top: 30px;">
            <h3>📋 How This Test Works</h3>
            <ol style="text-align: left; max-width: 600px; margin: 0 auto;">
                <li>Click "Test Login" to authenticate as demo user</li>
                <li>You'll be redirected to <code>?action=check</code></li>
                <li>If auth persists, you'll see the green success box</li>
                <li>If auth is lost, you'll see the red error box</li>
                <li>The "Test Marker" confirms session data persistence</li>
            </ol>
        </div>
    </div>
    
    <script>
    // Auto-refresh if we're on the check page
    if (window.location.search === '?action=check') {
        setTimeout(() => {
            console.log('Auth check complete. Status: <?= Auth::check() ? "LOGGED IN" : "NOT LOGGED IN" ?>');
        }, 100);
    }
    </script>
</body>
</html>