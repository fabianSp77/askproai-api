<?php
/**
 * Fix Auth Session Key Mismatch
 * 
 * The REAL problem: Different parts of the code use different session keys!
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;

$action = $_GET['action'] ?? '';

// Get all possible session keys
function getAllPossibleAuthKeys() {
    $guardName = Auth::getDefaultDriver();
    $guard = Auth::guard($guardName);
    
    $keys = [];
    
    // Key 1: Laravel default
    $keys['laravel_default'] = 'login_' . $guardName . '_' . sha1(Illuminate\Auth\SessionGuard::class);
    
    // Key 2: Custom guard
    $keys['custom_guard'] = 'login_' . $guardName . '_' . sha1(get_class($guard));
    
    // Key 3: Possible alternative
    $keys['alternative'] = 'login_' . $guardName . '_' . sha1('web');
    
    return $keys;
}

if ($action === 'fix-login') {
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    
    if ($user) {
        // Clear and regenerate
        Auth::logout();
        session()->flush();
        session()->regenerate();
        
        $sessionId = session()->getId();
        
        // Login
        Auth::login($user, true);
        
        // Get all possible keys
        $authKeys = getAllPossibleAuthKeys();
        
        // SET AUTH DATA WITH ALL POSSIBLE KEYS
        foreach ($authKeys as $type => $key) {
            session()->put($key, $user->id);
        }
        
        // Also set password hash
        session()->put('password_hash_' . Auth::getDefaultDriver(), $user->getAuthPassword());
        
        // Add markers
        session()->put('fix_applied', true);
        session()->put('all_keys_set', array_keys($authKeys));
        
        // Force save
        session()->save();
        
        // Debug: Check what was written
        $sessionFile = storage_path('framework/sessions/' . $sessionId);
        $written = file_exists($sessionFile) ? unserialize(file_get_contents($sessionFile)) : null;
        
        $debugData = [
            'session_id' => $sessionId,
            'auth_keys_used' => $authKeys,
            'session_data_keys' => is_array($written) ? array_keys($written) : [],
            'auth_keys_in_file' => is_array($written) ? array_filter(array_keys($written), function($k) { return strpos($k, 'login_') === 0; }) : [],
        ];
        
        // Save debug info
        file_put_contents(
            storage_path('logs/auth_key_fix_' . date('Y-m-d_H-i-s') . '.json'),
            json_encode($debugData, JSON_PRETTY_PRINT)
        );
        
        header('Location: ?action=verify');
        exit;
    }
}

// Current status
$authCheck = Auth::check();
$sessionId = session()->getId();
$sessionData = session()->all();
$authKeys = getAllPossibleAuthKeys();

// Check which keys exist in session
$existingAuthKeys = [];
foreach ($authKeys as $type => $key) {
    if (isset($sessionData[$key])) {
        $existingAuthKeys[$type] = [
            'key' => $key,
            'value' => $sessionData[$key]
        ];
    }
}

// Check session file
$sessionFile = storage_path('framework/sessions/' . $sessionId);
$fileData = null;
if (file_exists($sessionFile)) {
    $content = file_get_contents($sessionFile);
    $fileData = @unserialize($content);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Auth Session Key Mismatch</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            max-width: 1200px;
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
        }
        .discovery {
            background: #e3f2fd;
            border: 2px solid #2196f3;
            color: #0d47a1;
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
            font-size: 13px;
        }
        .key-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid #dee2e6;
        }
        .big-icon {
            font-size: 72px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔑 Fix Auth Session Key Mismatch</h1>
        
        <div class="discovery">
            <h2>💡 The Discovery!</h2>
            <p><strong>Different parts of Laravel use DIFFERENT session keys!</strong></p>
            <p>Your trace shows auth is written with key: <code>login_web_f091f34ca659bece7fff5e7c0e9971e22d1ee510</code></p>
            <p>But some code might expect a different key format!</p>
        </div>
        
        <?php if ($action === 'verify'): ?>
            <div class="status <?= $authCheck ? 'success' : 'error' ?>">
                <div class="big-icon"><?= $authCheck ? '✅' : '❌' ?></div>
                <h2><?= $authCheck ? 'SUCCESS! All Keys Set!' : 'Still Not Working' ?></h2>
                <p>Auth::check() = <strong><?= $authCheck ? 'TRUE' : 'FALSE' ?></strong></p>
                <?php if ($authCheck): ?>
                    <p>User: <strong><?= Auth::user()->email ?></strong></p>
                    <p>Fix Applied: <strong><?= session('fix_applied') ? 'YES' : 'NO' ?></strong></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="?action=fix-login" class="button">
                🔧 Apply Multi-Key Fix
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
        
        <h2>🔍 Possible Auth Keys</h2>
        <?php foreach ($authKeys as $type => $key): ?>
        <div class="key-box">
            <strong><?= $type ?>:</strong><br>
            <code><?= $key ?></code>
            <?php if (isset($existingAuthKeys[$type])): ?>
                <span style="color: green; margin-left: 10px;">✅ EXISTS (value: <?= $existingAuthKeys[$type]['value'] ?>)</span>
            <?php else: ?>
                <span style="color: red; margin-left: 10px;">❌ MISSING</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        
        <h2>📊 Session Analysis</h2>
        <table>
            <tr>
                <th>Check</th>
                <th>Value</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>Auth::check()</td>
                <td><?= $authCheck ? 'TRUE' : 'FALSE' ?></td>
                <td><?= $authCheck ? '✅' : '❌' ?></td>
            </tr>
            <tr>
                <td>Session ID</td>
                <td><code><?= $sessionId ?></code></td>
                <td>✅</td>
            </tr>
            <tr>
                <td>Auth Keys in Session</td>
                <td><?= count($existingAuthKeys) ?> found</td>
                <td><?= count($existingAuthKeys) > 0 ? '✅' : '❌' ?></td>
            </tr>
            <tr>
                <td>Guard Class</td>
                <td><code><?= get_class(Auth::guard()) ?></code></td>
                <td>ℹ️</td>
            </tr>
        </table>
        
        <?php if ($fileData && is_array($fileData)): ?>
        <h3>Session File Keys:</h3>
        <p><?= implode(', ', array_keys($fileData)) ?></p>
        <?php endif; ?>
    </div>
</body>
</html>