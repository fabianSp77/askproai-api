<?php
/**
 * Portal Session Fix - Create a working session properly
 */

// Start native PHP session first
session_start();

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;

// Find or create test user
$user = PortalUser::withoutGlobalScopes()->where('email', 'session-test@askproai.de')->first();

if (!$user) {
    $user = PortalUser::create([
        'email' => 'session-test@askproai.de',
        'password' => bcrypt('test123'),
        'name' => 'Session Test User',
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

// Start Laravel session properly
Session::start();

// Login the user
Auth::guard('portal')->loginUsingId($user->id);

// Force session save
Session::put('portal_user_id', $user->id);
Session::put('portal_authenticated', true);
Session::save();

// Get the session ID that Laravel created
$sessionId = Session::getId();

// Debug info
$debug = [
    'session_id' => $sessionId,
    'session_driver' => config('session.driver'),
    'session_file_path' => storage_path('framework/sessions/' . $sessionId),
    'session_exists' => file_exists(storage_path('framework/sessions/' . $sessionId)),
    'auth_check' => Auth::guard('portal')->check(),
    'user' => Auth::guard('portal')->user() ? Auth::guard('portal')->user()->toArray() : null,
    'session_data' => Session::all(),
    'cookies_will_be_set' => [
        config('session.cookie') => $sessionId,
        'XSRF-TOKEN' => Session::token()
    ]
];

// Create a test page that checks auth
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Session Fix</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #1a1a1a;
            color: #e2e8f0;
            padding: 40px;
            margin: 0;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #2d3748;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }
        h1 { color: #10b981; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
        .info-box {
            background: #1a202c;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #3b82f6;
        }
        .debug {
            background: #000;
            color: #0f0;
            padding: 20px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 14px;
            overflow: auto;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            margin: 10px 10px 10px 0;
            transition: all 0.2s;
        }
        .button:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }
        .test-section {
            background: #374151;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Portal Session Fix</h1>
        
        @if($debug['auth_check'])
            <div class="info-box">
                <h2 class="success">✅ Session erfolgreich erstellt!</h2>
                <p><strong>User:</strong> {{ $user->name }} ({{ $user->email }})</p>
                <p><strong>Session ID:</strong> {{ $sessionId }}</p>
                <p><strong>Session File:</strong> {{ $debug['session_exists'] ? 'EXISTS' : 'NOT FOUND' }}</p>
            </div>
        @else
            <div class="info-box">
                <h2 class="error">❌ Session-Erstellung fehlgeschlagen</h2>
            </div>
        @endif
        
        <div class="debug">
            <h3>Debug Information:</h3>
            <pre>{{ json_encode($debug, JSON_PRETTY_PRINT) }}</pre>
        </div>
        
        <div class="test-section">
            <h2>Test Links:</h2>
            <p>Diese Links testen, ob die Session funktioniert:</p>
            
            <a href="/business/dashboard" class="button" target="_blank">
                📊 Dashboard (neues Fenster)
            </a>
            
            <a href="/business/calls" class="button" target="_blank">
                📞 Anrufliste (neues Fenster)
            </a>
            
            <a href="/portal-test-auth.php" class="button">
                🔍 Auth Status prüfen
            </a>
        </div>
        
        <div class="test-section">
            <h2>Inline Test:</h2>
            <p>Test ob Session in iframe funktioniert:</p>
            <iframe src="/portal-test-auth.php" style="width: 100%; height: 400px; border: 1px solid #4b5563; border-radius: 8px;"></iframe>
        </div>
        
        <div class="warning" style="margin-top: 30px;">
            <h3>⚠️ Wichtig:</h3>
            <p>Die Session wurde mit Laravel's Session-System erstellt. Wenn die Links immer noch zur Login-Seite weiterleiten, liegt ein tieferes Problem vor.</p>
            <p>Alternative: <a href="/business/bypass/dashboard" style="color: #f59e0b;">Bypass Dashboard verwenden</a></p>
        </div>
    </div>
</body>
</html>