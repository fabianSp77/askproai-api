<?php
// Emergency Admin Login - Completely bypasses Laravel's session/CSRF system
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Simple database connection for emergency access
$config = include __DIR__ . '/../config/database.php';
$dbConfig = $config['connections']['mysql'];

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        // Direct database connection with hardcoded credentials for emergency
        $pdo = new PDO(
            "mysql:host=127.0.0.1;dbname=askproai_db;charset=utf8mb4",
            "askproai_user",
            "lkZ57Dju9EDjrMxn"
        );
        
        // Find user
        $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Create a simple auth token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 86400); // 24 hours
            
            // Store token in database
            $stmt = $pdo->prepare("INSERT INTO personal_access_tokens (tokenable_type, tokenable_id, name, token, abilities, expires_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([
                'App\\Models\\User',
                $user['id'],
                'emergency-login',
                hash('sha256', $token),
                '["*"]',
                $expires
            ]);
            
            // Set emergency auth cookie
            setcookie('emergency_admin_token', $token, time() + 86400, '/', '', true, true);
            setcookie('emergency_admin_user', $user['id'], time() + 86400, '/', '', true, true);
            
            $success = true;
        } else {
            $error = 'Ungültige Anmeldedaten';
        }
    } catch (Exception $e) {
        $error = 'Datenbankfehler: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Emergency Admin Login</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #ef4444; padding: 20px; }
        .login-box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); width: 100%; max-width: 400px; }
        h2 { margin: 0 0 10px 0; color: #dc2626; }
        .subtitle { color: #7f1d1d; font-size: 14px; margin-bottom: 30px; font-weight: bold; }
        input { width: 100%; padding: 12px; margin: 8px 0 20px 0; border: 2px solid #dc2626; border-radius: 6px; font-size: 16px; box-sizing: border-box; }
        input:focus { outline: none; border-color: #991b1b; }
        button { width: 100%; padding: 12px; background: #dc2626; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; font-weight: bold; }
        button:hover { background: #991b1b; }
        .error { background: #7f1d1d; color: white; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .success { background: #16a34a; color: white; padding: 20px; border-radius: 6px; text-align: center; font-size: 18px; }
        .warning { background: #fef2f2; color: #7f1d1d; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; border: 2px solid #dc2626; }
        .info { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-top: 20px; font-size: 13px; }
        a { color: #dc2626; text-decoration: none; font-weight: bold; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-box">
        <?php if ($success): ?>
            <div class="success">
                ✓ Notfall-Login erfolgreich!<br><br>
                <a href="/admin" style="color: white;">→ Zum Admin Panel</a>
            </div>
            <script>
                setTimeout(() => { window.location.href = '/admin'; }, 2000);
            </script>
        <?php else: ?>
            <h2>🚨 NOTFALL LOGIN</h2>
            <div class="subtitle">Nur für kritische Situationen verwenden!</div>
            
            <?php if ($error): ?>
                <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="warning">
                ⚠️ <strong>WARNUNG:</strong> Dies ist ein Notfall-Zugang, der alle Sicherheitsmechanismen umgeht. Verwenden Sie ihn nur, wenn der normale Login nicht funktioniert!
            </div>
            
            <form method="POST">
                <input type="email" name="email" placeholder="Admin E-Mail-Adresse" required autofocus>
                <input type="password" name="password" placeholder="Passwort" required>
                <button type="submit">NOTFALL-ANMELDUNG</button>
            </form>
            
            <div class="info">
                Nach dem Login müssen Sie möglicherweise:<br>
                • Browser-Cache leeren (Strg+Shift+Entf)<br>
                • In einem anderen Browser versuchen<br>
                • <a href="/admin-bypass-login.php">Alternative Login-Methode</a> verwenden
            </div>
        <?php endif; ?>
    </div>
</body>
</html>