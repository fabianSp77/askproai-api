<?php
/**
 * ULTRATHINK: Final Solution Test
 * 
 * This combines all fixes and tests if session finally works
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Auth;

?>
<!DOCTYPE html>
<html>
<head>
    <title>ULTRATHINK: Final Solution</title>
    <style>
        body {
            font-family: -apple-system, system-ui, sans-serif;
            background: #1a1a1a;
            color: #e0e0e0;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            color: #fff;
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 30px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }
        .status-card {
            background: #2a2a2a;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .success {
            border-left: 4px solid #4caf50;
        }
        .error {
            border-left: 4px solid #f44336;
        }
        .info {
            border-left: 4px solid #2196f3;
        }
        .warning {
            border-left: 4px solid #ff9800;
        }
        .status-icon {
            font-size: 3em;
            text-align: center;
            margin-bottom: 20px;
        }
        .details {
            background: #1a1a1a;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            margin: 10px;
            background: #2196f3;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        .button:hover {
            background: #1976d2;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(33,150,243,0.4);
        }
        .button.success {
            background: #4caf50;
        }
        .button.success:hover {
            background: #45a049;
        }
        code {
            background: #333;
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
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #333;
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
        <h1>🧠 ULTRATHINK: Final Solution</h1>
        
        <?php
        $session = app('session.store');
        $isLoggedIn = Auth::check();
        ?>
        
        <?php if ($isLoggedIn): ?>
            <div class="status-card success">
                <div class="status-icon">✅</div>
                <h2>Session Working!</h2>
                <p>You are successfully logged in as: <strong><?= htmlspecialchars(Auth::user()->email) ?></strong></p>
                <p>User ID: <?= Auth::id() ?></p>
                <p>Session ID: <?= substr($session->getId(), 0, 20) ?>...</p>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="/admin" class="button success">Go to Admin Panel</a>
                </div>
            </div>
        <?php else: ?>
            <div class="status-card error">
                <div class="status-icon">❌</div>
                <h2>Not Logged In</h2>
                <p>Session is not persisting. Let's fix this!</p>
            </div>
        <?php endif; ?>
        
        <div class="status-card info">
            <h2>📊 System Analysis</h2>
            
            <table>
                <tr>
                    <th>Check</th>
                    <th>Status</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>Session Cookie Present</td>
                    <td><?= isset($_COOKIE[config('session.cookie')]) ? '<span class="check">✓</span>' : '<span class="cross">✗</span>' ?></td>
                    <td><?= isset($_COOKIE[config('session.cookie')]) ? 'Yes' : 'No' ?></td>
                </tr>
                <tr>
                    <td>Session File Exists</td>
                    <td><?= file_exists(storage_path('framework/sessions/' . $session->getId())) ? '<span class="check">✓</span>' : '<span class="cross">✗</span>' ?></td>
                    <td><?= $session->getId() ?></td>
                </tr>
                <tr>
                    <td>SESSION_DOMAIN</td>
                    <td><span class="check">✓</span></td>
                    <td><?= config('session.domain') ?: '(empty - good!)' ?></td>
                </tr>
                <tr>
                    <td>SESSION_SECURE_COOKIE</td>
                    <td><?= !config('session.secure') ? '<span class="check">✓</span>' : '<span class="cross">✗</span>' ?></td>
                    <td><?= config('session.secure') ? 'true' : 'false' ?></td>
                </tr>
                <tr>
                    <td>Request Secure (HTTPS)</td>
                    <td><?= request()->isSecure() ? '<span class="check">✓</span>' : '<span class="cross">✗</span>' ?></td>
                    <td><?= request()->isSecure() ? 'Yes' : 'No' ?></td>
                </tr>
                <tr>
                    <td>Headers Sent</td>
                    <td><?= !headers_sent() ? '<span class="check">✓</span>' : '<span class="cross">✗</span>' ?></td>
                    <td><?= headers_sent($file, $line) ? "From $file:$line" : 'No' ?></td>
                </tr>
                <tr>
                    <td>CleanDuplicateSessionKeys</td>
                    <td>
                        <?php
                        $hasMiddleware = false;
                        $kernel = app(\App\Http\Kernel::class);
                        $reflection = new ReflectionClass($kernel);
                        $prop = $reflection->getProperty('middlewareGroups');
                        $prop->setAccessible(true);
                        $groups = $prop->getValue($kernel);
                        foreach ($groups['web'] ?? [] as $mw) {
                            if (strpos($mw, 'CleanDuplicateSessionKeys') !== false) {
                                $hasMiddleware = true;
                                break;
                            }
                        }
                        ?>
                        <?= $hasMiddleware ? '<span class="check">✓</span>' : '<span class="cross">✗</span>' ?>
                    </td>
                    <td><?= $hasMiddleware ? 'Active' : 'Not Found' ?></td>
                </tr>
            </table>
            
            <div class="details">
                <strong>Cookie Parameters:</strong><br>
                <?php $params = session_get_cookie_params(); ?>
                Lifetime: <?= $params['lifetime'] ?><br>
                Path: <?= $params['path'] ?><br>
                Domain: <?= $params['domain'] ?: '(not set)' ?><br>
                Secure: <?= $params['secure'] ? 'true' : 'false' ?><br>
                HttpOnly: <?= $params['httponly'] ? 'true' : 'false' ?><br>
                SameSite: <?= $params['samesite'] ?: 'none' ?>
            </div>
        </div>
        
        <?php if (!$isLoggedIn): ?>
            <div class="status-card warning">
                <h2>🔧 Solution</h2>
                <p>Based on the analysis above, here's what needs to be fixed:</p>
                
                <?php
                $issues = [];
                if (!isset($_COOKIE[config('session.cookie')])) {
                    $issues[] = "No session cookie found - browser is not sending/receiving cookies";
                }
                if (config('session.secure') && !request()->isSecure()) {
                    $issues[] = "SESSION_SECURE_COOKIE is true but request is not HTTPS";
                }
                if (config('session.domain')) {
                    $issues[] = "SESSION_DOMAIN is set - should be empty for flexibility";
                }
                ?>
                
                <?php if (!empty($issues)): ?>
                    <ul>
                        <?php foreach ($issues as $issue): ?>
                            <li><?= htmlspecialchars($issue) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="?action=login" class="button">Test Login Now</a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php
        if (isset($_GET['action']) && $_GET['action'] === 'login') {
            $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
            
            if ($user) {
                // Clear everything
                Auth::logout();
                $session->flush();
                $session->regenerate();
                
                // Login
                Auth::login($user, true);
                
                // Force save
                $session->save();
                
                // Redirect to see if it persists
                echo '<script>window.location.href = "?";</script>';
                echo '<div class="status-card info"><p>Logging in... Please wait...</p></div>';
            }
        }
        ?>
        
        <div class="status-card info">
            <h2>📝 Final Configuration</h2>
            <p>Your <code>.env</code> should have:</p>
            <div class="details">
SESSION_DOMAIN=<br>
SESSION_SECURE_COOKIE=false<br>
SESSION_HTTP_ONLY=true<br>
SESSION_SAME_SITE=lax<br>
SESSION_COOKIE=askproai_session
            </div>
            <p style="margin-top: 20px;">Then run: <code>php artisan config:cache</code></p>
        </div>
    </div>
</body>
</html>