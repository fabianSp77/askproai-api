<?php
// Check Authentication Status

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Auth Status Check</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .status { padding: 20px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>Authentication Status Check</h1>
    
    <div class="status <?= Auth::check() ? 'success' : 'error' ?>">
        <h2>Auth Status: <?= Auth::check() ? '✅ LOGGED IN' : '❌ NOT LOGGED IN' ?></h2>
    </div>
    
    <?php if (Auth::check()): ?>
        <div class="info">
            <h3>User Information:</h3>
            <table>
                <tr><th>Field</th><th>Value</th></tr>
                <tr><td>ID</td><td><?= Auth::id() ?></td></tr>
                <tr><td>Name</td><td><?= Auth::user()->name ?></td></tr>
                <tr><td>Email</td><td><?= Auth::user()->email ?></td></tr>
                <tr><td>Guard</td><td><?= Auth::getDefaultDriver() ?></td></tr>
            </table>
        </div>
    <?php endif; ?>
    
    <div class="info">
        <h3>Session Information:</h3>
        <table>
            <tr><th>Field</th><th>Value</th></tr>
            <tr><td>Session ID</td><td><?= Session::getId() ?></td></tr>
            <tr><td>Session Driver</td><td><?= config('session.driver') ?></td></tr>
            <tr><td>CSRF Token</td><td><?= substr(Session::token(), 0, 20) ?>...</td></tr>
            <tr><td>Has Web Auth</td><td><?= Session::has('password_hash_web') ? 'Yes' : 'No' ?></td></tr>
        </table>
    </div>
    
    <div class="info">
        <h3>Cookie Information:</h3>
        <table>
            <tr><th>Cookie</th><th>Value</th></tr>
            <?php foreach ($_COOKIE as $name => $value): ?>
                <tr>
                    <td><?= htmlspecialchars($name) ?></td>
                    <td><?= htmlspecialchars(substr($value, 0, 50)) ?>...</td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <hr>
    
    <h3>Quick Actions:</h3>
    <p>
        <a href="/admin" style="padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">Go to Admin Panel</a>
        <a href="/force-admin-login.php" style="padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;">Force Login</a>
        <a href="/admin/logout" style="padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;">Logout</a>
    </p>
</body>
</html>