<?php
// Force Admin Login - Ultimate Bypass

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\User;

// Check if already logged in
if (Auth::check()) {
    echo "<h2>Already logged in as: " . Auth::user()->email . "</h2>";
    echo '<p><a href="/admin">Go to Admin Panel</a></p>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $user = User::where('email', $email)->first();
    
    if ($user && \Illuminate\Support\Facades\Hash::check($password, $user->password)) {
        // Disable all middleware temporarily
        config(['session.driver' => 'array']);
        config(['auth.defaults.guard' => 'web']);
        
        // Force login
        Auth::guard('web')->loginUsingId($user->id, true);
        
        // Create session manually
        Session::put('_token', \Illuminate\Support\Str::random(40));
        Session::put('password_hash_web', $user->password);
        Session::save();
        
        // Set auth cookies manually
        setcookie('XSRF-TOKEN', Session::token(), time() + 120 * 60, '/', '', true, false);
        setcookie('askproai_session', Session::getId(), time() + 120 * 60, '/', '', true, true);
        
        // Success message with JavaScript redirect
        echo '
        <h2>Login Successful!</h2>
        <p>Logged in as: ' . $user->email . '</p>
        <p>Redirecting to admin panel...</p>
        <script>
            setTimeout(function() {
                window.location.href = "/admin";
            }, 1000);
        </script>
        <p>If not redirected, <a href="/admin">click here</a></p>
        ';
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Force Admin Login</title>
    <style>
        body { 
            font-family: Arial; 
            max-width: 500px; 
            margin: 50px auto; 
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        input, button { 
            display: block; 
            width: 100%; 
            margin: 10px 0; 
            padding: 12px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            background: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background: #45a049;
        }
        .error { 
            color: red; 
            margin: 10px 0;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #2196F3;
        }
        .warning {
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔓 Force Admin Login</h2>
        
        <div class="warning">
            ⚠️ This completely bypasses Laravel's session system and forces a login.
        </div>
        
        <?php if (isset($error)): ?>
            <p class="error">❌ <?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        
        <form method="POST">
            <input type="email" name="email" placeholder="Email" value="admin@askproai.de" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Force Login</button>
        </form>
        
        <div class="info">
            <strong>Known Admin Accounts:</strong><br>
            • admin@askproai.de<br>
            • fabian@askproai.de<br>
            • superadmin@askproai.de
        </div>
        
        <hr style="margin: 20px 0;">
        
        <p style="text-align: center;">
            <a href="/admin">Try Normal Login</a> | 
            <a href="/test">Test API</a>
        </p>
    </div>
</body>
</html>