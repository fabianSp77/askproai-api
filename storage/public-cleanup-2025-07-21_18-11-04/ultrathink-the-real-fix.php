<?php
/**
 * ULTRATHINK: The REAL Fix
 * 
 * This identifies and fixes the actual root cause
 */

// NO OUTPUT before session operations!
ob_start();

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Don't use kernel->handle() yet - it might send headers
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Now we can work with session safely
session_start(); // Start PHP session

use Illuminate\Support\Facades\Auth;

$results = [];

// 1. Check PHP vs Laravel session conflict
$results['php_vs_laravel'] = [
    'php_session_id' => session_id(),
    'laravel_session_id' => app('session.store')->getId(),
    'match' => session_id() === app('session.store')->getId()
];

// 2. Check session configuration
$results['session_config'] = [
    'driver' => config('session.driver'),
    'cookie' => config('session.cookie'),
    'path' => config('session.path'),
    'domain' => config('session.domain'),
    'secure' => config('session.secure'),
    'http_only' => config('session.http_only'),
    'same_site' => config('session.same_site'),
];

// 3. Check actual cookie parameters
$results['cookie_params'] = session_get_cookie_params();

// 4. Test setting a session value
$testKey = 'test_' . time();
app('session.store')->put($testKey, 'test_value');
app('session.store')->save();

// Check if it was written
$sessionFile = storage_path('framework/sessions/' . app('session.store')->getId());
$fileExists = file_exists($sessionFile);
$results['session_write'] = [
    'file' => $sessionFile,
    'exists' => $fileExists,
    'written' => false
];

if ($fileExists) {
    $content = file_get_contents($sessionFile);
    $data = @unserialize($content);
    $results['session_write']['written'] = isset($data[$testKey]);
    $results['session_write']['all_keys'] = $data ? array_keys($data) : [];
}

// 5. The REAL fix - ensure session cookie params are correct
if (isset($_GET['fix'])) {
    // Override session cookie parameters
    session_set_cookie_params([
        'lifetime' => config('session.lifetime') * 60, // Convert minutes to seconds
        'path' => config('session.path', '/'),
        'domain' => config('session.domain', ''),
        'secure' => config('session.secure', false),
        'httponly' => config('session.http_only', true),
        'samesite' => config('session.same_site', 'lax')
    ]);
    
    // Regenerate session with new params
    session_regenerate_id(true);
    
    $results['fix_applied'] = true;
    $results['new_cookie_params'] = session_get_cookie_params();
}

// 6. Test login
if (isset($_GET['login'])) {
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    
    if ($user) {
        // Clear everything
        Auth::logout();
        app('session.store')->flush();
        
        // Set correct cookie params BEFORE login
        session_set_cookie_params([
            'lifetime' => config('session.lifetime') * 60,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'lax'
        ]);
        
        // Regenerate with new params
        app('session.store')->regenerate();
        
        // Now login
        Auth::login($user, true);
        app('session.store')->save();
        
        $results['login_test'] = [
            'user_found' => true,
            'auth_check' => Auth::check(),
            'auth_id' => Auth::id(),
            'session_data' => app('session.store')->all()
        ];
    }
}

// Clean any output
ob_clean();

// Now properly handle the request
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Generate our content
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>ULTRATHINK: The REAL Fix</title>
    <style>
        body {
            font-family: -apple-system, system-ui, sans-serif;
            background: #1e1e1e;
            color: #d4d4d4;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        h1 {
            color: #569cd6;
            border-bottom: 2px solid #569cd6;
            padding-bottom: 10px;
        }
        .section {
            background: #252526;
            border: 1px solid #3e3e3e;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .success { color: #4ec9b0; }
        .error { color: #f44747; }
        .warning { color: #dcdcaa; }
        pre {
            background: #1e1e1e;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            border: 1px solid #3e3e3e;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 5px;
            background: #569cd6;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s;
        }
        .button:hover {
            background: #4a8bc2;
        }
        .critical {
            background: #5a1d1d;
            border-color: #8b2525;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        code {
            background: #3e3e3e;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Consolas', 'Monaco', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧠 ULTRATHINK: The REAL Fix</h1>
        
        <?php if (Auth::check()): ?>
            <div class="section success">
                <h2>✅ Session Works!</h2>
                <p>Logged in as: <?= htmlspecialchars(Auth::user()->email) ?></p>
                <p>User ID: <?= Auth::id() ?></p>
                <a href="/admin" class="button">Go to Admin Panel</a>
            </div>
        <?php else: ?>
            <div class="section error">
                <h2>❌ Not Logged In</h2>
            </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>🔍 Diagnosis Results</h2>
            <pre><?= json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?></pre>
        </div>
        
        <?php
        // Identify the REAL problem
        $realProblem = null;
        
        if (!$results['php_vs_laravel']['match']) {
            $realProblem = "PHP and Laravel are using different session IDs!";
        } elseif ($results['cookie_params']['httponly'] === false) {
            $realProblem = "Session cookie HttpOnly is FALSE - cookies might not be set properly!";
        } elseif ($results['cookie_params']['lifetime'] === 0 && config('session.lifetime') > 0) {
            $realProblem = "Session cookie lifetime is 0 but should be " . config('session.lifetime') * 60 . " seconds!";
        } elseif (!$results['session_write']['written']) {
            $realProblem = "Session data is not being written to file!";
        }
        
        if ($realProblem):
        ?>
            <div class="critical">
                <h2>🚨 REAL Problem Found!</h2>
                <p><?= htmlspecialchars($realProblem) ?></p>
            </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>🛠️ The Solution</h2>
            <p>The session cookie parameters are not being set correctly. This fix will:</p>
            <ol>
                <li>Set correct cookie parameters (HttpOnly, Lifetime, etc.)</li>
                <li>Regenerate session with proper settings</li>
                <li>Ensure cookies persist across requests</li>
            </ol>
            
            <div style="text-align: center; margin-top: 20px;">
                <?php if (!isset($_GET['fix'])): ?>
                    <a href="?fix=1" class="button">Apply Fix</a>
                <?php else: ?>
                    <p class="success">✅ Fix Applied! Now test login:</p>
                    <a href="?login=1" class="button">Test Login</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section">
            <h2>📝 Manual Fix</h2>
            <p>Add this to <code>AppServiceProvider::boot()</code>:</p>
            <pre>
// Fix session cookie parameters
if (config('session.driver') === 'file') {
    session_set_cookie_params([
        'lifetime' => config('session.lifetime') * 60,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'lax'
    ]);
}
            </pre>
        </div>
    </div>
</body>
</html>
<?php
$content = ob_get_clean();
$response->setContent($content);
$response->send();
$kernel->terminate($request, $response);
?>