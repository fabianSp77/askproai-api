<?php
/**
 * Debug Session Core - Find the REAL problem
 * 
 * This debugs the session system at its core
 */

// Start without Laravel first
session_start();
$phpSessionId = session_id();
$_SESSION['php_test'] = 'PHP_SESSION_WORKS';

// Now load Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();

// Check session config BEFORE handling request
$sessionConfig = [
    'driver' => config('session.driver'),
    'lifetime' => config('session.lifetime'),
    'path' => config('session.path'),
    'domain' => config('session.domain'),
    'secure' => config('session.secure'),
    'files' => config('session.files'),
];

// Handle request
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;

$action = $_GET['action'] ?? '';

// Debug session write process
if ($action === 'test-write') {
    $sessionId = session()->getId();
    $sessionPath = storage_path('framework/sessions/' . $sessionId);
    
    // Try to write directly
    $testData = [
        '_token' => session()->token(),
        'test_write' => 'DIRECT_WRITE_TEST',
        'timestamp' => time(),
    ];
    
    // Write to session
    session()->put('test_write', 'SESSION_PUT_TEST');
    session()->put('timestamp', time());
    
    // Force save
    session()->save();
    
    // Check if file exists after save
    $fileExists = file_exists($sessionPath);
    $fileContent = $fileExists ? file_get_contents($sessionPath) : 'FILE_NOT_FOUND';
    
    // Try manual file write
    $manualPath = storage_path('framework/sessions/manual_test_' . time());
    $manualWrite = file_put_contents($manualPath, serialize($testData));
    
    die(json_encode([
        'session_id' => $sessionId,
        'session_path' => $sessionPath,
        'file_exists_after_save' => $fileExists,
        'file_content' => $fileContent,
        'manual_write_path' => $manualPath,
        'manual_write_success' => $manualWrite !== false,
        'session_data' => session()->all(),
        'storage_path_writable' => is_writable(storage_path('framework/sessions')),
        'session_handler' => get_class(session()->getHandler()),
    ], JSON_PRETTY_PRINT));
}

// Test login with detailed debugging
if ($action === 'debug-login') {
    $steps = [];
    
    $steps[] = ['step' => 'Initial session ID', 'value' => session()->getId()];
    
    // Get user
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    $steps[] = ['step' => 'User found', 'value' => $user ? 'Yes' : 'No'];
    
    if ($user) {
        // Clear session
        Auth::logout();
        session()->flush();
        $steps[] = ['step' => 'After flush', 'session_data' => session()->all()];
        
        // Regenerate
        session()->regenerate();
        $newId = session()->getId();
        $steps[] = ['step' => 'After regenerate', 'new_id' => $newId];
        
        // Login
        Auth::login($user, true);
        $steps[] = ['step' => 'After login', 'auth_check' => Auth::check()];
        
        // Add test data
        session()->put('debug_marker', 'LOGIN_SUCCESS');
        $steps[] = ['step' => 'After put marker', 'session_data' => session()->all()];
        
        // Save
        session()->save();
        $steps[] = ['step' => 'After save', 'session_data' => session()->all()];
        
        // Check file
        $sessionFile = storage_path('framework/sessions/' . $newId);
        $steps[] = [
            'step' => 'Session file check',
            'file_exists' => file_exists($sessionFile),
            'file_size' => file_exists($sessionFile) ? filesize($sessionFile) : 0,
        ];
        
        // Manual check of session data
        if (file_exists($sessionFile)) {
            $content = file_get_contents($sessionFile);
            $data = @unserialize($content);
            $steps[] = [
                'step' => 'File content',
                'has_auth_keys' => is_array($data) ? count(array_filter(array_keys($data), function($k) { return strpos($k, 'login_') === 0; })) : 0,
                'has_marker' => is_array($data) && isset($data['debug_marker']),
                'keys' => is_array($data) ? array_keys($data) : [],
            ];
        }
    }
    
    header('Content-Type: application/json');
    die(json_encode(['debug_steps' => $steps], JSON_PRETTY_PRINT));
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Session Core</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
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
            text-align: center;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .box {
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border-color: #ffeaa7;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
        }
        .button:hover {
            background: #0056b3;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug Session Core</h1>
        
        <div class="grid">
            <!-- PHP Session -->
            <div class="box <?= isset($_SESSION['php_test']) ? 'success' : 'error' ?>">
                <h3>PHP Native Session</h3>
                <p>Session ID: <code><?= $phpSessionId ?></code></p>
                <p>Test Value: <code><?= $_SESSION['php_test'] ?? 'NOT SET' ?></code></p>
                <p>Status: <?= isset($_SESSION['php_test']) ? '✅ Working' : '❌ Not Working' ?></p>
            </div>
            
            <!-- Laravel Session -->
            <div class="box <?= session()->has('test') ? 'success' : 'warning' ?>">
                <h3>Laravel Session</h3>
                <p>Session ID: <code><?= session()->getId() ?></code></p>
                <p>Driver: <code><?= config('session.driver') ?></code></p>
                <p>Handler: <code><?= get_class(session()->getHandler()) ?></code></p>
                <p>Has Data: <?= count(session()->all()) > 0 ? 'Yes (' . count(session()->all()) . ' keys)' : 'No' ?></p>
            </div>
            
            <!-- Session Files -->
            <div class="box info">
                <h3>Session Storage</h3>
                <?php
                $sessionPath = storage_path('framework/sessions');
                $writable = is_writable($sessionPath);
                $fileCount = count(glob($sessionPath . '/*'));
                ?>
                <p>Path: <code><?= $sessionPath ?></code></p>
                <p>Writable: <?= $writable ? '✅ Yes' : '❌ No' ?></p>
                <p>Files: <?= $fileCount ?></p>
                <p>Permissions: <code><?= substr(sprintf('%o', fileperms($sessionPath)), -4) ?></code></p>
            </div>
        </div>
        
        <!-- Session Config -->
        <div class="box info">
            <h3>Session Configuration</h3>
            <pre><?= json_encode($sessionConfig, JSON_PRETTY_PRINT) ?></pre>
        </div>
        
        <!-- Test Buttons -->
        <div style="text-align: center; margin: 30px 0;">
            <a href="?action=test-write" class="button" target="_blank">Test Session Write</a>
            <a href="?action=debug-login" class="button" target="_blank">Debug Login Process</a>
            <a href="?" class="button">Refresh</a>
        </div>
        
        <!-- Current Session Data -->
        <div class="box">
            <h3>Current Session Data</h3>
            <pre><?= json_encode(session()->all(), JSON_PRETTY_PRINT) ?></pre>
        </div>
    </div>
</body>
</html>