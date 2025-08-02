<?php
/**
 * Ultimate Session Test - Final Analysis
 * 
 * This test shows exactly why sessions are not persisting
 */

// Buffer output to prevent headers already sent
ob_start();

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;

// Get action
$action = $_GET['action'] ?? '';

// Test results
$tests = [];

// Test 1: Current session
$session = app('session.store');
$tests['current_session'] = [
    'id' => $session->getId(),
    'is_started' => method_exists($session, 'isStarted') ? $session->isStarted() : 'unknown',
    'has_data' => !empty($session->all()),
    'data' => $session->all(),
];

// Test 2: Auth status
$tests['auth_status'] = [
    'check' => Auth::check(),
    'id' => Auth::id(),
    'user' => Auth::user() ? Auth::user()->email : null,
];

// Test 3: Cookie analysis
$cookieName = config('session.cookie');
$tests['cookies'] = [
    'session_cookie_name' => $cookieName,
    'cookie_present' => isset($_COOKIE[$cookieName]),
    'cookie_value' => $_COOKIE[$cookieName] ?? 'not found',
    'all_cookies' => array_keys($_COOKIE),
];

// Test 4: Session file
$sessionFile = storage_path('framework/sessions/' . $session->getId());
$tests['session_file'] = [
    'path' => $sessionFile,
    'exists' => file_exists($sessionFile),
];

if (file_exists($sessionFile)) {
    $content = file_get_contents($sessionFile);
    $data = @unserialize($content);
    $tests['session_file']['size'] = strlen($content);
    $tests['session_file']['has_auth_key'] = false;
    
    if ($data && is_array($data)) {
        foreach ($data as $key => $value) {
            if (strpos($key, 'login_web_') === 0) {
                $tests['session_file']['has_auth_key'] = true;
                $tests['session_file']['auth_key'] = $key;
                $tests['session_file']['auth_user_id'] = $value;
            }
        }
    }
}

// Test 5: Headers
$tests['headers'] = [
    'sent' => headers_sent($file, $line),
    'from' => headers_sent() ? "$file:$line" : null,
];

// Handle actions
if ($action === 'login') {
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    
    if ($user) {
        // Clear session
        Auth::logout();
        $session->flush();
        $session->regenerate();
        
        // Login
        Auth::login($user, true);
        $session->save();
        
        // Redirect to test persistence
        header('Location: ?action=check');
        exit;
    }
} elseif ($action === 'logout') {
    Auth::logout();
    $session->flush();
    header('Location: ?');
    exit;
}

