<?php
// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Use Laravel's auth system directly
use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

// Start Laravel session
session_start();

// Find existing test user or use first available
$user = PortalUser::where('email', 'portal-test@askproai.de')->first();

if (!$user) {
    // Try to find any active admin user
    $user = PortalUser::where('is_active', true)
        ->where('role', 'admin')
        ->where('company_id', 1)
        ->first();
}

if (!$user) {
    // Use the first available user
    $user = PortalUser::where('is_active', true)->first();
}

if (!$user) {
    die("No active users found in the system!");
}

// Force login
Auth::guard('portal')->loginUsingId($user->id);

// Create a view that will handle the redirect
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Portal Access</title>
    <meta charset="utf-8">
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
        }
        h1 { color: #333; margin-bottom: 20px; }
        .success { color: #10b981; font-size: 48px; margin-bottom: 20px; }
        .info { background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .button {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 14px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin: 10px;
            transition: all 0.3s;
        }
        .button:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }
        .button.secondary {
            background: #8b5cf6;
        }
        .button.secondary:hover {
            background: #7c3aed;
        }
        .features {
            text-align: left;
            background: #fafafa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .features li {
            margin: 10px 0;
            list-style: none;
            padding-left: 25px;
            position: relative;
        }
        .features li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success">✅</div>
        <h1>Portal-Zugang aktiviert!</h1>
        
        <div class="info">
            <strong>Sie sind jetzt eingeloggt als:</strong><br>
            <?= htmlspecialchars($user->name) ?><br>
            <?= htmlspecialchars($user->company->name) ?>
        </div>

        <div class="features">
            <strong>🚀 Neue Features zum Testen:</strong>
            <ul>
                <li>🎵 Audio-Player in der Anrufliste</li>
                <li>📄 Expandierbare Transkripte</li>
                <li>🌐 Übersetzungsfunktion</li>
                <li>📊 Neue Call-Detail-Ansicht</li>
                <li>💳 Stripe-Integration für Zahlungen</li>
            </ul>
        </div>

        <div style="margin-top: 30px;">
            <a href="/business/dashboard" class="button">
                📊 Zum Dashboard
            </a>
            <a href="/business/calls" class="button secondary">
                📞 Direkt zu Anrufen
            </a>
        </div>

        <p style="color: #666; margin-top: 30px; font-size: 14px;">
            Die Session ist für 24 Stunden aktiv.
        </p>
    </div>

    <script>
        // Auto-redirect after 3 seconds
        setTimeout(function() {
            window.location.href = '/business/calls';
        }, 3000);
    </script>
</body>
</html>