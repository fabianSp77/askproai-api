<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Portal Test Results</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; }
        .box { border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .button { display: inline-block; padding: 10px 20px; margin: 10px 5px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px; }
        .button:hover { background: #2563eb; }
        .button.green { background: #10b981; }
        .button.green:hover { background: #059669; }
    </style>
</head>
<body>
    <h1>✨ Portal Status After Fix</h1>
    
    <div class="box">
        <h2>System Status</h2>
        <?php
        try {
            // Test DB
            DB::connection()->getPdo();
            echo '<p class="success">✅ Database: Connected</p>';
            
            // Test Redis
            Redis::connection()->ping();
            echo '<p class="success">✅ Redis: Connected</p>';
            
            // Test Session
            $driver = config('session.driver');
            echo '<p class="success">✅ Session Driver: ' . $driver . '</p>';
            
        } catch (Exception $e) {
            echo '<p class="error">❌ Error: ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>
    
    <div class="box">
        <h2>Quick Access Links</h2>
        <a href="/admin/login" class="button">Admin Login</a>
        <a href="/admin/appointments" class="button">Admin Appointments</a>
        <a href="/business/login" class="button green">Business Portal</a>
        <a href="/health.php" class="button" style="background: #6366f1;">Health Check</a>
    </div>
    
    <div class="box">
        <h2>Test Business Portal Login</h2>
        <p><strong>Credentials:</strong></p>
        <p>Email: demo@example.com<br>Password: password</p>
        
        <form method="POST" action="/business/login" style="margin-top: 20px;">
            <?php echo csrf_field(); ?>
            <input type="email" name="email" value="demo@example.com" style="padding: 8px; margin: 5px;">
            <input type="password" name="password" value="password" style="padding: 8px; margin: 5px;">
            <button type="submit" style="padding: 8px 16px; background: #10b981; color: white; border: none; cursor: pointer;">Test Login</button>
        </form>
    </div>
    
    <div class="box">
        <h2>Appointment Data Check</h2>
        <?php
        $count = \App\Models\Appointment::count();
        echo "<p>Total appointments in system: <strong>$count</strong></p>";
        
        if (auth()->check()) {
            $user = auth()->user();
            echo "<p>Logged in as: {$user->email}</p>";
        } else {
            echo "<p class='warning'>Not logged in - login to see appointments</p>";
        }
        ?>
    </div>
    
    <div class="box">
        <h2>Instructions if Still Having Issues</h2>
        <ol>
            <li>Clear your browser cache completely (Ctrl+Shift+Delete)</li>
            <li>Clear cookies for this domain</li>
            <li>Try in an incognito/private window</li>
            <li>Check browser console for JavaScript errors (F12)</li>
        </ol>
    </div>
</body>
</html>