<?php
// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

// Handle POST login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Use withoutGlobalScopes to bypass tenant filtering
    $user = PortalUser::withoutGlobalScopes()->where('email', $email)->first();
    
    if ($user) {
        if (Hash::check($password, $user->password)) {
            // Login successful
            Auth::guard('portal')->login($user);
            session(['portal_user_id' => $user->id]);
            session(['portal_authenticated' => true]);
            session()->regenerate();
            
            // Redirect to business portal
            header('Location: /business');
            exit;
        } else {
            $error = 'Invalid password for ' . $email;
        }
    } else {
        $error = 'User not found: ' . $email;
    }
}
?>
<\!DOCTYPE html>
<html>
<head>
    <title>Business Portal Login</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, sans-serif; 
            background: #f5f5f5; 
            margin: 0; 
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 400px;
        }
        h2 {
            margin: 0 0 30px 0;
            color: #333;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #666;
            font-weight: 500;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover {
            background: #2563eb;
        }
        .error {
            color: #ef4444;
            margin-bottom: 20px;
            text-align: center;
        }
        .info {
            background: #e0f2fe;
            border: 1px solid #0284c7;
            color: #0c4a6e;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Business Portal Login</h2>
        
        <div class="info">
            <strong>Demo Credentials:</strong><br>
            Email: demo@askproai.de<br>
            Password: demo123
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="demo@askproai.de" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" value="demo123" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
