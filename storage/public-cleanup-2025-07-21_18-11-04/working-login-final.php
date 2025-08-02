<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

// Start session if needed
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Get demo user
$user = \App\Models\User::where('email', 'demo@askproai.de')->first();

if (!$user) {
    die('Demo user not found!');
}

// Get the ACTUAL session key
$guard = Auth::guard('web');
$reflection = new ReflectionMethod($guard, 'getName');
$reflection->setAccessible(true);
$actualSessionKey = $reflection->invoke($guard);

// Login the user
Auth::login($user, true);

// Get session
$session = app('session.store');
$sessionId = session_id();

// Ensure session has correct data
$session->put($actualSessionKey, $user->id);
$session->put('password_hash_web', $user->password);
$session->save();

// Also write to file if session ID exists
if ($sessionId) {
    $sessionPath = storage_path('framework/sessions');
    $sessionFile = $sessionPath . '/' . $sessionId;
    
    // Get current session data and merge
    $currentData = [];
    if (file_exists($sessionFile)) {
        $currentData = unserialize(file_get_contents($sessionFile));
    }
    
    // Merge with auth data
    $sessionData = array_merge($currentData, [
        '_token' => csrf_token(),
        $actualSessionKey => $user->id,
        'password_hash_web' => $user->password,
        '_previous' => ['url' => 'https://api.askproai.de/admin'],
        '_flash' => ['old' => [], 'new' => []]
    ]);
    
    file_put_contents($sessionFile, serialize($sessionData));
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Working Login Final</title>
    <meta http-equiv="refresh" content="1;url=/admin">
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: #1a1a1a;
            color: white;
        }
        .box {
            background: #2a2a2a;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            text-align: center;
            border: 2px solid #4CAF50;
        }
        .success { color: #4CAF50; font-size: 60px; margin-bottom: 20px; }
        h1 { margin: 0 0 20px 0; }
        .details {
            background: #1a1a1a;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 14px;
            text-align: left;
            border: 1px solid #444;
        }
        .key { color: #4CAF50; }
        .value { color: #2196F3; }
    </style>
</head>
<body>
    <div class="box">
        <div class="success">✓</div>
        <h1>Login Successful!</h1>
        <p>Using the CORRECT session key</p>
        
        <div class="details">
            <div><span class="key">Session Key:</span> <span class="value"><?= htmlspecialchars($actualSessionKey) ?></span></div>
            <div><span class="key">User ID:</span> <span class="value"><?= $user->id ?></span></div>
            <div><span class="key">Email:</span> <span class="value"><?= htmlspecialchars($user->email) ?></span></div>
            <div><span class="key">Auth Check:</span> <span class="value"><?= Auth::check() ? 'PASSED ✓' : 'FAILED ✗' ?></span></div>
            <div><span class="key">Session ID:</span> <span class="value"><?= htmlspecialchars($sessionId) ?></span></div>
        </div>
        
        <p>Redirecting to admin panel...</p>
    </div>
</body>
</html>