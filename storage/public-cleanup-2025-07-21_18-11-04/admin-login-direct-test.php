<?php
// Direct login test for admin panel
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

// Force login for testing
if (isset($_GET['login'])) {
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    if ($user) {
        Auth::login($user);
        session()->save();
        header('Location: /admin');
        exit;
    }
}

$isLoggedIn = Auth::check();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login Direct Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; }
        .status { padding: 20px; margin: 20px 0; border-radius: 5px; }
        .success { background: #e8f5e9; color: #2e7d32; }
        .error { background: #ffebee; color: #c62828; }
        .info { background: #e3f2fd; color: #1565c0; }
        button { padding: 10px 20px; margin: 10px; cursor: pointer; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Admin Login Direct Test</h1>
    
    <div class="status <?php echo $isLoggedIn ? 'success' : 'error'; ?>">
        <h2>Current Status:</h2>
        <p>Logged In: <?php echo $isLoggedIn ? 'YES ✅' : 'NO ❌'; ?></p>
        <?php if ($isLoggedIn): ?>
            <p>User: <?php echo Auth::user()->email; ?></p>
            <p>User ID: <?php echo Auth::user()->id; ?></p>
        <?php endif; ?>
    </div>
    
    <div class="status info">
        <h2>Session Info:</h2>
        <pre><?php
        echo "Session ID: " . session()->getId() . "\n";
        echo "Session Driver: " . config('session.driver') . "\n";
        echo "Session Cookie: " . config('session.cookie') . "\n";
        echo "Session Domain: " . (config('session.domain') ?: '(not set)') . "\n";
        echo "Secure Cookie: " . (config('session.secure') ? 'Yes' : 'No') . "\n";
        echo "SameSite: " . config('session.same_site') . "\n";
        ?></pre>
    </div>
    
    <div class="status info">
        <h2>Cookie Debug:</h2>
        <pre><?php
        echo "Cookies received:\n";
        print_r($_COOKIE);
        echo "\n\nSession data:\n";
        print_r(session()->all());
        ?></pre>
    </div>
    
    <?php if (!$isLoggedIn): ?>
        <div>
            <h2>Actions:</h2>
            <button onclick="window.location.href='?login=1'">Force Login as Demo User</button>
            <button onclick="window.location.href='/admin/login'">Go to Normal Login</button>
        </div>
    <?php else: ?>
        <div>
            <h2>Test Admin Pages:</h2>
            <button onclick="window.open('/admin', '_blank')">Open Admin Dashboard</button>
            <button onclick="window.open('/admin/calls', '_blank')">Open Calls</button>
            <button onclick="window.open('/admin/appointments', '_blank')">Open Appointments</button>
        </div>
    <?php endif; ?>
    
    <div style="margin-top: 40px;">
        <h3>Instructions:</h3>
        <ol>
            <li>If not logged in, click "Force Login as Demo User"</li>
            <li>This will log you in and redirect to /admin</li>
            <li>If the admin panel still redirects to login, there's a deeper issue</li>
        </ol>
    </div>
</body>
</html>