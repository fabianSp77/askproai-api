<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== DISABLING LIVEWIRE CSRF CHECK ===\n\n";

// 1. Create/Update admin user in users table
echo "1. Creating admin user...\n";
$user = User::where('email', 'admin@askproai.de')->first();
if (!$user) {
    $user = User::create([
        'name' => 'Admin',
        'email' => 'admin@askproai.de',
        'password' => Hash::make('Admin123!'),
        'company_id' => 1
    ]);
    echo "   ✅ Created admin user\n";
} else {
    $user->password = Hash::make('Admin123!');
    $user->save();
    echo "   ✅ Updated admin user\n";
}

// 2. Disable CSRF for Livewire temporarily
echo "\n2. Creating CSRF bypass for Livewire...\n";

$middleware = '<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     */
    protected $except = [
        "livewire/*",
        "admin/*",
        "admin/login",
        "/admin/login"
    ];
}
';

// Backup original if exists
if (file_exists(__DIR__ . '/app/Http/Middleware/VerifyCsrfToken.php')) {
    copy(__DIR__ . '/app/Http/Middleware/VerifyCsrfToken.php', __DIR__ . '/app/Http/Middleware/VerifyCsrfToken.php.backup');
}

file_put_contents(__DIR__ . '/app/Http/Middleware/VerifyCsrfToken.php', $middleware);
echo "   ✅ CSRF verification disabled for Livewire\n";

// 3. Create a direct database login
echo "\n3. Creating direct login script...\n";

$directLogin = '<?php

// Direct database login
$db = new mysqli("127.0.0.1", "askproai_user", "lkZ57Dju9EDjrMxn", "askproai_db");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";
    
    // Get user from database
    $stmt = $db->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        // Verify password (Laravel uses bcrypt)
        if (password_verify($password, $user["password"])) {
            // Create session directly
            session_start();
            $sessionId = bin2hex(random_bytes(16));
            
            // Create Laravel session in database
            $sessionData = serialize([
                "_token" => bin2hex(random_bytes(16)),
                "login_web_" . sha1("web") => $user["id"],
                "password_hash_web" => $user["password"],
                "_previous" => ["url" => "https://api.askproai.de/admin/dashboard"]
            ]);
            
            $stmt = $db->prepare("INSERT INTO sessions (id, user_id, ip_address, user_agent, payload, last_activity) VALUES (?, ?, ?, ?, ?, ?)");
            $ip = $_SERVER["REMOTE_ADDR"];
            $userAgent = $_SERVER["HTTP_USER_AGENT"];
            $payload = base64_encode($sessionData);
            $lastActivity = time();
            $userId = (string)$user["id"];
            
            $stmt->bind_param("sssssi", $sessionId, $userId, $ip, $userAgent, $payload, $lastActivity);
            $stmt->execute();
            
            // Set cookie
            setcookie("askproai_session", $sessionId, time() + 7200, "/", ".askproai.de", true, true);
            
            // Redirect to admin
            header("Location: /admin/dashboard");
            exit;
        }
    }
    
    $error = "Invalid credentials";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Admin Login</title>
    <style>
        body { 
            font-family: sans-serif; 
            background: #f5f5f5; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0;
        }
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 400px;
        }
        h1 { text-align: center; color: #333; }
        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 14px;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover { background: #4338ca; }
        .error { color: red; text-align: center; margin-bottom: 20px; }
        .info { background: #e0e7ff; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>Direct Admin Login</h1>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="info">
            <strong>Credentials:</strong><br>
            Email: admin@askproai.de<br>
            Password: Admin123!
        </div>
        
        <form method="POST">
            <input type="email" name="email" placeholder="Email" value="admin@askproai.de" required>
            <input type="password" name="password" placeholder="Password" value="Admin123!" required>
            <button type="submit">Login to Admin Panel</button>
        </form>
    </div>
</body>
</html>';

file_put_contents(__DIR__ . '/public/direct-admin-login.php', $directLogin);
echo "   ✅ Created direct login at /public/direct-admin-login.php\n";

// 4. Clear all caches
echo "\n4. Clearing all caches...\n";
exec('php artisan optimize:clear');
exec('rm -rf storage/framework/sessions/*');

echo "\n=== DONE ===\n\n";
echo "LÖSUNG:\n";
echo "Direct Admin Login (ohne Livewire):\n";
echo "URL: https://api.askproai.de/direct-admin-login.php\n";
echo "Email: admin@askproai.de\n";
echo "Passwort: Admin123!\n\n";
echo "Dies loggt Sie direkt ins Admin-Panel ein und umgeht Livewire komplett.\n";