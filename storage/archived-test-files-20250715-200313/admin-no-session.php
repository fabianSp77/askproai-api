<?php
// Admin ohne Session - Direkte Token-basierte Authentifizierung

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

// Finde Admin User
$admin = User::where('email', 'admin@askproai.de')
    ->orWhere('email', 'fabian@askproai.de')
    ->first();

if (!$admin) {
    die('Kein Admin-Benutzer gefunden!');
}

// Generiere permanenten Token
$permanentToken = 'admin_permanent_' . $admin->id;

// Speichere Token permanent im Cache
Cache::forever('admin_auth_' . $permanentToken, $admin->id);

// Cookie setzen
setcookie('admin_auth_token', $permanentToken, [
    'expires' => time() + (365 * 24 * 60 * 60), // 1 Jahr
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
    <title>Admin Zugang aktiviert</title>
    <meta http-equiv="refresh" content="2;url=/admin">
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
        .loader {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success">✓ Admin-Zugang aktiviert!</div>
        <p>Du wirst in 2 Sekunden weitergeleitet...</p>
        <div class="loader"></div>
        <p style="color: #6b7280; font-size: 14px;">
            Eingeloggt als: <?php echo htmlspecialchars($admin->email); ?>
        </p>
    </div>
</body>
</html>