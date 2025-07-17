<?php
// Direct Admin Login Bypass - Umgeht alle CSRF Probleme
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

// Bootstrap Laravel
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Request::capture();
$app->instance('request', $request);
$kernel->bootstrap();

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        // Find user
        $user = User::where('email', $email)->first();
        
        if ($user && password_verify($password, $user->password)) {
            // Manual login
            Auth::login($user);
            
            // Force session regeneration
            Session::regenerate();
            Session::put('_token', \Illuminate\Support\Str::random(40));
            Session::save();
            
            // Set admin flag
            Session::put('is_admin', true);
            Session::put('admin_logged_in', true);
            
            // Create a remember token if needed
            if (empty($user->remember_token)) {
                $user->remember_token = \Illuminate\Support\Str::random(60);
                $user->save();
            }
            
            // Set auth cookie manually
            $cookieValue = encrypt([
                'user_id' => $user->id,
                'remember_token' => $user->remember_token,
                'timestamp' => time()
            ]);
            
            setcookie(
                'admin_auth_bypass',
                $cookieValue,
                time() + (86400 * 30), // 30 days
                '/',
                '',
                true, // secure
                true  // httponly
            );
            
            // Success response
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <title>Login Successful</title>
                <meta http-equiv="refresh" content="2;url=/admin">
                <style>
                    body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f3f4f6; }
                    .success-box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
                    .success { color: #10b981; font-size: 24px; margin-bottom: 20px; }
                    .redirect { color: #6b7280; }
                </style>
            </head>
            <body>
                <div class="success-box">
                    <div class="success">✓ Login erfolgreich!</div>
                    <div class="redirect">Sie werden zum Admin-Panel weitergeleitet...</div>
                    <div style="margin-top: 20px;">
                        <a href="/admin" style="color: #3b82f6; text-decoration: none;">Klicken Sie hier, falls die Weiterleitung nicht funktioniert</a>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        } else {
            $error = 'Ungültige Anmeldedaten';
        }
    } catch (Exception $e) {
        $error = 'Login-Fehler: ' . $e->getMessage();
    }
}

// Helper function removed - using Str::random directly
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Bypass Login</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #f3f4f6; padding: 20px; }
        .login-box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { margin: 0 0 10px 0; color: #1f2937; }
        .subtitle { color: #6b7280; font-size: 14px; margin-bottom: 30px; }
        input { width: 100%; padding: 12px; margin: 8px 0 20px 0; border: 1px solid #d1d5db; border-radius: 6px; font-size: 16px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; font-weight: 500; }
        button:hover { background: #2563eb; }
        .error { background: #fef2f2; color: #dc2626; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .warning { background: #fef3c7; color: #92400e; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .info { background: #eff6ff; color: #1e40af; padding: 12px; border-radius: 6px; margin-top: 20px; font-size: 14px; }
        .debug { background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 20px; font-size: 12px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Admin Bypass Login</h2>
        <div class="subtitle">Direkter Login ohne CSRF-Validierung</div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="warning">
            ⚠️ Diese Login-Methode umgeht die CSRF-Sicherheit. Nur für Notfälle verwenden!
        </div>
        
        <form method="POST">
            <input type="email" name="email" placeholder="E-Mail-Adresse" required autofocus value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            <input type="password" name="password" placeholder="Passwort" required>
            <button type="submit">Anmelden</button>
        </form>
        
        <div class="info">
            Nach erfolgreichem Login werden Sie automatisch zum Admin-Panel weitergeleitet.
        </div>
        
        <div class="debug">
            Session ID: <?php echo session_id(); ?><br>
            CSRF aktiv: NEIN<br>
            Admin Routes: <?php echo class_exists('\Filament\FilamentManager') ? 'JA' : 'NEIN'; ?>
        </div>
    </div>
</body>
</html>