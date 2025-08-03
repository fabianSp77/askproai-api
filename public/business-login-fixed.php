<?php
/**
 * Fixed Business Portal Login
 */

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

$message = '';
$messageType = '';

// Check if already logged in
if (Auth::guard('portal')->check()) {
    $currentUser = Auth::guard('portal')->user();
    $message = "Sie sind bereits angemeldet als: {$currentUser->email} (ID: {$currentUser->id})";
    $messageType = 'info';
}

// Handle logout
if (isset($_GET['logout'])) {
    Auth::guard('portal')->logout();
    Session::flush();
    Session::regenerateToken();
    $message = "Sie wurden erfolgreich abgemeldet.";
    $messageType = 'success';
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (Auth::guard('portal')->check()) {
        // Already logged in as someone else
        $currentUser = Auth::guard('portal')->user();
        if ($currentUser->email !== $email) {
            $message = "Sie sind bereits als {$currentUser->email} angemeldet. Bitte melden Sie sich zuerst ab.";
            $messageType = 'error';
        } else {
            $message = "Sie sind bereits angemeldet!";
            $messageType = 'info';
        }
    } else {
        // Try to login
        $user = PortalUser::withoutGlobalScope(\App\Scopes\CompanyScope::class)
            ->where('email', $email)
            ->first();
            
        if ($user && Hash::check($password, $user->password)) {
            if (!$user->is_active) {
                $message = "Ihr Konto ist deaktiviert.";
                $messageType = 'error';
            } else {
                Auth::guard('portal')->login($user);
                Session::regenerate();
                $message = "Login erfolgreich! Sie sind jetzt als {$user->email} angemeldet.";
                $messageType = 'success';
            }
        } else {
            $message = "Ungültige Anmeldedaten.";
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <title>Business Portal - Fixed Login</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 400px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #1f2937;
            margin-bottom: 30px;
        }
        .message {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover {
            background: #2563eb;
        }
        .links {
            margin-top: 20px;
            text-align: center;
        }
        .links a {
            color: #3b82f6;
            text-decoration: none;
            margin: 0 10px;
        }
        .links a:hover {
            text-decoration: underline;
        }
        .status {
            background: #f9fafb;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .demo-info {
            background: #eff6ff;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #bfdbfe;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Business Portal Login</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="status">
            <strong>Login Status:</strong><br>
            <?php if (Auth::guard('portal')->check()): ?>
                ✅ Angemeldet als: <?php echo Auth::guard('portal')->user()->email; ?><br>
                User ID: <?php echo Auth::guard('portal')->id(); ?><br>
                <a href="?logout=1" style="color: #dc2626;">→ Abmelden</a>
            <?php else: ?>
                ❌ Nicht angemeldet
            <?php endif; ?>
        </div>
        
        <?php if (!Auth::guard('portal')->check()): ?>
            <div class="demo-info">
                <strong>Demo Zugangsdaten:</strong><br>
                Email: demo@askproai.de<br>
                Passwort: password
            </div>
            
            <form method="POST">
                <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                <input type="email" name="email" placeholder="E-Mail-Adresse" value="demo@askproai.de" required>
                <input type="password" name="password" placeholder="Passwort" value="password" required>
                <button type="submit">Anmelden</button>
            </form>
        <?php else: ?>
            <div style="text-align: center;">
                <a href="/business/dashboard" style="display: inline-block; padding: 12px 24px; background: #10b981; color: white; text-decoration: none; border-radius: 4px;">
                    → Zum Dashboard
                </a>
            </div>
        <?php endif; ?>
        
        <div class="links">
            <a href="/business-login-test.html">Test Dashboard</a>
            <a href="/business/login">Original Login</a>
        </div>
    </div>
</body>
</html>