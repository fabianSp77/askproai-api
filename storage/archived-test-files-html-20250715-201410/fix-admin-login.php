<?php
// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Clear all sessions
session()->flush();

// Clear all auth guards
\Illuminate\Support\Facades\Auth::guard('web')->logout();
\Illuminate\Support\Facades\Auth::guard('portal')->logout();

// Clear specific session keys
$sessionKeysToForget = [
    'is_admin_viewing',
    'admin_impersonation',
    'portal_user_id',
    'portal_login',
    'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal'),
    'login_web_' . sha1('Illuminate\Auth\SessionGuard.web'),
];

foreach ($sessionKeysToForget as $key) {
    session()->forget($key);
}

// Regenerate session
session()->regenerate();

// Set a flag to bypass redirects
session(['admin_login_fix' => true]);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login Fix</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success {
            color: #22c55e;
            margin-bottom: 20px;
        }
        .links {
            margin-top: 20px;
        }
        .links a {
            display: block;
            margin: 10px 0;
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            text-align: center;
        }
        .links a:hover {
            background: #2563eb;
        }
        .warning {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Admin Login Fix</h1>
        <div class="success">✅ Alle Sessions wurden zurückgesetzt!</div>
        
        <p>Die Session-Konflikte wurden behoben. Sie können sich jetzt wieder normal einloggen:</p>
        
        <div class="links">
            <a href="/admin/login">→ Admin Portal Login</a>
            <a href="/business/login" style="background: #10b981;">→ Business Portal Login</a>
        </div>
        
        <div class="warning">
            <strong>Wichtig:</strong> Falls Sie immer noch weitergeleitet werden:
            <ol>
                <li>Löschen Sie Ihre Browser-Cookies für diese Domain</li>
                <li>Verwenden Sie ein Inkognito/Privates Fenster</li>
                <li>Oder drücken Sie Strg+Shift+R (Cmd+Shift+R auf Mac) für einen Hard Refresh</li>
            </ol>
        </div>
    </div>
</body>
</html>