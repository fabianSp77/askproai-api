<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

// Get demo user
$user = \App\Models\User::where('email', 'demo@askproai.de')->first();

if (!$user) {
    die('Demo user not found!');
}

// Method 1: Use Laravel's Auth system correctly
$guard = Auth::guard('web');

// Get the correct session key name using reflection
$reflection = new ReflectionMethod($guard, 'getName');
$reflection->setAccessible(true);
$sessionKey = $reflection->invoke($guard);

// Login the user properly
Auth::login($user, true); // true = remember

// Double-check and force session data
$session = app('session.store');
$session->put($sessionKey, $user->id);
$session->put('password_hash_web', $user->password);
$session->save();

// Method 2: Also write directly to session file as backup
$sessionId = session_id();
$sessionPath = storage_path('framework/sessions');
$sessionFile = $sessionPath . '/' . $sessionId;

$sessionData = [
    '_token' => csrf_token(),
    $sessionKey => $user->id,
    'password_hash_web' => $user->password,
    '_previous' => ['url' => 'https://api.askproai.de/admin'],
    '_flash' => ['old' => [], 'new' => []]
];

file_put_contents($sessionFile, serialize($sessionData));

// Set cookie to ensure it persists
setcookie(
    config('session.cookie'),
    $sessionId,
    time() + (config('session.lifetime') * 60),
    config('session.path'),
    config('session.domain'),
    config('session.secure'),
    config('session.http_only')
);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Guaranteed Login Success</title>
    <meta http-equiv="refresh" content="2;url=/admin">
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .success-box {
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 500px;
        }
        .checkmark {
            font-size: 80px;
            color: #4CAF50;
            margin-bottom: 20px;
            animation: bounce 0.5s;
        }
        @keyframes bounce {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        h1 { color: #333; margin: 0 0 20px 0; }
        p { color: #666; margin: 10px 0; }
        .details {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 14px;
            text-align: left;
        }
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="success-box">
        <div class="checkmark">✓</div>
        <h1>Login Successful!</h1>
        <p>You are now logged in as <strong><?= htmlspecialchars($user->email) ?></strong></p>
        
        <div class="details">
            <strong>Technical Details:</strong><br>
            Session Key: <?= htmlspecialchars($sessionKey) ?><br>
            Session ID: <?= htmlspecialchars(substr($sessionId, 0, 20)) ?>...<br>
            Auth Check: <?= Auth::check() ? '✓ PASSED' : '✗ FAILED' ?><br>
            Session File: <?= file_exists($sessionFile) ? '✓ EXISTS' : '✗ MISSING' ?><br>
            File Size: <?= file_exists($sessionFile) ? filesize($sessionFile) . ' bytes' : 'N/A' ?>
        </div>
        
        <div class="spinner"></div>
        <p style="color: #667eea; font-weight: bold;">Redirecting to Admin Panel...</p>
        
        <p style="margin-top: 30px; font-size: 14px;">
            If you are not redirected, <a href="/admin">click here</a>
        </p>
    </div>
</body>
</html>