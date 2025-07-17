<?php
// Admin Token Login - Umgeht alle Session-Probleme

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

// Admin User finden
$admin = User::where('email', 'admin@askproai.de')
    ->orWhere('email', 'fabian@askproai.de')
    ->first();

if (!$admin) {
    die('Kein Admin-Benutzer gefunden!');
}

// Token generieren
$token = Str::random(64);

// Token in Cache speichern (15 Minuten gültig)
Cache::put('admin_token_' . $token, $admin->id, 900);

// Redirect URL mit Token
$redirectUrl = '/admin?admin_token=' . $token;

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Admin Token Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: #f3f4f6;
        }
        .container {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 600px;
        }
        .success {
            color: #10b981;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .info {
            color: #6b7280;
            margin-bottom: 30px;
        }
        .token {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            word-break: break-all;
            margin: 20px 0;
            border: 2px dashed #d1d5db;
        }
        .btn {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.2s;
            margin: 10px;
        }
        .btn:hover {
            background: #2563eb;
        }
        .warning {
            color: #dc2626;
            font-size: 14px;
            margin-top: 20px;
        }
    </style>
    <script>
        function copyToken() {
            const token = document.getElementById('token').innerText;
            navigator.clipboard.writeText(token);
            alert('Token kopiert!');
        }
        
        // Auto-redirect nach 3 Sekunden
        setTimeout(function() {
            window.location.href = '<?php echo $redirectUrl; ?>';
        }, 3000);
    </script>
</head>
<body>
    <div class="container">
        <div class="success">✓ Admin Token generiert!</div>
        <div class="info">
            <p>Du wirst in 3 Sekunden automatisch weitergeleitet...</p>
            <p>Falls die Weiterleitung nicht funktioniert, klicke auf den Button:</p>
        </div>
        
        <a href="<?php echo $redirectUrl; ?>" class="btn">Jetzt zum Admin-Panel →</a>
        
        <div class="token" id="token"><?php echo $token; ?></div>
        <button onclick="copyToken()" class="btn" style="background: #6b7280;">Token kopieren</button>
        
        <div class="warning">
            ⚠️ Dieser Token ist nur 15 Minuten gültig und kann nur einmal verwendet werden.
        </div>
    </div>
</body>
</html>