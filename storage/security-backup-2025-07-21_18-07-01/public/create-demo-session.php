<?php
// Create a demo session for testing
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

// Find demo user
$user = \App\Models\User::where('email', 'demo@askproai.de')->first();

if (!$user) {
    die("Demo user not found. Creating one...\n");
}

// Login the user
Auth::login($user);

// Set session data
session(['authenticated' => true]);
session(['user_id' => $user->id]);
session()->save();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Demo Session Created</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; text-align: center; }
        .success { color: green; padding: 20px; background: #e8f5e9; border-radius: 5px; }
        .links { margin-top: 30px; }
        .links a { display: inline-block; margin: 10px; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="success">
        <h1>✅ Demo Session Created Successfully!</h1>
        <p>You are now logged in as: <?php echo $user->email; ?></p>
        <p>User ID: <?php echo $user->id; ?></p>
        <p>Company: <?php echo $user->company->name ?? 'N/A'; ?></p>
        <p>Session ID: <?php echo session()->getId(); ?></p>
    </div>
    
    <div class="links">
        <h2>Test These Pages:</h2>
        <a href="/admin" target="_blank">Admin Dashboard</a>
        <a href="/admin/calls" target="_blank">Calls</a>
        <a href="/admin/appointments" target="_blank">Appointments</a>
        <a href="/admin/customers" target="_blank">Customers</a>
    </div>
    
    <div style="margin-top: 30px; padding: 20px; background: #f5f5f5;">
        <h3>Session Debug Info:</h3>
        <pre style="text-align: left;"><?php
        echo "Auth check: " . (Auth::check() ? 'YES' : 'NO') . "\n";
        echo "Session driver: " . config('session.driver') . "\n";
        echo "Session cookie: " . config('session.cookie') . "\n";
        echo "All session data:\n";
        print_r(session()->all());
        ?></pre>
    </div>
</body>
</html>