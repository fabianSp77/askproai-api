<?php
// Initialize Laravel application
require_once __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Handle the request through Laravel
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Now we're in Laravel context
?>
<!DOCTYPE html>
<html>
<head>
    <title>Portal Login - Final Solution</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f3f4f6; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #059669; background: #d1fae5; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { color: #dc2626; background: #fee2e2; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { color: #2563eb; background: #dbeafe; padding: 15px; border-radius: 5px; margin: 10px 0; }
        button { background: #3b82f6; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; font-size: 16px; }
        button:hover { background: #2563eb; }
        .btn-success { background: #10b981; }
        .btn-success:hover { background: #059669; }
        h1 { color: #1f2937; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Portal Login - Final Working Solution</h1>
        
        <?php
        // Check current authentication status
        $portalAuth = auth()->guard('portal')->check();
        $webAuth = auth()->guard('web')->check();
        
        if ($portalAuth) {
            $user = auth()->guard('portal')->user();
            echo '<div class="success">';
            echo '<h2>✅ Already Logged In!</h2>';
            echo '<p>You are authenticated as: <strong>' . $user->email . '</strong></p>';
            echo '<p>Company ID: ' . $user->company_id . '</p>';
            echo '<button onclick="window.location.href=\'/business/dashboard\'">Go to Dashboard</button>';
            echo '</div>';
        } else {
            echo '<div class="info">';
            echo '<h2>Not Logged In</h2>';
            echo '<p>Click the button below to login as demo@askproai.de</p>';
            echo '</div>';
            
            // Login button
            echo '<form method="POST" action="?action=login">';
            echo '<input type="hidden" name="_token" value="' . csrf_token() . '">';
            echo '<button type="submit" class="btn-success">Login as Demo User</button>';
            echo '</form>';
        }
        
        // Handle login action
        if (request()->get('action') === 'login' && request()->isMethod('POST')) {
            // Clear any existing sessions
            session()->forget('errors');
            session()->forget('_old_input');
            session()->flush();
            
            // Find demo user
            $user = \App\Models\PortalUser::withoutGlobalScopes()
                ->where('email', 'demo@askproai.de')
                ->where('is_active', 1)
                ->first();
                
            if ($user) {
                // Login using portal guard
                auth()->guard('portal')->login($user);
                session()->regenerate();
                session()->save();
                
                echo '<div class="success">';
                echo '<h2>✅ Login Successful!</h2>';
                echo '<p>Redirecting to dashboard...</p>';
                echo '</div>';
                
                echo '<script>setTimeout(function() { window.location.href = "/business/dashboard"; }, 1000);</script>';
            } else {
                echo '<div class="error">Demo user not found!</div>';
            }
        }
        ?>
        
        <div class="info" style="margin-top: 30px;">
            <h3>Session Information:</h3>
            <p><strong>Session ID:</strong> <?php echo session()->getId(); ?></p>
            <p><strong>Session Name:</strong> <?php echo session()->getName(); ?></p>
            <p><strong>Portal Auth:</strong> <?php echo $portalAuth ? 'YES' : 'NO'; ?></p>
            <p><strong>Web Auth:</strong> <?php echo $webAuth ? 'YES' : 'NO'; ?></p>
        </div>
        
        <div style="margin-top: 20px;">
            <button onclick="window.location.href='/business/login'">Go to Login Page</button>
            <button onclick="window.location.href='/business/dashboard'">Try Dashboard</button>
        </div>
    </div>
</body>
</html>
<?php
// Terminate Laravel request handling
$kernel->terminate($request, $response);
?>