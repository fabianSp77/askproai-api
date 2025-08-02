<?php
/**
 * Fix Laravel Session Cookie Issue
 * 
 * The askproai_session cookie is not updating properly
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();

// CRITICAL: Process the request properly
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;

$action = $_GET['action'] ?? '';
$message = '';

// Debug current state
$sessionStore = app('session.store');
$sessionId = $sessionStore->getId();
$cookieName = config('session.cookie');
$hasCookie = isset($_COOKIE[$cookieName]);
$cookieValue = $_COOKIE[$cookieName] ?? 'none';

// Decode Laravel session cookie
$decodedCookie = '';
if ($hasCookie) {
    try {
        $payload = app('cookie')->decrypt($cookieValue);
        $decodedCookie = $payload;
    } catch (\Exception $e) {
        $decodedCookie = 'Failed to decrypt: ' . $e->getMessage();
    }
}

if ($action === 'force_new_session') {
    // Force new session
    $sessionStore->flush();
    $sessionStore->regenerate(true);
    $newId = $sessionStore->getId();
    
    // Manually set cookie
    $cookie = cookie(
        $cookieName,
        $newId,
        config('session.lifetime'),
        config('session.path'),
        config('session.domain'),
        config('session.secure'),
        config('session.http_only')
    );
    
    $message = "Forced new session: $newId";
    
    // Add cookie to response
    return redirect('?action=check')->cookie($cookie);
    
} elseif ($action === 'login') {
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    
    if ($user) {
        // Clear old session
        Auth::logout();
        $sessionStore->flush();
        
        // Get new session ID
        $sessionStore->regenerate(true);
        $newSessionId = $sessionStore->getId();
        
        // Login user
        Auth::login($user, true);
        
        // Save session
        $sessionStore->save();
        
        // Create new cookie with proper session ID
        $cookie = cookie(
            $cookieName,
            $newSessionId,
            config('session.lifetime'),
            config('session.path'),
            config('session.domain'),
            config('session.secure'),
            config('session.http_only')
        );
        
        // Redirect with new cookie
        return redirect('?action=check')->cookie($cookie);
    }
} elseif ($action === 'check') {
    $message = Auth::check() ? 
        '✅ Login successful! User: ' . Auth::user()->email : 
        '❌ Not logged in after redirect';
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Laravel Session Cookie</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f0f0f0;
        }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 5px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .button:hover {
            background: #0056b3;
        }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <h1>Fix Laravel Session Cookie Issue</h1>
    
    <?php if ($message): ?>
        <div class="box">
            <h2><?= htmlspecialchars($message) ?></h2>
        </div>
    <?php endif; ?>
    
    <div class="box">
        <h2>Current Session State</h2>
        <table>
            <tr>
                <th>Property</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Session ID (Server)</td>
                <td><code><?= htmlspecialchars($sessionId) ?></code></td>
            </tr>
            <tr>
                <td>Cookie Name</td>
                <td><code><?= htmlspecialchars($cookieName) ?></code></td>
            </tr>
            <tr>
                <td>Has Cookie</td>
                <td class="<?= $hasCookie ? 'success' : 'error' ?>"><?= $hasCookie ? 'YES' : 'NO' ?></td>
            </tr>
            <tr>
                <td>Cookie Value (Encrypted)</td>
                <td><code><?= htmlspecialchars(substr($cookieValue, 0, 50)) ?>...</code></td>
            </tr>
            <tr>
                <td>Cookie Value (Decrypted)</td>
                <td><code><?= htmlspecialchars($decodedCookie) ?></code></td>
            </tr>
            <tr>
                <td>Auth::check()</td>
                <td class="<?= Auth::check() ? 'success' : 'error' ?>"><?= Auth::check() ? 'TRUE' : 'FALSE' ?></td>
            </tr>
            <?php if (Auth::check()): ?>
            <tr>
                <td>User</td>
                <td><?= Auth::user()->email ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <div class="box">
        <h2>Cookie Mismatch Analysis</h2>
        <?php
        $cookieMatchesSession = false;
        if ($hasCookie && $decodedCookie && !str_contains($decodedCookie, 'Failed')) {
            $cookieMatchesSession = ($decodedCookie === $sessionId);
        }
        ?>
        
        <?php if (!$cookieMatchesSession): ?>
            <p class="error">❌ Cookie session ID does not match server session ID!</p>
            <p>This is why the session is not persisting. The browser sends an old/wrong session ID.</p>
        <?php else: ?>
            <p class="success">✅ Cookie matches server session ID</p>
        <?php endif; ?>
    </div>
    
    <div class="box">
        <h2>All Cookies</h2>
        <pre><?= print_r($_COOKIE, true) ?></pre>
    </div>
    
    <div class="box">
        <h2>Session Data</h2>
        <pre><?= print_r($sessionStore->all(), true) ?></pre>
    </div>
    
    <div class="box">
        <h2>Actions</h2>
        <a href="?action=force_new_session" class="button">Force New Session</a>
        <a href="?action=login" class="button">Test Login with Cookie Fix</a>
        <a href="?" class="button">Refresh</a>
        <?php if (Auth::check()): ?>
            <a href="/admin" class="button" style="background: green;">Go to Admin</a>
        <?php endif; ?>
    </div>
    
    <div class="box">
        <h2>Configuration</h2>
        <pre>
SESSION_DRIVER: <?= config('session.driver') ?>

SESSION_LIFETIME: <?= config('session.lifetime') ?> minutes
SESSION_PATH: <?= config('session.path') ?>

SESSION_DOMAIN: <?= config('session.domain') ?: '(empty)' ?>

SESSION_SECURE: <?= config('session.secure') ? 'true' : 'false' ?>

SESSION_HTTPONLY: <?= config('session.http_only') ? 'true' : 'false' ?>

SESSION_SAMESITE: <?= config('session.same_site') ?>
        </pre>
    </div>
</body>
</html>
<?php
// Send the response
$response->setContent(ob_get_contents());
ob_end_clean();
echo $response->getContent();
?>