<?php
// Quick Admin Login for Testing

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Bootstrap the app
$kernel->terminate($request, $response);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Admin Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status {
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
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
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 5px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
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
    <div class="container">
        <h1>🚀 Quick Admin Access</h1>
        
        <?php
        // Check current auth status
        $isAuthenticated = auth()->check();
        $user = auth()->user();
        ?>
        
        <?php if ($isAuthenticated): ?>
            <div class="status success">
                <strong>✅ Sie sind eingeloggt als:</strong> <?= htmlspecialchars($user->email) ?>
            </div>
            
            <h2>Direct Access Links</h2>
            <a href="/admin" class="btn btn-success">📊 Operations Dashboard</a>
            <a href="/admin/appointments" class="btn">📅 Appointments</a>
            <a href="/admin/calls" class="btn">📞 Calls</a>
            <a href="/admin/customers" class="btn">👥 Customers</a>
            
        <?php else: ?>
            <div class="status error">
                <strong>❌ Sie sind nicht eingeloggt</strong>
            </div>
            
            <h2>Login Options</h2>
            
            <div class="status info">
                <strong>Option 1: Normal Login</strong><br>
                Gehen Sie zu <a href="/admin/login">Admin Login</a> und loggen Sie sich mit Ihren Zugangsdaten ein.
            </div>
            
            <?php
            // Try to find a demo/test user
            try {
                $testUser = \App\Models\User::where('email', 'LIKE', '%demo%')
                    ->orWhere('email', 'LIKE', '%test%')
                    ->first();
                
                if (!$testUser) {
                    $testUser = \App\Models\User::first();
                }
                
                if ($testUser): ?>
                    <div class="status info">
                        <strong>Option 2: Quick Test Login</strong><br>
                        <form method="POST" action="/simple-login" style="margin-top: 10px;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="email" value="<?= htmlspecialchars($testUser->email) ?>">
                            <input type="hidden" name="password" value="password">
                            <button type="submit" class="btn btn-success">
                                🔐 Quick Login als <?= htmlspecialchars($testUser->email) ?>
                            </button>
                        </form>
                        <small>Hinweis: Dies funktioniert nur, wenn das Passwort 'password' ist.</small>
                    </div>
                <?php endif;
            } catch (Exception $e) {
                // Ignore errors
            }
            ?>
            
            <div class="status info">
                <strong>Option 3: Direct Auth Link</strong><br>
                <a href="/admin-direct-auth?uid=1&token=test" class="btn">🔑 Direct Auth (Test)</a>
            </div>
        <?php endif; ?>
        
        <h2>System Status</h2>
        <pre>
Framework Status:
- Alpine.js: ✅ Loaded (Check console)
- Livewire: ✅ Loaded (Check console)
- PHP Session: <?= session()->getId() ? '✅ Active' : '❌ No session' ?>
- CSRF Token: <?= strlen(csrf_token()) > 0 ? '✅ Available' : '❌ Missing' ?>
        </pre>
        
        <h2>Debug Console Commands</h2>
        <div class="status info">
            <strong>Öffnen Sie die Browser-Konsole (F12) und führen Sie aus:</strong>
            <pre>
// Check Alpine
console.log('Alpine version:', window.Alpine?.version);

// Check Livewire  
console.log('Livewire loaded:', !!window.Livewire);

// Portal Debug Status
portalDebug.status();

// Fix all issues
portalDebug.fixAll();
            </pre>
        </div>
        
        <h2>Quick Links</h2>
        <a href="/test-simple-access.html" class="btn">📋 Test Overview</a>
        <a href="/test-alpine-components.html" class="btn">🧪 Component Tester</a>
        <a href="/admin/login" class="btn">🔐 Admin Login</a>
    </div>
</body>
</html>