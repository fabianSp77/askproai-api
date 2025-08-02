<?php
/**
 * ULTRATHINK: Deep Session Analysis
 * 
 * Why is the session STILL not persisting?
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Auth;

// Start output buffering to capture any errors
ob_start();

$analysis = [
    'timestamp' => date('Y-m-d H:i:s'),
    'stage' => 'initial',
];

// 1. Check PHP session
$analysis['php_session'] = [
    'id' => session_id(),
    'status' => session_status(),
    'save_path' => session_save_path(),
    'gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
    'cookie_lifetime' => ini_get('session.cookie_lifetime'),
    'cookie_params' => session_get_cookie_params(),
];

// 2. Check Laravel session
$session = app('session.store');
$analysis['laravel_session'] = [
    'id' => $session->getId(),
    'name' => $session->getName(),
    'handler' => get_class($session->getHandler()),
    'all_data' => $session->all(),
];

// 3. Check if session is being started
if (method_exists($session, 'isStarted')) {
    $analysis['laravel_session']['is_started'] = $session->isStarted();
}

// 4. Check headers
$analysis['headers'] = [
    'already_sent' => headers_sent($file, $line),
    'sent_from' => headers_sent() ? "$file:$line" : null,
    'list' => headers_list(),
];

// 5. Check request
$analysis['request'] = [
    'url' => request()->url(),
    'secure' => request()->isSecure(),
    'host' => request()->getHost(),
    'user_agent' => request()->userAgent(),
    'ip' => request()->ip(),
];

// 6. Check cookies
$analysis['cookies'] = [
    'raw_cookie_header' => $_SERVER['HTTP_COOKIE'] ?? 'none',
    'parsed_cookies' => $_COOKIE,
    'session_cookie_name' => config('session.cookie'),
    'has_session_cookie' => isset($_COOKIE[config('session.cookie')]),
];

// 7. Test session write
$testKey = 'test_' . time();
$session->put($testKey, 'test_value');
$session->save();

// Check if it was written
$sessionFile = storage_path('framework/sessions/' . $session->getId());
$analysis['session_write_test'] = [
    'file_path' => $sessionFile,
    'file_exists' => file_exists($sessionFile),
    'file_permissions' => file_exists($sessionFile) ? substr(sprintf('%o', fileperms($sessionFile)), -4) : 'N/A',
    'directory_permissions' => substr(sprintf('%o', fileperms(dirname($sessionFile))), -4),
    'file_owner' => file_exists($sessionFile) ? posix_getpwuid(fileowner($sessionFile))['name'] : 'N/A',
    'process_user' => posix_getpwuid(posix_geteuid())['name'],
];

// 8. Check session file content
if (file_exists($sessionFile)) {
    $content = file_get_contents($sessionFile);
    $data = @unserialize($content);
    $analysis['session_file_content'] = [
        'raw_size' => strlen($content),
        'is_serialized' => $data !== false,
        'keys' => $data ? array_keys($data) : [],
        'has_test_key' => $data && isset($data[$testKey]),
    ];
}

// 9. Test login
if (isset($_GET['test_login'])) {
    $analysis['stage'] = 'login_test';
    
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    if ($user) {
        // Clear session
        Auth::logout();
        $session->flush();
        $oldId = $session->getId();
        
        // Regenerate
        $session->regenerate();
        $newId = $session->getId();
        
        // Login
        Auth::login($user, true);
        
        // Force save
        $session->save();
        
        // Check immediate state
        $analysis['login_test'] = [
            'user_found' => true,
            'old_session_id' => $oldId,
            'new_session_id' => $newId,
            'session_changed' => $oldId !== $newId,
            'auth_check_immediate' => Auth::check(),
            'auth_id' => Auth::id(),
            'session_data_after_login' => $session->all(),
        ];
        
        // Check what guard is doing
        $guard = Auth::guard('web');
        $analysis['login_test']['guard_user'] = $guard->user() ? $guard->user()->id : null;
        $analysis['login_test']['guard_class'] = get_class($guard);
        
        // Check session file after login
        $newSessionFile = storage_path('framework/sessions/' . $newId);
        if (file_exists($newSessionFile)) {
            $newContent = file_get_contents($newSessionFile);
            $newData = @unserialize($newContent);
            $analysis['login_test']['session_file_after_login'] = [
                'exists' => true,
                'size' => strlen($newContent),
                'keys' => $newData ? array_keys($newData) : [],
            ];
            
            // Look for auth keys
            $authKeys = [];
            if ($newData && is_array($newData)) {
                foreach ($newData as $key => $value) {
                    if (strpos($key, 'login_') === 0 || strpos($key, 'password_hash_') === 0) {
                        $authKeys[$key] = is_scalar($value) ? $value : gettype($value);
                    }
                }
            }
            $analysis['login_test']['auth_keys_in_file'] = $authKeys;
        }
    }
}

// 10. Check middleware
$kernelReflection = new ReflectionClass($kernel);
$middlewareGroupsProp = $kernelReflection->getProperty('middlewareGroups');
$middlewareGroupsProp->setAccessible(true);
$middlewareGroups = $middlewareGroupsProp->getValue($kernel);

$analysis['middleware'] = [
    'web_group' => array_map(function($m) {
        return is_string($m) ? basename(str_replace('\\', '/', $m)) : gettype($m);
    }, $middlewareGroups['web'] ?? []),
];

// Get any output that was generated
$output = ob_get_clean();
if ($output) {
    $analysis['unexpected_output'] = $output;
}

// Display results
?>
<!DOCTYPE html>
<html>
<head>
    <title>ULTRATHINK: Deep Session Analysis</title>
    <style>
        body { 
            font-family: 'Courier New', monospace; 
            background: #000; 
            color: #00ff00; 
            padding: 20px;
            margin: 0;
        }
        h1, h2 { 
            color: #00ff00; 
            text-shadow: 0 0 10px #00ff00;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .section {
            background: #0a0a0a;
            border: 1px solid #00ff00;
            padding: 15px;
            margin: 15px 0;
            overflow-x: auto;
        }
        pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .critical { color: #ff0000; font-weight: bold; }
        .warning { color: #ffff00; }
        .success { color: #00ff00; }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px;
            background: #00ff00;
            color: #000;
            text-decoration: none;
            border-radius: 3px;
            font-weight: bold;
        }
        .json { font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧠 ULTRATHINK: Deep Session Analysis</h1>
        
        <div class="section">
            <h2>Quick Status</h2>
            <pre>
Auth::check() = <?= Auth::check() ? '<span class="success">TRUE ✅</span>' : '<span class="critical">FALSE ❌</span>' ?>

Session Cookie = <?= isset($_COOKIE[config('session.cookie')]) ? '<span class="success">PRESENT ✅</span>' : '<span class="critical">MISSING ❌</span>' ?>

Session File = <?= file_exists($sessionFile) ? '<span class="success">EXISTS ✅</span>' : '<span class="critical">MISSING ❌</span>' ?>
            </pre>
        </div>
        
        <div class="section">
            <h2>Complete Analysis</h2>
            <pre class="json"><?= json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?></pre>
        </div>
        
        <?php
        // Identify issues
        $issues = [];
        
        if (!isset($_COOKIE[config('session.cookie')])) {
            $issues[] = "No session cookie in request";
        }
        
        if (!file_exists($sessionFile)) {
            $issues[] = "Session file does not exist";
        }
        
        if ($analysis['headers']['already_sent']) {
            $issues[] = "Headers already sent from " . $analysis['headers']['sent_from'];
        }
        
        if (!$analysis['request']['secure'] && config('session.secure')) {
            $issues[] = "Using HTTP but session.secure=true";
        }
        
        if (!empty($issues)) {
            echo '<div class="section critical">';
            echo '<h2>🚨 Issues Detected:</h2>';
            echo '<ul>';
            foreach ($issues as $issue) {
                echo '<li>' . htmlspecialchars($issue) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        ?>
        
        <div class="section">
            <a href="?" class="button">Refresh Analysis</a>
            <a href="?test_login=1" class="button">Test Login Process</a>
        </div>
    </div>
</body>
</html>