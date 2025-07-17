<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Simple login form
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Quick Login</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f3f4f6; }
        .login-box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 350px; }
        h2 { margin: 0 0 30px 0; color: #1f2937; }
        input { width: 100%; padding: 12px; margin: 8px 0 20px 0; border: 1px solid #d1d5db; border-radius: 6px; font-size: 16px; }
        button { width: 100%; padding: 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; }
        button:hover { background: #2563eb; }
        .error { color: #ef4444; margin-bottom: 20px; }
        .info { color: #6b7280; font-size: 14px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Admin Quick Login</h2>
        <?php if (isset($_GET['error'])): ?>
            <div class="error">Login fehlgeschlagen. Bitte überprüfen Sie Ihre Zugangsdaten.</div>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="E-Mail" required autofocus>
            <input type="password" name="password" placeholder="Passwort" required>
            <button type="submit">Anmelden</button>
        </form>
        <div class="info">
            Diese temporäre Login-Seite umgeht CSRF-Probleme.<br>
            Nach dem Login werden Sie zum Admin-Panel weitergeleitet.
        </div>
    </div>
</body>
</html>
<?php
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Attempt login
    if (Auth::attempt(['email' => $email, 'password' => $password])) {
        // Regenerate session
        session()->regenerate();
        
        // Set admin session
        session(['is_admin' => true]);
        
        // Redirect to admin panel
        header('Location: /admin');
        exit;
    } else {
        // Redirect back with error
        header('Location: /admin-quick-login.php?error=1');
        exit;
    }
}
?>