// Clear buffer
ob_clean();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Ultimate Session Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .test-section {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .test-section h2 {
            margin-top: 0;
            color: #555;
        }
        .success {
            color: #27ae60;
            font-weight: bold;
        }
        .error {
            color: #e74c3c;
            font-weight: bold;
        }
        .warning {
            color: #f39c12;
            font-weight: bold;
        }
        .info {
            color: #3498db;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            border: 1px solid #e9ecef;
        }
        .actions {
            margin: 20px 0;
            text-align: center;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 10px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .button:hover {
            background: #2980b9;
        }
        .button.success {
            background: #27ae60;
        }
        .button.success:hover {
            background: #229954;
        }
        .button.danger {
            background: #e74c3c;
        }
        .button.danger:hover {
            background: #c0392b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .verdict {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        .verdict h2 {
            margin-top: 0;
            font-size: 24px;
        }
    </style>
</head>
<body>
    <h1>🔬 Ultimate Session Test</h1>
    
    <?php if ($action === 'check'): ?>
        <div class="test-section" style="background: #e8f5e9; border-color: #4caf50;">
            <h2 style="color: #2e7d32;">✅ Login Test Complete</h2>
            <p>You were redirected here after login. If you see your user info below, the session persisted!</p>
        </div>
    <?php endif; ?>
    
    <div class="test-section">
        <h2>🔐 Authentication Status</h2>
        <table>
            <tr>
                <th>Check</th>
                <th>Result</th>
            </tr>
            <tr>
                <td>Auth::check()</td>
                <td class="<?= $tests['auth_status']['check'] ? 'success' : 'error' ?>">
                    <?= $tests['auth_status']['check'] ? 'TRUE ✅' : 'FALSE ❌' ?>
                </td>
            </tr>
            <tr>
                <td>User</td>
                <td><?= $tests['auth_status']['user'] ?: '<span class="error">Not logged in</span>' ?></td>
            </tr>
            <tr>
                <td>User ID</td>
                <td><?= $tests['auth_status']['id'] ?: '-' ?></td>
            </tr>
        </table>
    </div>
    
    <div class="test-section">
        <h2>🍪 Cookie Analysis</h2>
        <table>
            <tr>
                <th>Check</th>
                <th>Result</th>
            </tr>
            <tr>
                <td>Session Cookie Name</td>
                <td><code><?= htmlspecialchars($tests['cookies']['session_cookie_name']) ?></code></td>
            </tr>
            <tr>
                <td>Cookie Present</td>
                <td class="<?= $tests['cookies']['cookie_present'] ? 'success' : 'error' ?>">
                    <?= $tests['cookies']['cookie_present'] ? 'YES ✅' : 'NO ❌' ?>
                </td>
            </tr>
            <tr>
                <td>All Cookies</td>
                <td><?= implode(', ', $tests['cookies']['all_cookies']) ?></td>
            </tr>
        </table>
    </div>
    
    <div class="test-section">
        <h2>📁 Session Storage</h2>
        <table>
            <tr>
                <th>Check</th>
                <th>Result</th>
            </tr>
            <tr>
                <td>Session ID</td>
                <td><code><?= htmlspecialchars($tests['current_session']['id']) ?></code></td>
            </tr>
            <tr>
                <td>Session Started</td>
                <td><?= $tests['current_session']['is_started'] ?></td>
            </tr>
            <tr>
                <td>File Exists</td>
                <td class="<?= $tests['session_file']['exists'] ? 'success' : 'error' ?>">
                    <?= $tests['session_file']['exists'] ? 'YES ✅' : 'NO ❌' ?>
                </td>
            </tr>
            <tr>
                <td>Has Auth Key</td>
                <td class="<?= ($tests['session_file']['has_auth_key'] ?? false) ? 'success' : 'error' ?>">
                    <?= ($tests['session_file']['has_auth_key'] ?? false) ? 'YES ✅' : 'NO ❌' ?>
                </td>
            </tr>
            <?php if ($tests['session_file']['has_auth_key'] ?? false): ?>
            <tr>
                <td>Auth Key</td>
                <td><code><?= htmlspecialchars($tests['session_file']['auth_key']) ?></code></td>
            </tr>
            <tr>
                <td>Auth User ID</td>
                <td><?= $tests['session_file']['auth_user_id'] ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <div class="test-section">
        <h2>📊 Session Data</h2>
        <pre><?= json_encode($tests['current_session']['data'], JSON_PRETTY_PRINT) ?></pre>
    </div>
    
    <?php
    // Determine the verdict
    $verdict = '';
    $verdictClass = '';
    
    if ($tests['auth_status']['check']) {
        $verdict = '✅ Session is working! You are logged in.';
        $verdictClass = 'success';
    } elseif ($tests['cookies']['cookie_present'] && $tests['session_file']['exists'] && ($tests['session_file']['has_auth_key'] ?? false)) {
        $verdict = '⚠️ Session data exists but Auth::check() fails. Possible Guard issue.';
        $verdictClass = 'warning';
    } elseif (!$tests['cookies']['cookie_present']) {
        $verdict = '❌ No session cookie found. Cookies are not being set/sent.';
        $verdictClass = 'error';
    } elseif (!$tests['session_file']['exists']) {
        $verdict = '❌ Session file does not exist. Session not being saved.';
        $verdictClass = 'error';
    } else {
        $verdict = '❌ Session exists but no auth data. Login not persisting.';
        $verdictClass = 'error';
    }
    ?>
    
    <div class="verdict">
        <h2 class="<?= $verdictClass ?>"><?= $verdict ?></h2>
    </div>
    
    <div class="actions">
        <?php if (!$tests['auth_status']['check']): ?>
            <a href="?action=login" class="button success">Test Login</a>
        <?php else: ?>
            <a href="/admin" class="button success">Go to Admin</a>
            <a href="?action=logout" class="button danger">Logout</a>
        <?php endif; ?>
        <a href="?" class="button">Refresh</a>
    </div>
    
    <div class="test-section">
        <h2>ℹ️ Debug Info</h2>
        <p><strong>Headers Sent:</strong> <?= $tests['headers']['sent'] ? 'Yes from ' . $tests['headers']['from'] : 'No' ?></p>
        <p><strong>PHP Session ID:</strong> <?= session_id() ?: 'No PHP session' ?></p>
        <p><strong>Config:</strong></p>
        <pre>
SESSION_DRIVER: <?= config('session.driver') ?>

SESSION_DOMAIN: <?= config('session.domain') ?: '(empty)' ?>

SESSION_SECURE: <?= config('session.secure') ? 'true' : 'false' ?>

SESSION_HTTPONLY: <?= config('session.http_only') ? 'true' : 'false' ?>
        </pre>
    </div>
</body>
</html>
<?php
// Send response
$content = ob_get_contents();
ob_end_clean();
$response->setContent($content);
$response->send();
$kernel->terminate($request, $response);
?>