<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../vendor/autoload.php';

try {
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "<h2>Emergency Portal Fix</h2>";
    
    // 1. Clear all caches
    echo "<h3>1. Clearing all caches...</h3>";
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    echo "✅ All caches cleared<br><br>";
    
    // 2. Test database connection
    echo "<h3>2. Testing database connection...</h3>";
    $dbTest = \DB::connection()->getPdo();
    echo "✅ Database connection OK<br><br>";
    
    // 3. Test Redis connection
    echo "<h3>3. Testing Redis connection...</h3>";
    $redisTest = \Illuminate\Support\Facades\Redis::connection()->ping();
    echo "✅ Redis connection OK<br><br>";
    
    // 4. Check session configuration
    echo "<h3>4. Session configuration:</h3>";
    echo "Driver: " . config('session.driver') . "<br>";
    echo "Domain: " . config('session.domain') . "<br>";
    echo "Path: " . config('session.path') . "<br><br>";
    
    // 5. Create symlinks if missing
    echo "<h3>5. Checking storage symlinks...</h3>";
    if (!file_exists(public_path('storage'))) {
        \Illuminate\Support\Facades\Artisan::call('storage:link');
        echo "✅ Storage link created<br>";
    } else {
        echo "✅ Storage link exists<br>";
    }
    echo "<br>";
    
    // 6. Test authentication
    echo "<h3>6. Testing authentication...</h3>";
    $user = \App\Models\User::find(6);
    if ($user) {
        \Illuminate\Support\Facades\Auth::login($user);
        echo "✅ Admin user authenticated<br>";
        echo "User: {$user->email}<br>";
        echo "Company: {$user->company_id}<br><br>";
    }
    
    // 7. Quick links
    echo "<h3>7. Quick Links:</h3>";
    echo "<a href='/admin/login'>Admin Login</a> | ";
    echo "<a href='/admin/appointments'>Admin Appointments</a> | ";
    echo "<a href='/business/login'>Business Portal Login</a><br><br>";
    
    // 8. Fix file permissions
    echo "<h3>8. Fixing permissions...</h3>";
    echo "Please run: <code>sudo chown -R www-data:www-data storage bootstrap/cache</code><br>";
    echo "And: <code>sudo chmod -R 775 storage bootstrap/cache</code><br><br>";
    
    echo "<hr>";
    echo "✅ <strong>Emergency fix completed!</strong><br>";
    echo "If you still see 500 errors, check the Laravel log: <code>tail -f storage/logs/laravel.log</code>";
    
} catch (\Exception $e) {
    echo "<h2>❌ ERROR FOUND!</h2>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString();
    echo "</pre>";
}