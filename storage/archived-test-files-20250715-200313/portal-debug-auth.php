<?php
// Portal Auth Debug - Understand the authentication issue

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\PortalUser;

// Start session
session_start();

$debug = [
    'session_driver' => config('session.driver'),
    'session_cookie' => config('session.cookie'),
    'session_domain' => config('session.domain'),
    'session_path' => config('session.path'),
    'session_same_site' => config('session.same_site'),
    'session_secure' => config('session.secure'),
    'auth_guards' => array_keys(config('auth.guards')),
    'portal_guard_driver' => config('auth.guards.portal.driver'),
    'portal_provider' => config('auth.guards.portal.provider'),
    'session_id' => session()->getId(),
    'session_all' => session()->all(),
    'portal_check' => Auth::guard('portal')->check(),
    'portal_user' => Auth::guard('portal')->user(),
    'cookies' => $_COOKIE,
    'session_files' => [],
    'php_session' => $_SESSION ?? []
];

// Check session files if using file driver
if (config('session.driver') === 'file') {
    $sessionPath = storage_path('framework/sessions');
    if (is_dir($sessionPath)) {
        $files = scandir($sessionPath);
        $debug['session_files'] = array_slice($files, 0, 10); // First 10 files
        $debug['session_path_exists'] = true;
        $debug['session_path_writable'] = is_writable($sessionPath);
    }
}

// Try to manually login a user
if (isset($_GET['login'])) {
    $user = PortalUser::withoutGlobalScopes()->where('email', 'demo-user@askproai.de')->first();
    if ($user) {
        Auth::guard('portal')->login($user);
        $debug['manual_login_attempt'] = true;
        $debug['after_login_check'] = Auth::guard('portal')->check();
        $debug['after_login_user'] = Auth::guard('portal')->user() ? Auth::guard('portal')->user()->toArray() : null;
        $debug['after_login_session'] = session()->all();
    }
}

// Format debug output
?>
<!DOCTYPE html>
<html>
<head>
    <title>Portal Auth Debug</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #0f0; padding: 20px; }
        pre { background: #000; padding: 20px; border: 1px solid #0f0; overflow: auto; }
        h2 { color: #0ff; }
        .error { color: #f00; }
        .success { color: #0f0; }
        .warning { color: #ff0; }
        a { color: #0ff; }
    </style>
</head>
<body>
    <h1>🔍 Portal Authentication Debug</h1>
    
    <h2>Configuration</h2>
    <pre><?= json_encode([
        'session_driver' => $debug['session_driver'],
        'session_cookie' => $debug['session_cookie'],
        'session_domain' => $debug['session_domain'],
        'session_path' => $debug['session_path'],
        'session_same_site' => $debug['session_same_site'],
        'session_secure' => $debug['session_secure']
    ], JSON_PRETTY_PRINT) ?></pre>
    
    <h2>Auth Status</h2>
    <pre><?= json_encode([
        'portal_authenticated' => $debug['portal_check'],
        'portal_user' => $debug['portal_user'],
        'session_id' => $debug['session_id']
    ], JSON_PRETTY_PRINT) ?></pre>
    
    <h2>Session Data</h2>
    <pre><?= json_encode($debug['session_all'], JSON_PRETTY_PRINT) ?></pre>
    
    <h2>Cookies</h2>
    <pre><?= json_encode($debug['cookies'], JSON_PRETTY_PRINT) ?></pre>
    
    <?php if (!isset($_GET['login'])): ?>
        <h2>Actions</h2>
        <p><a href="?login=1">→ Try Manual Login</a></p>
    <?php else: ?>
        <h2 class="success">Login Attempt Results</h2>
        <pre><?= json_encode([
            'login_attempted' => true,
            'authenticated_after' => $debug['after_login_check'],
            'user_after' => $debug['after_login_user'],
            'session_after' => $debug['after_login_session']
        ], JSON_PRETTY_PRINT) ?></pre>
        
        <?php if ($debug['after_login_check']): ?>
            <h2 class="success">✅ Login Successful!</h2>
            <p>Now try accessing: <a href="/business/dashboard">Dashboard</a> | <a href="/business/calls">Calls</a></p>
        <?php else: ?>
            <h2 class="error">❌ Login Failed</h2>
        <?php endif; ?>
    <?php endif; ?>
    
    <h2>Diagnosis</h2>
    <?php
    $issues = [];
    if ($debug['session_driver'] !== 'file' && $debug['session_driver'] !== 'database') {
        $issues[] = "Unexpected session driver: " . $debug['session_driver'];
    }
    if (empty($debug['session_domain'])) {
        $issues[] = "Session domain not set - cookies might not persist";
    }
    if ($debug['session_same_site'] === 'strict') {
        $issues[] = "Session same_site is 'strict' - might cause issues with redirects";
    }
    if (!isset($_COOKIE[$debug['session_cookie']])) {
        $issues[] = "Session cookie not found in browser";
    }
    ?>
    
    <?php if (empty($issues)): ?>
        <p class="success">✅ No obvious configuration issues found</p>
    <?php else: ?>
        <p class="warning">⚠️ Potential issues found:</p>
        <ul>
            <?php foreach ($issues as $issue): ?>
                <li class="warning"><?= htmlspecialchars($issue) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    
    <hr>
    <p><a href="/portal-working-access.php">← Back to Portal Access</a></p>
</body>
</html>