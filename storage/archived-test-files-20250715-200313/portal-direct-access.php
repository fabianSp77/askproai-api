<?php
/**
 * Direct Portal Access - Bypass for Testing
 * This creates a valid Laravel session and redirects to the portal
 */

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

// Generate a unique session ID
$sessionId = 'portal_' . bin2hex(random_bytes(16));

// Find or create test user - bypass all scopes
$user = PortalUser::withoutGlobalScopes()->where('email', 'demo-user@askproai.de')->first();

if (!$user) {
    // Check if user exists with different status
    $existingUser = DB::table('portal_users')->where('email', 'demo-user@askproai.de')->first();
    if ($existingUser) {
        // Update existing user to be active
        DB::table('portal_users')
            ->where('email', 'demo-user@askproai.de')
            ->update([
                'is_active' => true,
                'password' => bcrypt('demo123'),
                'updated_at' => now()
            ]);
        $user = PortalUser::withoutGlobalScopes()->where('email', 'demo-user@askproai.de')->first();
    } else {
        // Create new user
        $user = PortalUser::create([
            'email' => 'demo-user@askproai.de',
            'password' => bcrypt('demo123'),
            'name' => 'Demo User',
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
}

// Create session data
$sessionData = [
    '_token' => bin2hex(random_bytes(16)),
    'portal_user_id' => $user->id,
    '_previous' => ['url' => 'https://api.askproai.de/business/dashboard'],
    '_flash' => ['old' => [], 'new' => []],
    'url' => [],
    'portal_auth' => [
        'password_confirmed_at' => time(),
        'user_id' => $user->id
    ]
];

// Insert session directly into database
DB::table('sessions')->insert([
    'id' => $sessionId,
    'user_id' => null, // Laravel sessions don't require user_id for portal users
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0',
    'payload' => base64_encode(serialize($sessionData)),
    'last_activity' => time()
]);

// Set the session cookie
setcookie('askproai_session', $sessionId, time() + 86400, '/', null, true, true);
setcookie('XSRF-TOKEN', $sessionData['_token'], time() + 86400, '/', null, true, false);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Direct Access</title>
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
            width: 90%;
        }
        h1 {
            color: #1a202c;
            margin-bottom: 20px;
            font-size: 28px;
        }
        .success-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .info-box {
            background: #e0f2fe;
            border-left: 4px solid #0284c7;
            padding: 16px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .features {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .features h3 {
            margin-top: 0;
            color: #475569;
        }
        .features ul {
            list-style: none;
            padding: 0;
        }
        .features li {
            padding: 8px 0;
            padding-left: 28px;
            position: relative;
        }
        .features li:before {
            content: "✅";
            position: absolute;
            left: 0;
        }
        .button {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin: 8px;
            transition: all 0.2s;
        }
        .button:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }
        .button.secondary {
            background: #8b5cf6;
        }
        .button.secondary:hover {
            background: #7c3aed;
        }
        .button.success {
            background: #10b981;
        }
        .button.success:hover {
            background: #059669;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 30px;
            justify-content: center;
        }
        .countdown {
            color: #64748b;
            margin-top: 20px;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="text-align: center;">
            <div class="success-icon">🚀</div>
            <h1>Portal-Zugang aktiviert!</h1>
        </div>
        
        <div class="info-box">
            <strong>✅ Sie sind jetzt eingeloggt als:</strong><br>
            <?= htmlspecialchars($user->name) ?> (<?= htmlspecialchars($user->email) ?>)<br>
            Firma: <?= htmlspecialchars($user->company->name ?? 'AskProAI Demo') ?>
        </div>

        <div class="features">
            <h3>🎯 Neue Features zum Testen:</h3>
            <ul>
                <li>Audio-Player für Anrufaufnahmen</li>
                <li>Transkript Ein-/Ausklappen</li>
                <li>Übersetzungsfunktion (DeepL/Google)</li>
                <li>Detaillierte Call-Ansicht</li>
                <li>Stripe Zahlungsintegration</li>
                <li>Kosten-Breakdown pro Anruf</li>
            </ul>
        </div>

        <div class="actions">
            <a href="/business/dashboard" class="button">
                📊 Dashboard
            </a>
            <a href="/business/calls" class="button secondary">
                📞 Anrufliste
            </a>
            <a href="/business/billing" class="button success">
                💳 Billing & Stripe
            </a>
        </div>

        <div class="countdown" id="countdown">
            Automatische Weiterleitung in <span id="seconds">5</span> Sekunden...
        </div>
    </div>

    <script>
        // Countdown and redirect
        let seconds = 5;
        const countdownElement = document.getElementById('seconds');
        
        const interval = setInterval(() => {
            seconds--;
            countdownElement.textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(interval);
                window.location.href = '/business/calls';
            }
        }, 1000);

        // Allow manual navigation to stop countdown
        document.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                clearInterval(interval);
            });
        });
    </script>
</body>
</html>