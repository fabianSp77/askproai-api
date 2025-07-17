<?php
// Initialize Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

// Get current session info
session_start();
$sessionId = session_id();

// Always use direct database query to avoid scope issues
$testUser = DB::table('portal_users')
    ->where('email', 'fabianspitzer@icloud.com')
    ->first();

$message = '';
$error = '';
$debugInfo = [];

// Handle login attempt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Direct DB query
    $user = DB::table('portal_users')->where('email', $email)->first();
    
    if (!$user) {
        $error = "Benutzer nicht gefunden in DB";
        $debugInfo[] = "Query: SELECT * FROM portal_users WHERE email = '$email'";
    } elseif (!$user->is_active) {
        $error = "Account ist nicht aktiv";
        $debugInfo[] = "User ID: {$user->id}, Active: {$user->is_active}";
    } else {
        // Test password
        $passwordValid = Hash::check($password, $user->password);
        $debugInfo[] = "Password check: " . ($passwordValid ? 'VALID' : 'INVALID');
        $debugInfo[] = "Password length in DB: " . strlen($user->password);
        
        if (!$passwordValid) {
            $error = "Falsches Passwort";
            
            // Try common passwords
            $testPasswords = ['demo123', 'test123', 'password', $email];
            foreach ($testPasswords as $testPw) {
                if (Hash::check($testPw, $user->password)) {
                    $debugInfo[] = "Working password found: $testPw";
                    break;
                }
            }
        } else {
            // Try to get the model
            $portalUser = PortalUser::withoutGlobalScopes()->find($user->id);
            
            if ($portalUser) {
                // Manual login
                Auth::guard('portal')->login($portalUser);
                
                if (Auth::guard('portal')->check()) {
                    $message = "✅ Login erfolgreich!";
                    $debugInfo[] = "Auth check: PASSED";
                    $debugInfo[] = "User ID in session: " . Auth::guard('portal')->id();
                    
                    // Set redirect
                    $_SESSION['login_success'] = true;
                    $redirectUrl = '/business/dashboard';
                } else {
                    $error = "Auth Guard Login fehlgeschlagen";
                    $debugInfo[] = "Auth check: FAILED";
                }
            } else {
                $error = "Model konnte nicht geladen werden";
            }
        }
    }
}

// Reset password if requested
if (isset($_GET['reset']) && $_GET['reset'] === 'true') {
    if ($testUser) {
        $newHash = Hash::make('demo123');
        DB::table('portal_users')
            ->where('id', $testUser->id)
            ->update(['password' => $newHash]);
        $message = "✅ Passwort wurde auf 'demo123' zurückgesetzt!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Login Test</title>
    <meta charset="utf-8">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        button {
            background: #4F46E5;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover {
            background: #4338CA;
        }
        .error {
            background: #FEE2E2;
            color: #991B1B;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .success {
            background: #D1FAE5;
            color: #065F46;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .info-box {
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
        }
        .debug {
            background: #F9FAFB;
            border: 1px solid #E5E7EB;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 12px;
        }
        .user-info {
            background: #F3F4F6;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .reset-link {
            display: inline-block;
            margin-top: 10px;
            color: #4F46E5;
            text-decoration: none;
        }
        .reset-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Simple Portal Login Test</h1>
        
        <?php if ($error): ?>
            <div class="error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="success"><?= $message ?></div>
            <?php if (isset($redirectUrl)): ?>
                <script>
                    setTimeout(function() {
                        window.location.href = '<?= $redirectUrl ?>';
                    }, 2000);
                </script>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($testUser): ?>
            <div class="user-info">
                <h3>📋 Test-Account Details:</h3>
                <p><strong>Email:</strong> <?= htmlspecialchars($testUser->email) ?></p>
                <p><strong>Name:</strong> <?= htmlspecialchars($testUser->name) ?></p>
                <p><strong>Firma ID:</strong> <?= $testUser->company_id ?></p>
                <p><strong>Aktiv:</strong> <?= $testUser->is_active ? '✅ Ja' : '❌ Nein' ?></p>
                <p><strong>Rolle:</strong> <?= htmlspecialchars($testUser->role) ?></p>
                <p><strong>Empfohlenes Passwort:</strong> demo123</p>
                <a href="?reset=true" class="reset-link">🔄 Passwort auf 'demo123' zurücksetzen</a>
            </div>
        <?php else: ?>
            <div class="error">
                ❌ Test-User (fabianspitzer@icloud.com) nicht in Datenbank gefunden!
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="fabianspitzer@icloud.com" required>
            </div>
            
            <div class="form-group">
                <label for="password">Passwort:</label>
                <input type="password" id="password" name="password" placeholder="demo123" required>
            </div>
            
            <button type="submit">Login Testen</button>
        </form>
        
        <div class="info-box">
            <h3>ℹ️ Hinweise:</h3>
            <ul>
                <li>Diese Seite testet den Login direkt ohne komplexe Middleware</li>
                <li>Verwendet direkte Datenbank-Queries um Scope-Probleme zu vermeiden</li>
                <li>Bei Erfolg wirst du zu /business/dashboard weitergeleitet</li>
                <li>Browser-Cache und Cookies vorher löschen für beste Ergebnisse</li>
            </ul>
        </div>
        
        <?php if (!empty($debugInfo)): ?>
            <div class="debug">
                <h4>🐛 Debug Information:</h4>
                <?php foreach ($debugInfo as $info): ?>
                    <div><?= htmlspecialchars($info) ?></div>
                <?php endforeach; ?>
                <hr>
                <div>Session ID: <?= htmlspecialchars($sessionId) ?></div>
                <div>PHP Session Status: <?= session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive' ?></div>
                <div>Laravel Session Driver: <?= config('session.driver') ?></div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>