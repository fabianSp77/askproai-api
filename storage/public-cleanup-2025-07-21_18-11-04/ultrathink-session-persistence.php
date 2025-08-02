<?php
/**
 * ULTRATHINK: Session Persistence Analysis
 * 
 * Problem: Login works momentarily but session doesn't persist to next request
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Auth;

$analysis = [];

// 1. Current Request Analysis
$analysis['current_request'] = [
    'session_id' => session_id(),
    'laravel_session_id' => session()->getId(),
    'cookie_received' => $_COOKIE[config('session.cookie')] ?? 'NO COOKIE',
    'auth_check' => Auth::check(),
    'auth_id' => Auth::id(),
];

// 2. Session Configuration
$analysis['session_config'] = [
    'driver' => config('session.driver'),
    'lifetime' => config('session.lifetime'),
    'expire_on_close' => config('session.expire_on_close'),
    'cookie' => config('session.cookie'),
    'path' => config('session.path'),
    'domain' => config('session.domain'),
    'secure' => config('session.secure'),
    'http_only' => config('session.http_only'),
    'same_site' => config('session.same_site'),
];

// 3. Cookie Analysis
$analysis['cookies'] = [
    'all_cookies' => $_COOKIE,
    'cookie_params' => session_get_cookie_params(),
];

// 4. Session File Analysis
$sessionId = session()->getId();
$sessionFile = storage_path('framework/sessions/' . $sessionId);
$analysis['session_file'] = [
    'path' => $sessionFile,
    'exists' => file_exists($sessionFile),
    'readable' => is_readable($sessionFile),
    'writable' => is_writable($sessionFile),
];

if (file_exists($sessionFile)) {
    $content = file_get_contents($sessionFile);
    $data = @unserialize($content);
    $analysis['session_file']['size'] = filesize($sessionFile);
    $analysis['session_file']['data_keys'] = $data ? array_keys($data) : [];
    $analysis['session_file']['has_auth_key'] = false;
    
    if ($data && is_array($data)) {
        foreach ($data as $key => $value) {
            if (strpos($key, 'login_web_') === 0) {
                $analysis['session_file']['has_auth_key'] = true;
                $analysis['session_file']['auth_key'] = $key;
                $analysis['session_file']['auth_value'] = $value;
            }
        }
    }
}

// 5. Headers Analysis
$analysis['headers'] = [
    'sent' => headers_sent($file, $line),
    'sent_from' => headers_sent() ? "$file:$line" : null,
    'list' => headers_list(),
];

// 6. Test Login
if (isset($_GET['test'])) {
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    
    if ($user) {
        // Clear everything
        Auth::logout();
        session()->flush();
        
        // Get new session
        session()->regenerate();
        $newSessionId = session()->getId();
        
        // Login
        Auth::login($user, true);
        
        // Force save
        session()->save();
        
        // Check what happened
        $analysis['login_test'] = [
            'old_session_id' => $sessionId,
            'new_session_id' => $newSessionId,
            'session_changed' => $sessionId !== $newSessionId,
            'auth_check_immediate' => Auth::check(),
            'session_data_after' => session()->all(),
        ];
        
        // Check new session file
        $newSessionFile = storage_path('framework/sessions/' . $newSessionId);
        if (file_exists($newSessionFile)) {
            $newData = @unserialize(file_get_contents($newSessionFile));
            $analysis['login_test']['new_file_exists'] = true;
            $analysis['login_test']['new_file_data_keys'] = $newData ? array_keys($newData) : [];
        }
    }
}

// 7. Critical Issue Detection
$issues = [];

// Check if session cookie is being set
if (!isset($_COOKIE[config('session.cookie')])) {
    $issues[] = "❌ No session cookie received from browser";
}

// Check if session file exists
if (!file_exists($sessionFile)) {
    $issues[] = "❌ Session file does not exist";
}

// Check if using HTTPS but secure cookie is false
if (request()->isSecure() && !config('session.secure')) {
    $issues[] = "⚠️ Using HTTPS but session.secure is false";
}

// Check if domain mismatch
if (config('session.domain') && config('session.domain') !== request()->getHost()) {
    $issues[] = "❌ Session domain (" . config('session.domain') . ") doesn't match request host (" . request()->getHost() . ")";
}

// Display results
?>
<!DOCTYPE html>
<html>
<head>
    <title>ULTRATHINK: Session Persistence Analysis</title>
    <style>
        body { 
            font-family: 'Courier New', monospace; 
            background: #000; 
            color: #00ff00; 
            padding: 20px;
            line-height: 1.6;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto;
        }
        h1, h2 { 
            color: #00ff00; 
            text-shadow: 0 0 10px #00ff00;
        }
        .section {
            background: #0a0a0a;
            border: 1px solid #00ff00;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .critical {
            color: #ff0000;
            font-weight: bold;
        }
        .warning {
            color: #ffff00;
        }
        .success {
            color: #00ff00;
        }
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px;
            background: #00ff00;
            color: #000;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        .button:hover {
            background: #00cc00;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧠 ULTRATHINK: Session Persistence Analysis</h1>
        
        <?php if (!empty($issues)): ?>
        <div class="section critical">
            <h2>🚨 Critical Issues Found:</h2>
            <?php foreach ($issues as $issue): ?>
                <p><?= $issue ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>1. Current Request State</h2>
            <pre><?= json_encode($analysis['current_request'], JSON_PRETTY_PRINT) ?></pre>
        </div>
        
        <div class="section">
            <h2>2. Session Configuration</h2>
            <pre><?= json_encode($analysis['session_config'], JSON_PRETTY_PRINT) ?></pre>
        </div>
        
        <div class="section">
            <h2>3. Cookie Analysis</h2>
            <pre><?= json_encode($analysis['cookies'], JSON_PRETTY_PRINT) ?></pre>
        </div>
        
        <div class="section">
            <h2>4. Session File Analysis</h2>
            <pre><?= json_encode($analysis['session_file'], JSON_PRETTY_PRINT) ?></pre>
        </div>
        
        <div class="section">
            <h2>5. Headers Analysis</h2>
            <pre><?= json_encode($analysis['headers'], JSON_PRETTY_PRINT) ?></pre>
        </div>
        
        <?php if (isset($analysis['login_test'])): ?>
        <div class="section">
            <h2>6. Login Test Results</h2>
            <pre><?= json_encode($analysis['login_test'], JSON_PRETTY_PRINT) ?></pre>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>🔍 Root Cause Analysis</h2>
            <?php
            $rootCause = "UNKNOWN";
            
            if (!isset($_COOKIE[config('session.cookie')])) {
                $rootCause = "Browser is not sending session cookie";
            } elseif (!file_exists($sessionFile)) {
                $rootCause = "Session file is not being created";
            } elseif (!$analysis['session_file']['has_auth_key'] ?? false) {
                $rootCause = "Auth key not being written to session";
            } elseif (config('session.domain') && config('session.domain') !== request()->getHost()) {
                $rootCause = "Session cookie domain mismatch";
            } elseif (request()->isSecure() && !config('session.secure')) {
                $rootCause = "HTTPS/Secure cookie mismatch";
            }
            ?>
            <p class="critical">Probable Root Cause: <?= $rootCause ?></p>
        </div>
        
        <div class="section">
            <a href="?" class="button">Refresh Analysis</a>
            <a href="?test=1" class="button">Test Login</a>
            <a href="/admin" class="button">Try Admin Panel</a>
        </div>
    </div>
</body>
</html>