<?php
// Direkter Admin-Zugang - umgeht alle Laravel Middleware-Probleme

// Laravel Bootstrap
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Auto-Login für Admin
$admin = User::where('email', 'admin@askproai.de')
    ->orWhere('email', 'fabian@askproai.de')
    ->first();

if (!$admin) {
    die('Kein Admin-Benutzer gefunden!');
}

// Session manuell erstellen
$sessionId = Str::random(40);

// Session-Daten vorbereiten
$sessionData = [
    '_token' => Str::random(40),
    '_previous' => ['url' => 'https://api.askproai.de/admin'],
    '_flash' => ['old' => [], 'new' => []],
    'url' => [],
    'login_web_' . sha1('Illuminate\Auth\SessionGuard') => $admin->id,
    'password_hash_web' => $admin->password,
];

// Alte Sessions löschen
DB::table('sessions')->where('user_id', $admin->id)->delete();

// Neue Session in Datenbank erstellen
DB::table('sessions')->insert([
    'id' => $sessionId,
    'user_id' => $admin->id,
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0',
    'payload' => base64_encode(serialize($sessionData)),
    'last_activity' => time(),
]);

// Cookie setzen
setcookie('askproai_session', $sessionId, [
    'expires' => time() + (120 * 60), // 2 Stunden
    'path' => '/',
    'domain' => 'api.askproai.de',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Zusätzlich Laravel Session Cookie setzen
setcookie('laravel_session', $sessionId, [
    'expires' => time() + (120 * 60), // 2 Stunden
    'path' => '/',
    'domain' => 'api.askproai.de',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Admin Zugang</title>
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
        .btn {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #2563eb;
        }
        .code {
            background: #f3f4f6;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success">✓ Admin-Zugang aktiviert!</div>
        <div class="info">
            <p>Eingeloggt als: <strong><?php echo htmlspecialchars($admin->email); ?></strong></p>
            <p>Session ID: <code class="code"><?php echo $sessionId; ?></code></p>
        </div>
        <a href="/admin" class="btn">Zum Admin-Panel →</a>
        
        <div style="margin-top: 30px; color: #dc2626;">
            <small>⚠️ Diese Seite umgeht die normale Authentifizierung.<br>
            Nur für Notfälle verwenden!</small>
        </div>
    </div>
</body>
</html>