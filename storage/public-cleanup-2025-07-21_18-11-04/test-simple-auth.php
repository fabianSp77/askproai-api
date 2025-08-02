<?php
// Simple auth test without Filament
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

// Test login
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    if ($user && \Illuminate\Support\Facades\Hash::check($_POST['password'], $user->password)) {
        Auth::login($user);
        session()->regenerate();
        echo json_encode(['success' => true, 'message' => 'Login successful']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
    exit;
}

// Test logout
if (isset($_GET['logout'])) {
    Auth::logout();
    session()->flush();
    header('Location: /test-simple-auth.php');
    exit;
}

$isLoggedIn = Auth::check();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Auth Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; }
        .status { padding: 20px; margin: 20px 0; border-radius: 5px; }
        .success { background: #e8f5e9; color: #2e7d32; }
        .error { background: #ffebee; color: #c62828; }
        .info { background: #e3f2fd; color: #1565c0; }
        input, button { display: block; width: 100%; margin: 10px 0; padding: 10px; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Simple Authentication Test</h1>
    
    <div class="status <?php echo $isLoggedIn ? 'success' : 'error'; ?>">
        <h2>Status:</h2>
        <?php if ($isLoggedIn): ?>
            <p>✅ Logged in as: <?php echo Auth::user()->email; ?></p>
            <p>User ID: <?php echo Auth::user()->id; ?></p>
            <p>Company: <?php echo Auth::user()->company->name ?? 'None'; ?></p>
            <p><a href="?logout=1">Logout</a></p>
        <?php else: ?>
            <p>❌ Not logged in</p>
        <?php endif; ?>
    </div>
    
    <?php if (!$isLoggedIn): ?>
    <div class="status info">
        <h2>Login Form:</h2>
        <form id="loginForm">
            <input type="email" id="email" value="demo@askproai.de" placeholder="Email">
            <input type="password" id="password" placeholder="Password">
            <button type="submit">Login</button>
        </form>
        <div id="result"></div>
    </div>
    <?php endif; ?>
    
    <div class="status info">
        <h2>Session Info:</h2>
        <pre><?php
        echo "Session ID: " . session()->getId() . "\n";
        echo "Session Driver: " . config('session.driver') . "\n";
        echo "Session Path: " . storage_path('framework/sessions') . "\n";
        echo "Session Files: " . count(glob(storage_path('framework/sessions/*'))) . "\n";
        echo "\nAll Session Data:\n";
        print_r(session()->all());
        ?></pre>
    </div>
    
    <?php if ($isLoggedIn): ?>
    <div class="status info">
        <h2>Test Filament Access:</h2>
        <p>Now that you're logged in with standard Laravel auth, try accessing:</p>
        <ul>
            <li><a href="/admin" target="_blank">Admin Panel</a></li>
            <li><a href="/admin/calls" target="_blank">Calls</a></li>
        </ul>
        <p>If these still redirect to login, then Filament has additional checks.</p>
    </div>
    <?php endif; ?>
    
    <script>
        document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const result = document.getElementById('result');
            result.innerHTML = 'Logging in...';
            
            const response = await fetch('/test-simple-auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'login',
                    email: document.getElementById('email').value,
                    password: document.getElementById('password').value
                })
            });
            
            const data = await response.json();
            if (data.success) {
                result.innerHTML = '<p style="color: green">Login successful! Reloading...</p>';
                setTimeout(() => location.reload(), 1000);
            } else {
                result.innerHTML = '<p style="color: red">Login failed: ' + data.message + '</p>';
            }
        });
    </script>
</body>
</html>