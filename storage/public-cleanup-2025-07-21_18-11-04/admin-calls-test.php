<?php
session_start();

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Handle the request to initialize Laravel
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Auto-login as admin
\Illuminate\Support\Facades\Auth::guard('web')->loginUsingId(6); // Admin user (admin@askproai.de)

// Check if logged in
$user = \Illuminate\Support\Facades\Auth::user();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Calls Test</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        iframe { width: 100%; height: 600px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>Admin Calls Page Test</h1>
    
    <div class="status info">
        <strong>Login Status:</strong> 
        <?php if ($user): ?>
            ✅ Logged in as: <?= htmlspecialchars($user->email) ?> (ID: <?= $user->id ?>)
        <?php else: ?>
            ❌ Not logged in
        <?php endif; ?>
    </div>
    
    <?php if ($user): ?>
        <div class="status success">
            <strong>User Details:</strong><br>
            - Name: <?= htmlspecialchars($user->name) ?><br>
            - Role: <?= $user->roles->pluck('name')->join(', ') ?: 'No roles' ?><br>
            - Company: <?= $user->company ? htmlspecialchars($user->company->name) : 'No company' ?>
        </div>
        
        <h2>Direct Access Links:</h2>
        <ul>
            <li><a href="/admin" target="_blank">Admin Dashboard</a></li>
            <li><a href="/admin/calls" target="_blank">Admin Calls</a></li>
            <li><a href="/admin/appointments" target="_blank">Admin Appointments</a></li>
            <li><a href="/admin/customers" target="_blank">Admin Customers</a></li>
        </ul>
        
        <h2>Admin Calls Page (Embedded):</h2>
        <iframe src="/admin/calls" id="callsFrame"></iframe>
        
        <script>
            // Auto-refresh the iframe after 2 seconds to ensure session is active
            setTimeout(() => {
                document.getElementById('callsFrame').src = '/admin/calls';
            }, 2000);
        </script>
    <?php else: ?>
        <div class="status error">
            <strong>Error:</strong> Could not auto-login. Please check the admin user exists in the database.
        </div>
        
        <h3>Manual Login:</h3>
        <p>Try logging in manually:</p>
        <ul>
            <li><a href="/admin/login" target="_blank">Admin Login Page</a></li>
            <li>Email: admin@askproai.de</li>
            <li>Password: [Check database or .env]</li>
        </ul>
    <?php endif; ?>
    
    <hr>
    <p><small>This test page auto-logs you in as admin user (ID: 1)</small></p>
</body>
</html>

<?php
// Terminate the kernel
$kernel->terminate($request, $response);