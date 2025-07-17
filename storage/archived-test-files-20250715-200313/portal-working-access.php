<?php
// Working Portal Access - Direct Session Creation

// Start PHP session
session_start();

// Direct database connection
$db = new mysqli('127.0.0.1', 'askproai_user', 'lkZ57Dju9EDjrMxn', 'askproai_db');

// First, let's update the demo user password directly
$email = 'demo-user@askproai.de';
$hashedPassword = password_hash('demo123', PASSWORD_BCRYPT);

// Check if user exists
$result = $db->query("SELECT id, name, company_id FROM portal_users WHERE email = '$email'");
if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    // Update password and ensure active
    $db->query("UPDATE portal_users SET password = '$hashedPassword', is_active = 1 WHERE email = '$email'");
} else {
    // Create user
    $db->query("INSERT INTO portal_users (email, password, name, company_id, is_active, role, permissions, created_at, updated_at) 
                VALUES ('$email', '$hashedPassword', 'Demo User', 1, 1, 'admin', 
                '{\"calls.view_all\":true,\"billing.view\":true,\"billing.manage\":true,\"appointments.view_all\":true,\"customers.view_all\":true}',
                NOW(), NOW())");
    $user = ['id' => $db->insert_id, 'name' => 'Demo User', 'company_id' => 1];
}

// Create a Laravel-compatible session
$sessionId = 'portal_' . bin2hex(random_bytes(16));
$sessionData = [
    '_token' => bin2hex(random_bytes(16)),
    'portal_user_id' => $user['id'],
    '_previous' => ['url' => 'https://api.askproai.de/business/dashboard'],
    '_flash' => ['old' => [], 'new' => []],
    'url' => [],
    'portal_auth' => [
        'password_confirmed_at' => time(),
        'user_id' => $user['id']
    ],
    'guard.portal' => $user['id']
];

// Delete any existing sessions for this IP
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$db->query("DELETE FROM sessions WHERE ip_address = '$ip'");

// Insert new session
$payload = base64_encode(serialize($sessionData));
$userAgent = $db->real_escape_string($_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0');
$lastActivity = time();

$stmt = $db->prepare("INSERT INTO sessions (id, user_id, ip_address, user_agent, payload, last_activity) VALUES (?, NULL, ?, ?, ?, ?)");
$stmt->bind_param("ssssi", $sessionId, $ip, $userAgent, $payload, $lastActivity);
$stmt->execute();

// Set cookies
setcookie('askproai_session', $sessionId, time() + 86400, '/', '.askproai.de', true, true);
setcookie('XSRF-TOKEN', $sessionData['_token'], time() + 86400, '/', '.askproai.de', true, false);

// Also set PHP session
$_SESSION['portal_user_id'] = $user['id'];
$_SESSION['portal_auth'] = true;

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Access - Working</title>
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
            max-width: 600px;
            text-align: center;
        }
        .success-icon {
            font-size: 72px;
            margin-bottom: 20px;
            animation: bounce 0.5s ease-in-out;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        h1 {
            color: #1a202c;
            margin-bottom: 20px;
        }
        .info {
            background: #e0f2fe;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #0284c7;
        }
        .button {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 14px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin: 10px;
            transition: all 0.2s;
        }
        .button:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }
        .features {
            text-align: left;
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .features li {
            margin: 10px 0;
        }
        .countdown {
            color: #64748b;
            margin-top: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">✅</div>
        <h1>Portal-Zugang erfolgreich!</h1>
        
        <div class="info">
            <strong>Session erstellt für:</strong><br>
            <?= htmlspecialchars($user['name']) ?><br>
            Email: <?= htmlspecialchars($email) ?><br>
            Session ID: <?= substr($sessionId, 0, 20) ?>...
        </div>

        <div class="features">
            <h3>🎯 Jetzt können Sie testen:</h3>
            <ul>
                <li>🎵 Audio-Player in der Anrufliste</li>
                <li>📄 Transkript-Toggle (ein-/ausklappbar)</li>
                <li>🌐 Übersetzungsfunktion (12 Sprachen)</li>
                <li>📊 Neue Call-Detail-Ansicht</li>
                <li>💳 Stripe-Integration</li>
            </ul>
        </div>

        <div style="margin-top: 30px;">
            <a href="/business/dashboard" class="button">
                📊 Zum Dashboard
            </a>
            <a href="/business/calls" class="button" style="background: #8b5cf6;">
                📞 Direkt zu Anrufen
            </a>
        </div>

        <div class="countdown">
            Automatische Weiterleitung in <span id="seconds">5</span> Sekunden...
        </div>
    </div>

    <script>
        let seconds = 5;
        const interval = setInterval(() => {
            seconds--;
            document.getElementById('seconds').textContent = seconds;
            if (seconds <= 0) {
                clearInterval(interval);
                window.location.href = '/business/calls';
            }
        }, 1000);
    </script>
</body>
</html>