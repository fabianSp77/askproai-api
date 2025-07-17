<?php
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
</html>