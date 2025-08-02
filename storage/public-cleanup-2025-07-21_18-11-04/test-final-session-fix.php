<?php
/**
 * Test Final Session Fix
 * 
 * This tests if the new middleware fixes the cookie issue
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;

$action = $_GET['action'] ?? '';
$step = $_GET['step'] ?? '';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Final Session Fix</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #1a1a1a;
            color: #e0e0e0;
        }
        .container {
            background: #2a2a2a;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }
        h1 {
            color: #4fc3f7;
            text-align: center;
            margin-bottom: 40px;
        }
        .status-box {
            background: #333;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #666;
        }
        .status-box.success {
            border-color: #4caf50;
        }
        .status-box.error {
            border-color: #f44336;
        }
        .status-box.info {
            border-color: #2196f3;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            margin: 10px 5px;
            background: #2196f3;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s;
            text-align: center;
        }
        .button:hover {
            background: #1976d2;
            transform: translateY(-2px);
        }
        .button.success {
            background: #4caf50;
        }
        .button.success:hover {
            background: #45a049;
        }
        .button.danger {
            background: #f44336;
        }
        code {
            background: #444;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #444;
        }
        th {
            background: #333;
            font-weight: bold;
        }
        .check { color: #4caf50; }
        .cross { color: #f44336; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Test Final Session Fix</h1>
        
        <?php if ($action === 'login'): ?>
            <?php
            $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
            if ($user) {
                Auth::logout();
                session()->flush();
                session()->regenerate();
                
                Auth::login($user, true);
                session()->save();
                ?>
                <div class="status-box success">
                    <h2>✅ Login Successful!</h2>
                    <p>Logged in as: <?= htmlspecialchars($user->email) ?></p>
                    <p>Now redirecting to test persistence...</p>
                </div>
                <script>
                    setTimeout(() => {
                        window.location.href = '?step=check';
                    }, 1500);
                </script>
                <?php
            }
            ?>
        <?php elseif ($step === 'check'): ?>
            <div class="status-box <?= Auth::check() ? 'success' : 'error' ?>">
                <h2><?= Auth::check() ? '✅ Session Persisted!' : '❌ Session Lost!' ?></h2>
                <?php if (Auth::check()): ?>
                    <p>Great! The session survived the redirect.</p>
                    <p>User: <?= htmlspecialchars(Auth::user()->email) ?></p>
                <?php else: ?>
                    <p>The session was lost after redirect.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="status-box info">
                <h2>Session Fix Test</h2>
                <p>This tests if the new <code>EnsureSessionCookieResponse</code> middleware fixes the cookie issue.</p>
            </div>
        <?php endif; ?>
        
        <div class="status-box">
            <h3>Current Status</h3>
            <table>
                <tr>
                    <td>Auth::check()</td>
                    <td><?= Auth::check() ? '<span class="check">TRUE ✓</span>' : '<span class="cross">FALSE ✗</span>' ?></td>
                </tr>
                <tr>
                    <td>Session ID</td>
                    <td><code><?= session()->getId() ?></code></td>
                </tr>
                <tr>
                    <td>Cookie Name</td>
                    <td><code><?= config('session.cookie') ?></code></td>
                </tr>
                <tr>
                    <td>Cookie Present</td>
                    <td><?= isset($_COOKIE[config('session.cookie')]) ? '<span class="check">YES ✓</span>' : '<span class="cross">NO ✗</span>' ?></td>
                </tr>
            </table>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <?php if (!Auth::check()): ?>
                <a href="?action=login" class="button">Test Login</a>
            <?php else: ?>
                <a href="/admin" class="button success">Go to Admin</a>
                <a href="?action=logout" class="button danger">Logout</a>
            <?php endif; ?>
            <a href="?" class="button">Reset</a>
        </div>
        
        <?php if ($action === 'logout'): ?>
            <?php
            Auth::logout();
            session()->flush();
            ?>
            <script>window.location.href = '?';</script>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
$response->setContent(ob_get_contents());
ob_end_clean();
$response->send();
$kernel->terminate($request, $response);
?>