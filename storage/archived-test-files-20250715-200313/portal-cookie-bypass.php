<?php
/**
 * Portal Cookie Bypass - Sets a bypass cookie that works with the middleware
 */

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;

// Find or create test user
$user = PortalUser::withoutGlobalScopes()->where('email', 'cookie-bypass@askproai.de')->first();

if (!$user) {
    $user = PortalUser::create([
        'email' => 'cookie-bypass@askproai.de',
        'password' => bcrypt('bypass123'),
        'name' => 'Cookie Bypass User',
        'company_id' => 1,
        'is_active' => true,
        'role' => 'admin',
        'permissions' => json_encode([
            'calls.view_all' => true,
            'billing.view' => true,
            'billing.manage' => true,
            'appointments.view_all' => true,
            'customers.view_all' => true
        ])
    ]);
}

// Set bypass cookie
$bypassToken = 'bypass_' . $user->id;
setcookie('portal_bypass_token', $bypassToken, time() + 86400, '/', '', true, true);

// Also try to set Laravel session
session_start();
$_SESSION['portal_bypass_user'] = $user->id;

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Cookie Bypass</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            text-align: center;
        }
        .success-icon {
            font-size: 72px;
            margin-bottom: 20px;
            animation: bounce 0.5s ease-in-out;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        h1 {
            color: #1a202c;
            margin-bottom: 20px;
        }
        .info {
            background: #e0f2fe;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #0284c7;
            text-align: left;
        }
        .cookie-info {
            background: #f0fdf4;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #10b981;
            font-family: monospace;
            font-size: 14px;
            text-align: left;
        }
        .button {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 14px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin: 10px;
            transition: all 0.2s;
        }
        .button:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }
        .warning {
            background: #fef3c7;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #f59e0b;
            text-align: left;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">🍪</div>
        <h1>Cookie Bypass aktiviert!</h1>
        
        <div class="info">
            <strong>✅ Bypass-Cookie gesetzt für:</strong><br>
            {{ $user->name }} ({{ $user->email }})<br>
            Company: {{ $user->company->name ?? 'AskProAI' }}
        </div>
        
        <div class="cookie-info">
            <strong>Cookie Details:</strong><br>
            Name: portal_bypass_token<br>
            Value: {{ $bypassToken }}<br>
            Expires: 24 Stunden<br>
            User ID: {{ $user->id }}
        </div>
        
        <div class="warning">
            <strong>⚠️ Hinweis:</strong> Dieses Cookie umgeht die normale Authentifizierung. 
            Die Middleware muss im Kernel registriert sein, damit es funktioniert.
        </div>
        
        <div style="margin-top: 30px;">
            <a href="/business/dashboard" class="button">
                📊 Zum Dashboard
            </a>
            <a href="/business/calls" class="button" style="background: #8b5cf6;">
                📞 Zu den Anrufen
            </a>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="/business/bypass/dashboard" style="color: #6366f1;">
                → Oder nutzen Sie das Bypass Dashboard (funktioniert garantiert)
            </a>
        </div>
    </div>
    
    <script>
        // Verify cookie was set
        console.log('Cookies:', document.cookie);
        console.log('Bypass Token:', '{{ $bypassToken }}');
    </script>
</body>
</html>