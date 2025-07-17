<?php
// Simple Portal Login - Process login without Laravel complexity

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Bootstrap Laravel for password checking
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Direct database query
    $db = new mysqli('127.0.0.1', 'askproai_user', 'lkZ57Dju9EDjrMxn', 'askproai_db');
    $stmt = $db->prepare("SELECT id, password, name, company_id FROM portal_users WHERE email = ? AND is_active = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Create session
            session_start();
            $sessionId = 'portal_' . bin2hex(random_bytes(16));
            
            $sessionData = [
                '_token' => bin2hex(random_bytes(16)),
                'portal_user_id' => $user['id'],
                '_previous' => ['url' => 'https://api.askproai.de/business/dashboard'],
                '_flash' => ['old' => [], 'new' => []],
                'portal_auth' => ['password_confirmed_at' => time(), 'user_id' => $user['id']],
                'guard.portal' => $user['id']
            ];
            
            // Insert session
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $userAgent = $db->real_escape_string($_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0');
            $payload = base64_encode(serialize($sessionData));
            
            $db->query("DELETE FROM sessions WHERE ip_address = '$ip'");
            
            $stmt = $db->prepare("INSERT INTO sessions (id, user_id, ip_address, user_agent, payload, last_activity) VALUES (?, NULL, ?, ?, ?, ?)");
            $lastActivity = time();
            $stmt->bind_param("ssssi", $sessionId, $ip, $userAgent, $payload, $lastActivity);
            $stmt->execute();
            
            // Set cookies
            setcookie('askproai_session', $sessionId, time() + 86400, '/', '.askproai.de', true, true);
            setcookie('XSRF-TOKEN', $sessionData['_token'], time() + 86400, '/', '.askproai.de', true, false);
            
            // Redirect
            header('Location: /business/dashboard');
            exit;
        } else {
            $error = "Passwort falsch";
        }
    } else {
        $error = "Benutzer nicht gefunden oder inaktiv";
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Simple Login</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 90%;
        }
        h1 {
            color: #1a202c;
            margin-bottom: 30px;
            text-align: center;
        }
        .credentials {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .credential {
            background: white;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-family: monospace;
            font-size: 14px;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            margin-bottom: 15px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            background: #3b82f6;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        button:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        .error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c00;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .direct-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        .direct-link a {
            color: #8b5cf6;
            text-decoration: none;
            font-weight: 600;
        }
        .direct-link a:hover {
            color: #7c3aed;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Portal Login</h1>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="credentials">
            <strong>Test-Zugangsdaten:</strong>
            <div class="credential">test@askproai.de / Test123!</div>
            <div class="credential">demo@askproai.de / Demo123!</div>
            <div class="credential">demo-user@askproai.de / demo123</div>
        </div>
        
        <form method="POST">
            <input type="email" name="email" placeholder="Email" value="test@askproai.de" required>
            <input type="password" name="password" placeholder="Passwort" value="Test123!" required>
            <button type="submit">Anmelden</button>
        </form>
        
        <div class="direct-link">
            Probleme? <a href="/portal-working-access.php">Direktzugang verwenden →</a>
        </div>
    </div>
</body>
</html>