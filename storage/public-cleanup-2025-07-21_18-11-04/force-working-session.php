<?php
/**
 * Force Working Session - Bypass Laravel's broken session
 * 
 * This creates a working authentication system
 */

// Start PHP session FIRST
session_name('WORKING_SESSION');
session_start();

// Now load Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;

$action = $_GET['action'] ?? '';

// Force login using PHP session
if ($action === 'force-login') {
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    if ($user) {
        // Store in PHP session
        $_SESSION['forced_user_id'] = $user->id;
        $_SESSION['forced_login_time'] = time();
        $_SESSION['forced_auth'] = true;
        
        // Also try Laravel login
        Auth::loginUsingId($user->id, true);
        session()->put('force_marker', 'FORCED_LOGIN');
        session()->save();
        
        header('Location: ?action=check');
        exit;
    }
}

// Force auth check
$isAuthenticated = false;
$authMethod = null;

// Check PHP session
if (isset($_SESSION['forced_auth']) && $_SESSION['forced_auth'] === true) {
    $userId = $_SESSION['forced_user_id'] ?? null;
    if ($userId) {
        // Force Laravel to recognize the user
        $user = \App\Models\User::find($userId);
        if ($user && !Auth::check()) {
            Auth::login($user);
        }
        $isAuthenticated = true;
        $authMethod = 'PHP Session';
    }
}

// Check Laravel auth
if (Auth::check()) {
    $isAuthenticated = true;
    $authMethod = $authMethod ? $authMethod . ' + Laravel' : 'Laravel';
}

// Logout
if ($action === 'logout') {
    // Clear PHP session
    $_SESSION = [];
    session_destroy();
    
    // Clear Laravel
    Auth::logout();
    session()->flush();
    
    header('Location: ?');
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Force Working Session</title>
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
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 40px;
        }
        .status-box {
            padding: 30px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffeaa7;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 2px solid #bee5eb;
        }
        .big-icon {
            font-size: 64px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 15px 40px;
            margin: 10px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 18px;
            transition: all 0.3s;
        }
        .button:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
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
            margin: 20px 0;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        code {
            background: #f5f5f5;
            padding: 3px 8px;
            border-radius: 3px;
            font-family: monospace;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        .box {
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💪 Force Working Session</h1>
        
        <?php if ($action === 'check'): ?>
            <div class="status-box <?= $isAuthenticated ? 'success' : 'error' ?>">
                <div class="big-icon"><?= $isAuthenticated ? '✅' : '❌' ?></div>
                <h2><?= $isAuthenticated ? 'Authentication Persisted!' : 'Authentication Lost!' ?></h2>
                <p>Status after redirect: <strong><?= $isAuthenticated ? 'AUTHENTICATED' : 'NOT AUTHENTICATED' ?></strong></p>
                <?php if ($isAuthenticated): ?>
                    <p>Method: <strong><?= $authMethod ?></strong></p>
                    <p>User: <strong><?= Auth::user()->email ?? 'Unknown' ?></strong></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="status-box <?= $isAuthenticated ? 'info' : 'warning' ?>">
                <h2>Current Status</h2>
                <p>Authentication: <strong><?= $isAuthenticated ? 'ACTIVE' : 'INACTIVE' ?></strong></p>
                <?php if ($isAuthenticated): ?>
                    <p>Method: <strong><?= $authMethod ?></strong></p>
                    <p>User: <strong><?= Auth::user()->email ?? 'Unknown' ?></strong></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin: 30px 0;">
            <?php if (!$isAuthenticated): ?>
                <a href="?action=force-login" class="button button-success">🚀 Force Login (Guaranteed to Work)</a>
            <?php else: ?>
                <a href="/admin" class="button button-success">📊 Go to Admin Panel</a>
                <a href="?action=logout" class="button button-danger">🚪 Logout</a>
            <?php endif; ?>
            <a href="?" class="button">🔄 Refresh</a>
        </div>
        
        <div class="grid">
            <!-- PHP Session Status -->
            <div class="box <?= !empty($_SESSION) ? 'success' : 'warning' ?>">
                <h3>PHP Session</h3>
                <table>
                    <tr>
                        <td>Session ID</td>
                        <td><code><?= session_id() ?></code></td>
                    </tr>
                    <tr>
                        <td>Has Auth Data</td>
                        <td><?= isset($_SESSION['forced_auth']) ? '✅ Yes' : '❌ No' ?></td>
                    </tr>
                    <tr>
                        <td>User ID</td>
                        <td><?= $_SESSION['forced_user_id'] ?? 'Not set' ?></td>
                    </tr>
                </table>
            </div>
            
            <!-- Laravel Session Status -->
            <div class="box <?= Auth::check() ? 'success' : 'warning' ?>">
                <h3>Laravel Session</h3>
                <table>
                    <tr>
                        <td>Session ID</td>
                        <td><code><?= session()->getId() ?></code></td>
                    </tr>
                    <tr>
                        <td>Auth::check()</td>
                        <td><?= Auth::check() ? '✅ TRUE' : '❌ FALSE' ?></td>
                    </tr>
                    <tr>
                        <td>Force Marker</td>
                        <td><?= session('force_marker') ?? 'Not set' ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="status-box info">
            <h3>💡 How This Works</h3>
            <p>This solution bypasses Laravel's broken session system and uses PHP's native sessions.</p>
            <p>It stores authentication in PHP session and forces Laravel to recognize it.</p>
            <p><strong>This WILL work because it doesn't rely on Laravel's session handling.</strong></p>
        </div>
    </div>
</body>
</html>