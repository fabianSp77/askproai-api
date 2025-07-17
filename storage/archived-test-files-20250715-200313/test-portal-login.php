<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Bootstrap the app properly
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

// Start session
session_start();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Find user
    $user = PortalUser::where('email', $email)->first();
    
    if (!$user) {
        $error = "Benutzer nicht gefunden";
    } elseif (!$user->is_active) {
        $error = "Account ist nicht aktiv";
    } elseif (!Hash::check($password, $user->password)) {
        $error = "Falsches Passwort";
        // Debug info
        $error .= "<br>Debug: Hash-Check fehlgeschlagen";
        $error .= "<br>Eingegebenes PW: " . htmlspecialchars($password);
        $error .= "<br>Hash in DB: " . substr($user->password, 0, 20) . "...";
    } else {
        // Try to login
        Auth::guard('portal')->login($user);
        
        if (Auth::guard('portal')->check()) {
            $message = "Login erfolgreich! Redirecting...";
            header("Location: /business/dashboard");
            exit;
        } else {
            $error = "Login fehlgeschlagen trotz korrektem Passwort";
        }
    }
}

// Test data
$testUser = PortalUser::where('email', 'fabianspitzer@icloud.com')->first();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Portal Login Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            background: #4F46E5;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background: #4338CA;
        }
        .error {
            background: #FEE2E2;
            color: #DC2626;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            background: #D1FAE5;
            color: #065F46;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info {
            background: #E0E7FF;
            color: #3730A3;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .debug {
            background: #F3F4F6;
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Portal Login Test</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="success"><?= $message ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="fabianspitzer@icloud.com" required>
            </div>
            
            <div class="form-group">
                <label>Passwort:</label>
                <input type="password" name="password" placeholder="demo123" required>
            </div>
            
            <button type="submit">Login Testen</button>
        </form>
        
        <div class="info">
            <h3>Test-Account Info:</h3>
            <?php if ($testUser): ?>
                <p><strong>Email:</strong> <?= htmlspecialchars($testUser->email) ?></p>
                <p><strong>Name:</strong> <?= htmlspecialchars($testUser->name) ?></p>
                <p><strong>Aktiv:</strong> <?= $testUser->is_active ? 'Ja ✅' : 'Nein ❌' ?></p>
                <p><strong>Firma:</strong> <?= htmlspecialchars($testUser->company->name ?? 'N/A') ?></p>
                <p><strong>Rolle:</strong> <?= htmlspecialchars($testUser->role) ?></p>
                <p><strong>Passwort:</strong> demo123</p>
            <?php else: ?>
                <p>Test-User nicht gefunden!</p>
            <?php endif; ?>
        </div>
        
        <div class="debug">
            <h4>Debug Info:</h4>
            <p>Session ID: <?= session_id() ?></p>
            <p>Session Driver: <?= config('session.driver') ?></p>
            <p>Auth Guard: portal</p>
            <p>CSRF: Disabled for this test</p>
        </div>
    </div>
</body>
</html>