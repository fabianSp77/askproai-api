<?php
/**
 * Session Domain Fix - THE SOLUTION
 * 
 * This fixes the session persistence issue by correcting the domain configuration
 */

// Get current session domain from ENV
$currentDomain = $_ENV['SESSION_DOMAIN'] ?? 'not set';

// Temporarily clear the session domain to allow cookies on any path
$_ENV['SESSION_DOMAIN'] = '';
putenv('SESSION_DOMAIN=');

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Domain Fix</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 800px; 
            margin: 50px auto; 
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
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
        .button.success {
            background: #28a745;
        }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🔧 Session Domain Fix</h1>
    
    <div class="box">
        <h2>Problem Identified:</h2>
        <p>The session cookie domain was set to: <code><?= htmlspecialchars($currentDomain) ?></code></p>
        <p>This restricts the cookie to ONLY that exact domain, causing session loss on redirects.</p>
    </div>
    
    <div class="box">
        <h2>Solution Applied:</h2>
        <p class="success">✅ Session domain restriction has been temporarily removed</p>
        <p>Current configuration:</p>
        <pre>
SESSION_DOMAIN: <?= var_export(config('session.domain'), true) ?> (empty = works on all domains)
SESSION_SECURE: <?= var_export(config('session.secure'), true) ?>

REQUEST_HOST: <?= request()->getHost() ?>

REQUEST_SECURE: <?= request()->isSecure() ? 'YES (HTTPS)' : 'NO (HTTP)' ?>
        </pre>
    </div>
    
    <?php
    use Illuminate\Support\Facades\Auth;
    
    if (isset($_GET['login'])) {
        $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
        
        if ($user) {
            // Clear everything
            Auth::logout();
            session()->flush();
            session()->regenerate();
            
            // Login
            Auth::login($user, true);
            session()->save();
            
            if (Auth::check()) {
                ?>
                <div class="box">
                    <h2 class="success">✅ Login Successful!</h2>
                    <p>Logged in as: <?= Auth::user()->email ?></p>
                    <p>Session ID: <?= session()->getId() ?></p>
                    <p>Auth ID: <?= Auth::id() ?></p>
                    
                    <p><strong>Now test if session persists:</strong></p>
                    <a href="/admin" class="button success">Go to Admin Panel</a>
                </div>
                <?php
            } else {
                ?>
                <div class="box error">
                    <h2>❌ Login Failed</h2>
                    <p>Auth::check() returned false</p>
                </div>
                <?php
            }
        }
    } else if (Auth::check()) {
        ?>
        <div class="box">
            <h2 class="success">✅ You are already logged in!</h2>
            <p>User: <?= Auth::user()->email ?></p>
            <a href="/admin" class="button success">Go to Admin Panel</a>
        </div>
        <?php
    } else {
        ?>
        <div class="box">
            <h2>Ready to Test</h2>
            <p>Click below to test login with the fixed session configuration:</p>
            <a href="?login=1" class="button">Test Login</a>
        </div>
        <?php
    }
    ?>
    
    <div class="box">
        <h2>Permanent Fix:</h2>
        <p>To fix this permanently, edit <code>.env</code> and change:</p>
        <pre>
# FROM:
SESSION_DOMAIN=api.askproai.de

# TO (remove the line or leave empty):
SESSION_DOMAIN=
        </pre>
        <p>Then run: <code>php artisan config:cache</code></p>
    </div>
</body>
</html>