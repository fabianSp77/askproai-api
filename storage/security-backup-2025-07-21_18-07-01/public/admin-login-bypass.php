<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Force login as admin
$admin = User::where('email', 'admin@askproai.de')->first();

if (!$admin) {
    die("Admin user not found!");
}

// Login using web guard (which Filament uses)
Auth::guard('web')->loginUsingId($admin->id, true);

// Set company context
app()->instance('current_company_id', $admin->company_id);

// Create session
session_start();
$_SESSION['_token'] = csrf_token();
$_SESSION['password_hash_web'] = $admin->password;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login Bypass</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; }
        .success { color: green; }
        .info { background: #f0f0f0; padding: 20px; margin: 20px 0; }
        a { display: inline-block; margin: 10px 10px 10px 0; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; }
    </style>
</head>
<body>
    <h1>Admin Login Bypass</h1>
    
    <div class="success">
        <h2>✅ Login Successful!</h2>
        <p>Logged in as: <?php echo htmlspecialchars($admin->email); ?></p>
        <p>User ID: <?php echo $admin->id; ?></p>
        <p>Company ID: <?php echo $admin->company_id; ?></p>
    </div>
    
    <div class="info">
        <h3>Session Info:</h3>
        <pre><?php 
        echo "Session ID: " . session_id() . "\n";
        echo "Auth Check (web): " . (Auth::guard('web')->check() ? 'YES' : 'NO') . "\n";
        echo "Auth User: " . (Auth::guard('web')->user() ? Auth::guard('web')->user()->email : 'None') . "\n";
        ?></pre>
    </div>
    
    <h3>Try accessing these pages:</h3>
    <a href="/admin">Admin Dashboard</a>
    <a href="/admin/calls">Admin Calls</a>
    <a href="/admin/companies">Admin Companies</a>
    <a href="/emergency-login">Emergency Login</a>
    
    <h3>Alternative:</h3>
    <a href="/business">Business Portal (Working)</a>
    <a href="/minimal-dashboard.php?uid=41">Minimal Dashboard (Working)</a>
</body>
</html>