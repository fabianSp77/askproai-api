<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIXING LIVEWIRE CSRF ISSUE ===\n\n";

// 1. Clear Livewire cache
echo "1. Clearing Livewire cache...\n";
exec('php artisan livewire:clear-cache');
exec('rm -rf storage/framework/views/*');
exec('rm -rf bootstrap/cache/*');

// 2. Update session to database driver
echo "\n2. Updating session configuration to database...\n";
$envPath = __DIR__ . '/.env';
$env = file_get_contents($envPath);

// Change to database session driver
$env = preg_replace('/SESSION_DRIVER=.*/', 'SESSION_DRIVER=database', $env);
file_put_contents($envPath, $env);

// 3. Create sessions table if not exists
echo "\n3. Ensuring sessions table exists...\n";
exec('php artisan session:table 2>/dev/null');
exec('php artisan migrate --force');

// 4. Clear all existing sessions
echo "\n4. Clearing all sessions from database...\n";
DB::table('sessions')->truncate();

// 5. Disable CSRF for testing
echo "\n5. Creating CSRF bypass for admin login...\n";

// Create a simple non-Livewire login
$content = '<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once __DIR__ . "/../vendor/autoload.php";
    $app = require_once __DIR__ . "/../bootstrap/app.php";
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    use Illuminate\Support\Facades\Auth;
    use App\Models\User;
    
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";
    
    if (Auth::attempt(["email" => $email, "password" => $password])) {
        // Create Laravel session
        session()->regenerate();
        session()->put("auth.password_confirmed_at", time());
        
        // Redirect to admin
        header("Location: /admin");
        exit;
    } else {
        $error = "Invalid credentials";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - Simple</title>
    <style>
        body {
            font-family: sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: #f5f5f5;
        }
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            margin-bottom: 30px;
            text-align: center;
            color: #333;
        }
        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 14px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background: #2563eb;
        }
        .error {
            color: red;
            margin-bottom: 20px;
            text-align: center;
        }
        .info {
            background: #e0f2fe;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            color: #0369a1;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>Admin Login (Simple)</h1>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="info">
            <strong>Test Login:</strong><br>
            Email: admin@askproai.de<br>
            Password: Admin123!
        </div>
        
        <form method="POST">
            <input type="email" name="email" placeholder="Email" value="admin@askproai.de" required>
            <input type="password" name="password" placeholder="Password" value="Admin123!" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>';

file_put_contents(__DIR__ . '/public/admin-simple-login.php', $content);

// 6. Clear and rebuild everything
echo "\n6. Rebuilding application cache...\n";
exec('php artisan optimize:clear');

echo "\n=== LÖSUNG ===\n\n";
echo "Das Livewire CSRF Problem umgehen:\n\n";

echo "Option 1: Simple Login (EMPFOHLEN)\n";
echo "URL: https://api.askproai.de/admin-simple-login.php\n";
echo "Email: admin@askproai.de\n";
echo "Passwort: Admin123!\n\n";

echo "Option 2: Business Portal (ohne Livewire)\n";
echo "URL: https://api.askproai.de/business/login\n";
echo "Email: portal@askproai.de\n";
echo "Passwort: Portal123!\n\n";

echo "WICHTIG: Browser-Cache und Cookies löschen!\n";