<?php
/**
 * FINAL REAL FIX - The actual solution
 * 
 * Based on our findings:
 * - Session files ARE being written
 * - But they DON'T contain auth data
 * - This means Auth::login() is not persisting to session
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();

// CRITICAL: Handle request BEFORE any session operations
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;

$action = $_GET['action'] ?? '';

if ($action === 'real-fix-login') {
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    
    if ($user) {
        // Clear everything
        Auth::logout();
        session()->flush();
        session()->regenerate();
        
        // Get the new session ID
        $sessionId = session()->getId();
        
        // Login the user
        Auth::login($user, true);
        
        // FORCE the auth data into session manually
        $guardName = Auth::getDefaultDriver();
        $sessionKey = 'login_' . $guardName . '_' . sha1(get_class(Auth::guard($guardName)));
        
        // Manually set the auth session data
        session()->put($sessionKey, $user->id);
        session()->put('password_hash_' . $guardName, $user->getAuthPassword());
        
        // Add test marker
        session()->put('manual_fix_applied', true);
        session()->put('manual_fix_time', time());
        
        // CRITICAL: Force session to save NOW
        session()->save();
        
        // Double-check by writing directly to file
        $sessionFile = storage_path('framework/sessions/' . $sessionId);
        $sessionData = session()->all();
        
        // Ensure auth keys are in the data
        if (!isset($sessionData[$sessionKey])) {
            $sessionData[$sessionKey] = $user->id;
        }
        
        // Write session file manually as backup
        file_put_contents($sessionFile, serialize($sessionData));
        
        // Log what we did
        $debugLog = [
            'action' => 'manual_fix_login',
            'session_id' => $sessionId,
            'session_key' => $sessionKey,
            'user_id' => $user->id,
            'session_file' => $sessionFile,
            'file_written' => file_exists($sessionFile),
            'session_data_keys' => array_keys($sessionData),
        ];
        
        file_put_contents(
            storage_path('logs/manual_fix_' . date('Y-m-d_H-i-s') . '.json'),
            json_encode($debugLog, JSON_PRETTY_PRINT)
        );
        
        header('Location: ?action=verify');
        exit;
    }
}

// Check current auth status
$authCheck = Auth::check();
$sessionId = session()->getId();
$sessionFile = storage_path('framework/sessions/' . $sessionId);
$sessionData = null;

if (file_exists($sessionFile)) {
    $content = file_get_contents($sessionFile);
    $sessionData = @unserialize($content);
}

// Find auth keys in session
$authKeys = [];
if (is_array($sessionData)) {
    foreach ($sessionData as $key => $value) {
        if (strpos($key, 'login_') === 0 || strpos($key, 'password_hash_') === 0) {
            $authKeys[$key] = $value;
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Final Real Fix</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background: #f0f0f5;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            font-size: 36px;
            margin-bottom: 40px;
        }
        .finding {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            font-size: 18px;
        }
        .solution {
            background: #d1ecf1;
            border: 2px solid #17a2b8;
            color: #0c5460;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .status {
            padding: 30px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #dc3545;
        }
        .button {
            display: inline-block;
            padding: 15px 40px;
            margin: 10px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 18px;
            transition: all 0.3s;
        }
        .button:hover {
            background: #0056b3;
            transform: translateY(-2px);
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
            padding: 3px 8px;
            border-radius: 4px;
            font-family: monospace;
        }
        .big-icon {
            font-size: 72px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 Final Real Fix</h1>
        
        <div class="finding">
            <h2>📍 The Real Problem Found!</h2>
            <p><strong>Session files ARE written, but they DON'T contain auth data!</strong></p>
            <p>Laravel's Auth::login() is NOT persisting the login state to the session file.</p>
        </div>
        
        <?php if ($action === 'verify'): ?>
            <div class="status <?= $authCheck ? 'success' : 'error' ?>">
                <div class="big-icon"><?= $authCheck ? '✅' : '❌' ?></div>
                <h2><?= $authCheck ? 'IT WORKS!' : 'Still Not Working' ?></h2>
                <p>Auth::check() = <strong><?= $authCheck ? 'TRUE' : 'FALSE' ?></strong></p>
                <?php if ($authCheck): ?>
                    <p>User: <strong><?= Auth::user()->email ?></strong></p>
                    <p>Manual Fix: <strong><?= session('manual_fix_applied') ? 'Applied' : 'Not Applied' ?></strong></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="solution">
            <h2>💡 The Solution</h2>
            <p>This fix manually writes auth data to the session file:</p>
            <ol>
                <li>Login the user with Auth::login()</li>
                <li>Manually set the session key that Laravel expects</li>
                <li>Force session save</li>
                <li>Write directly to session file as backup</li>
            </ol>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="?action=real-fix-login" class="button">
                🔧 Apply Real Fix
            </a>
            <a href="?" class="button">
                🔄 Refresh
            </a>
            <?php if ($authCheck): ?>
                <a href="/admin" class="button" style="background: #28a745;">
                    📊 Go to Admin
                </a>
            <?php endif; ?>
        </div>
        
        <h2>📊 Session Analysis</h2>
        <table>
            <tr>
                <th>Check</th>
                <th>Value</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>Session ID</td>
                <td><code><?= $sessionId ?></code></td>
                <td>✅</td>
            </tr>
            <tr>
                <td>Session File Exists</td>
                <td><?= file_exists($sessionFile) ? 'Yes' : 'No' ?></td>
                <td><?= file_exists($sessionFile) ? '✅' : '❌' ?></td>
            </tr>
            <tr>
                <td>Auth Keys in File</td>
                <td><?= count($authKeys) ?> found</td>
                <td><?= count($authKeys) > 0 ? '✅' : '❌' ?></td>
            </tr>
            <tr>
                <td>Auth::check()</td>
                <td><?= $authCheck ? 'TRUE' : 'FALSE' ?></td>
                <td><?= $authCheck ? '✅' : '❌' ?></td>
            </tr>
        </table>
        
        <?php if ($authKeys): ?>
        <h3>Auth Keys Found:</h3>
        <table>
            <?php foreach ($authKeys as $key => $value): ?>
            <tr>
                <td><code><?= $key ?></code></td>
                <td><?= is_scalar($value) ? $value : gettype($value) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        
        <?php if (is_array($sessionData)): ?>
        <h3>All Session Keys:</h3>
        <p><?= implode(', ', array_keys($sessionData)) ?></p>
        <?php endif; ?>
    </div>
</body>
</html>