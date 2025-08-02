<?php
/**
 * Ultimate Authentication Test
 * 
 * This test combines all our session fixes and provides detailed diagnostics
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Capture output buffering issues
ob_start();

$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;

$action = $_GET['action'] ?? '';
$forceMethod = $_GET['force'] ?? '';

// Handle actions
if ($action === 'login') {
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    if ($user) {
        // Clear everything
        Auth::logout();
        session()->flush();
        
        // Different login methods based on force parameter
        if ($forceMethod === 'direct') {
            // Method 1: Direct cookie manipulation
            session()->regenerate();
            Auth::login($user, true);
            session()->put('login_method', 'direct_cookie');
            session()->save();
            
            // Force set cookie
            setcookie(
                config('session.cookie'),
                session()->getId(),
                time() + (config('session.lifetime') * 60),
                '/',
                '',
                false,
                true
            );
        } elseif ($forceMethod === 'queue') {
            // Method 2: Laravel cookie queue
            session()->regenerate();
            Auth::login($user, true);
            session()->put('login_method', 'cookie_queue');
            session()->save();
            
            \Cookie::queue(
                config('session.cookie'),
                session()->getId(),
                config('session.lifetime')
            );
        } else {
            // Method 3: Standard Laravel
            session()->regenerate();
            Auth::login($user, true);
            session()->put('login_method', 'standard');
            session()->save();
        }
        
        // Terminate properly
        ob_end_clean();
        $kernel->terminate($request, $response);
        
        header('Location: ?action=verify&method=' . ($forceMethod ?: 'standard'));
        exit;
    }
} elseif ($action === 'logout') {
    Auth::logout();
    session()->flush();
    session()->regenerate();
    header('Location: ?');
    exit;
}

// Get comprehensive session info
$sessionId = session()->getId();
$cookieValue = $_COOKIE[config('session.cookie')] ?? null;
$sessionFile = storage_path('framework/sessions/' . $sessionId);
$sessionExists = file_exists($sessionFile);

// Check session file data
$sessionData = null;
$authKeys = [];
if ($sessionExists) {
    $content = file_get_contents($sessionFile);
    $sessionData = @unserialize($content);
    if ($sessionData && is_array($sessionData)) {
        foreach ($sessionData as $key => $value) {
            if (strpos($key, 'login_') === 0) {
                $authKeys[] = $key;
            }
        }
    }
}

// Check response headers
$headers = headers_list();
$setCookieHeaders = array_filter($headers, function($h) {
    return stripos($h, 'set-cookie') !== false;
});
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ultimate Auth Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f0f2f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #1a1a1a;
            text-align: center;
            margin-bottom: 40px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        .status-error {
            background: #f8d7da;
            color: #721c24;
        }
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        .status-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            border: none;
            cursor: pointer;
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
        .button-warning {
            background: #ffc107;
            color: #333;
        }
        .button-warning:hover {
            background: #e0a800;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            font-size: 14px;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .pre-box {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 13px;
        }
        .big-icon {
            font-size: 48px;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔒 Ultimate Authentication Test</h1>
        
        <?php if ($action === 'verify'): ?>
            <div class="alert <?= Auth::check() ? 'alert-success' : 'alert-error' ?>">
                <div class="big-icon"><?= Auth::check() ? '✅' : '❌' ?></div>
                <h2 style="text-align: center;">
                    <?= Auth::check() ? 'Authentication Persisted Successfully!' : 'Authentication Lost After Redirect!' ?>
                </h2>
                <p style="text-align: center;">
                    Method used: <strong><?= $_GET['method'] ?? 'unknown' ?></strong><br>
                    Login method from session: <strong><?= session('login_method', 'not found') ?></strong>
                </p>
            </div>
        <?php endif; ?>
        
        <div class="grid">
            <!-- Authentication Status -->
            <div class="card">
                <h2>🔐 Authentication Status</h2>
                <table>
                    <tr>
                        <td>Auth Check</td>
                        <td>
                            <span class="status <?= Auth::check() ? 'status-success' : 'status-error' ?>">
                                <?= Auth::check() ? 'AUTHENTICATED' : 'NOT AUTHENTICATED' ?>
                            </span>
                        </td>
                    </tr>
                    <?php if (Auth::check()): ?>
                    <tr>
                        <td>User Email</td>
                        <td><code><?= Auth::user()->email ?></code></td>
                    </tr>
                    <tr>
                        <td>User ID</td>
                        <td><code><?= Auth::user()->id ?></code></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td>Guard</td>
                        <td><code><?= get_class(Auth::guard()) ?></code></td>
                    </tr>
                </table>
            </div>
            
            <!-- Session Status -->
            <div class="card">
                <h2>🗂️ Session Status</h2>
                <table>
                    <tr>
                        <td>Session ID</td>
                        <td><code><?= substr($sessionId, 0, 20) ?>...</code></td>
                    </tr>
                    <tr>
                        <td>Cookie Present</td>
                        <td>
                            <span class="status <?= $cookieValue ? 'status-success' : 'status-error' ?>">
                                <?= $cookieValue ? 'YES' : 'NO' ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>Session File</td>
                        <td>
                            <span class="status <?= $sessionExists ? 'status-success' : 'status-error' ?>">
                                <?= $sessionExists ? 'EXISTS' : 'MISSING' ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>Auth Keys</td>
                        <td>
                            <span class="status <?= count($authKeys) > 0 ? 'status-success' : 'status-warning' ?>">
                                <?= count($authKeys) ?> found
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Cookie Configuration -->
            <div class="card">
                <h2>🍪 Cookie Config</h2>
                <table>
                    <tr>
                        <td>Name</td>
                        <td><code><?= config('session.cookie') ?></code></td>
                    </tr>
                    <tr>
                        <td>Domain</td>
                        <td>
                            <span class="status <?= empty(config('session.domain')) ? 'status-success' : 'status-warning' ?>">
                                <?= config('session.domain') ?: '(empty)' ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>Secure</td>
                        <td>
                            <span class="status <?= !config('session.secure') ? 'status-success' : 'status-warning' ?>">
                                <?= config('session.secure') ? 'TRUE' : 'FALSE' ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>SameSite</td>
                        <td><code><?= config('session.same_site') ?: 'null' ?></code></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Login Methods -->
        <div class="card">
            <h2>🔑 Test Different Login Methods</h2>
            <p>Try each method to see which one successfully persists authentication:</p>
            <div class="button-group">
                <a href="?action=login" class="button">Standard Laravel Login</a>
                <a href="?action=login&force=direct" class="button button-warning">Direct Cookie Force</a>
                <a href="?action=login&force=queue" class="button button-warning">Cookie Queue Method</a>
                <?php if (Auth::check()): ?>
                <a href="?action=logout" class="button button-danger">Logout</a>
                <a href="/admin" class="button button-success">Go to Admin</a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Response Headers -->
        <?php if (!empty($setCookieHeaders)): ?>
        <div class="card">
            <h2>📤 Set-Cookie Headers</h2>
            <div class="pre-box">
                <?php foreach ($setCookieHeaders as $header): ?>
                <div><?= htmlspecialchars($header) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Session Data Preview -->
        <?php if ($authKeys): ?>
        <div class="card">
            <h2>🔍 Session Auth Keys</h2>
            <div class="pre-box">
                <?php foreach ($authKeys as $key): ?>
                <div><code><?= $key ?></code></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Instructions -->
        <div class="card">
            <h2>📋 Testing Instructions</h2>
            <ol>
                <li><strong>Standard Laravel Login:</strong> Uses Laravel's default session handling</li>
                <li><strong>Direct Cookie Force:</strong> Manually sets cookie with PHP's setcookie()</li>
                <li><strong>Cookie Queue Method:</strong> Uses Laravel's Cookie::queue()</li>
            </ol>
            <p>After clicking a login method, you'll be redirected. If authentication persists, you'll see a green success message.</p>
        </div>
    </div>
    
    <script>
    // Log current auth status
    console.log('Current auth status:', <?= Auth::check() ? 'true' : 'false' ?>);
    console.log('Session ID:', '<?= $sessionId ?>');
    console.log('Cookie present:', <?= $cookieValue ? 'true' : 'false' ?>);
    </script>
</body>
</html>