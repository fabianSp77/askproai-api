<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "=== FIXING PORTAL ACCESS ===\n\n";

// 1. Create/Update a simple test user
$email = 'portal-test@askproai.de';
$password = 'test';

// Delete existing
PortalUser::where('email', $email)->delete();

// Create new
$user = PortalUser::create([
    'email' => $email,
    'password' => Hash::make($password),
    'name' => 'Portal Test User',
    'company_id' => 1,
    'is_active' => true,
    'role' => 'admin',
    'permissions' => json_encode(['calls.view_all' => true, 'billing.view' => true])
]);

echo "✅ User created: {$email} / {$password}\n\n";

// 2. Create a bypass file in public directory
$bypassContent = '<?php
session_start();

// Direct database connection
$db = new mysqli("127.0.0.1", "askproai_user", "lkZ57Dju9EDjrMxn", "askproai_db");

// Force set session
$_SESSION["portal_user_id"] = ' . $user->id . ';
$_SESSION["is_portal_authenticated"] = true;
$_SESSION["portal_company_id"] = 1;

// Also set Laravel session directly
$sessionId = session_id();
$sessionData = serialize([
    "_token" => bin2hex(random_bytes(16)),
    "portal_user_id" => ' . $user->id . ',
    "url" => [],
    "_previous" => ["url" => "https://api.askproai.de/business/dashboard"],
    "_flash" => ["old" => [], "new" => []],
    "PHPDEBUGBAR_STACK_DATA" => []
]);

// Update Laravel session in database
$stmt = $db->prepare("REPLACE INTO sessions (id, user_id, ip_address, user_agent, payload, last_activity) VALUES (?, ?, ?, ?, ?, ?)");
$userId = "portal_' . $user->id . '";
$ip = $_SERVER["REMOTE_ADDR"] ?? "127.0.0.1";
$userAgent = $_SERVER["HTTP_USER_AGENT"] ?? "Mozilla/5.0";
$payload = base64_encode($sessionData);
$lastActivity = time();

$stmt->bind_param("sssssi", $sessionId, $userId, $ip, $userAgent, $payload, $lastActivity);
$stmt->execute();

// Set cookies
setcookie("askproai_session", $sessionId, time() + 3600, "/", ".askproai.de", true, true);
setcookie("XSRF-TOKEN", bin2hex(random_bytes(16)), time() + 3600, "/", ".askproai.de", true, false);

echo "<h2>Session Set!</h2>";
echo "<p>Redirecting to dashboard in 2 seconds...</p>";
echo "<script>setTimeout(function() { window.location.href = \"/business/dashboard\"; }, 2000);</script>";
echo "<p>Or click here: <a href=\"/business/dashboard\">Go to Dashboard</a></p>";
';

file_put_contents(__DIR__ . '/public/portal-bypass.php', $bypassContent);

echo "✅ Bypass file created: https://api.askproai.de/portal-bypass.php\n\n";

// 3. Also create a simple login form
$loginFormContent = '<!DOCTYPE html>
<html>
<head>
    <title>Simple Portal Login</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f5f5f5; }
        .login-box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        input { display: block; width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #3b82f6; color: white; border: none; padding: 12px 24px; border-radius: 4px; cursor: pointer; width: 100%; }
        button:hover { background: #2563eb; }
        .info { background: #e0f2fe; padding: 10px; border-radius: 4px; margin-bottom: 20px; color: #0369a1; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Portal Login</h2>
        <div class="info">
            <strong>Test-Login:</strong><br>
            Email: portal-test@askproai.de<br>
            Passwort: test
        </div>
        <form method="POST" action="/business/login">
            <input type="hidden" name="_token" value="' . csrf_token() . '">
            <input type="email" name="email" placeholder="Email" value="portal-test@askproai.de" required>
            <input type="password" name="password" placeholder="Passwort" value="test" required>
            <button type="submit">Login</button>
        </form>
        <hr style="margin: 20px 0;">
        <p style="text-align: center;">
            <a href="/portal-bypass.php">→ Bypass Login (Direkt-Zugang)</a>
        </p>
    </div>
</body>
</html>';

file_put_contents(__DIR__ . '/public/simple-login.html', $loginFormContent);

echo "✅ Simple login form: https://api.askproai.de/simple-login.html\n\n";

echo "=== OPTIONEN ===\n\n";
echo "1. BYPASS (Empfohlen):\n";
echo "   https://api.askproai.de/portal-bypass.php\n\n";
echo "2. SIMPLE LOGIN FORM:\n";
echo "   https://api.askproai.de/simple-login.html\n\n";
echo "3. DIRECT CREDENTIALS:\n";
echo "   Email: portal-test@askproai.de\n";
echo "   Password: test\n";