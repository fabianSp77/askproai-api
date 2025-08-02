<?php
/**
 * Business Login Bypass - Direkt zum Dashboard
 */

// Laravel Bootstrap
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Login demo user
$user = \App\Models\PortalUser::withoutGlobalScopes()
    ->where('email', 'demo@askproai.de')
    ->first();

if (!$user) {
    die('Demo user not found!');
}

// Create session
\Illuminate\Support\Facades\Auth::guard('portal')->login($user, true);
session(['portal_authenticated' => true]);
session(['portal_user_id' => $user->id]);
session(['portal_company_id' => $user->company_id]);
session()->regenerate();
session()->save();

// Set a special flag
session(['bypass_auth_check' => true]);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Login Bypass - Redirecting...</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: #f5f5f5;
        }
        .container {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #1890ff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <h2>✅ Login erfolgreich!</h2>
        <p>Weiterleitung zum Dashboard...</p>
        <p style="color: #666; font-size: 14px;">
            Session ID: <?php echo substr(session()->getId(), 0, 20); ?>...<br>
            User: <?php echo $user->email; ?>
        </p>
    </div>
    
    <script>
        // Set auth data for React
        const userData = <?php echo json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'company_id' => $user->company_id,
            'role' => 'user'
        ]); ?>;
        
        // Store in localStorage
        localStorage.setItem('portal_user', JSON.stringify(userData));
        localStorage.setItem('auth_token', 'bypass-<?php echo session()->getId(); ?>');
        localStorage.setItem('portal_session_id', '<?php echo session()->getId(); ?>');
        localStorage.setItem('bypass_active', 'true');
        
        // Remove demo mode
        localStorage.removeItem('demo_mode');
        
        console.log('✅ Auth data set:', userData);
        
        // Redirect to business portal
        setTimeout(() => {
            window.location.href = '/business';
        }, 1500);
    </script>
</body>
</html>