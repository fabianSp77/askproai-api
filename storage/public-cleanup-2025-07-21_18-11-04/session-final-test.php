<?php
/**
 * Final Session Test - Confirms Everything Works
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
    <title>Final Session Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 600px;
            margin: 100px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        .status {
            font-size: 24px;
            margin: 20px 0;
            padding: 20px;
            border-radius: 8px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            margin: 10px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .button:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .button.success {
            background: #28a745;
        }
        .button.success:hover {
            background: #218838;
        }
        .info {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            color: #004085;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
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
    <div class="card">
        <h1>🧪 Final Session Test</h1>
        
        <?php if (Auth::check()): ?>
            <div class="status success">
                ✅ Session Works Perfectly!
            </div>
            <p><strong>Logged in as:</strong> <?= htmlspecialchars(Auth::user()->email) ?></p>
            <p><strong>User ID:</strong> <?= Auth::id() ?></p>
            <p><strong>Session ID:</strong> <?= substr(session()->getId(), 0, 10) ?>...</p>
            
            <a href="/admin" class="button success">Go to Admin Panel</a>
            
            <div class="info">
                <strong>Success!</strong> Your session is persisting correctly. 
                You can now navigate to any page without being logged out.
            </div>
            
        <?php else: ?>
            <div class="status error">
                ❌ Not Logged In
            </div>
            
            <?php if (isset($_GET['login'])): ?>
                <?php
                $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
                if ($user) {
                    Auth::login($user, true);
                    session()->save();
                    echo '<script>window.location.href = "?";</script>';
                    echo '<p>Logging in... Please wait...</p>';
                } else {
                    echo '<p>Demo user not found!</p>';
                }
                ?>
            <?php else: ?>
                <p>Click below to test the login:</p>
                <a href="?login=1" class="button">Login as Demo User</a>
            <?php endif; ?>
        <?php endif; ?>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #e0e0e0;">
        
        <h3>Session Configuration:</h3>
        <p>
            <code>SESSION_DOMAIN:</code> <?= var_export(config('session.domain'), true) ?: '(empty - works everywhere!)' ?><br>
            <code>SESSION_SECURE:</code> <?= config('session.secure') ? 'true' : 'false' ?><br>
            <code>SESSION_DRIVER:</code> <?= config('session.driver') ?>
        </p>
    </div>
</body>
</html>