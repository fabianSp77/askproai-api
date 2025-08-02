<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

?>
<!DOCTYPE html>
<html>
<head>
    <title>500 Error Diagnosis</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>500 Error Diagnosis</h1>
    
    <?php
    try {
        // Test 1: Database
        echo "<h2>1. Database Connection</h2>";
        DB::connection()->getPdo();
        echo '<p class="success">✅ Database connected</p>';
        
        // Test 2: Redis
        echo "<h2>2. Redis Connection</h2>";
        Redis::connection()->ping();
        echo '<p class="success">✅ Redis connected</p>';
        
        // Test 3: Storage permissions
        echo "<h2>3. Storage Permissions</h2>";
        $dirs = [
            storage_path('app'),
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            base_path('bootstrap/cache'),
        ];
        
        foreach ($dirs as $dir) {
            if (is_writable($dir)) {
                echo '<p class="success">✅ ' . $dir . ' is writable</p>';
            } else {
                echo '<p class="error">❌ ' . $dir . ' is NOT writable</p>';
            }
        }
        
        // Test 4: Check error logs
        echo "<h2>4. Recent Errors</h2>";
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $lines = array_slice(file($logFile), -20);
            $errors = [];
            foreach ($lines as $line) {
                if (stripos($line, 'error') !== false || stripos($line, 'exception') !== false) {
                    $errors[] = $line;
                }
            }
            if (empty($errors)) {
                echo '<p class="success">No recent errors in log</p>';
            } else {
                echo '<pre>' . htmlspecialchars(implode("\n", array_slice($errors, -5))) . '</pre>';
            }
        }
        
        // Test 5: Session
        echo "<h2>5. Session Configuration</h2>";
        echo '<pre>';
        echo "Driver: " . config('session.driver') . "\n";
        echo "Path: " . session_save_path() . "\n";
        echo "Session ID: " . session_id() . "\n";
        echo '</pre>';
        
        // Test 6: Routes
        echo "<h2>6. Route Test</h2>";
        try {
            $routes = app('router')->getRoutes();
            echo '<p class="success">✅ Routes loaded: ' . count($routes) . ' routes</p>';
        } catch (Exception $e) {
            echo '<p class="error">❌ Route error: ' . $e->getMessage() . '</p>';
        }
        
        // Test 7: Admin login page
        echo "<h2>7. Admin Login Test</h2>";
        try {
            $response = app()->handle(
                \Illuminate\Http\Request::create('/admin/login', 'GET')
            );
            echo '<p>Admin login response: ' . $response->getStatusCode() . '</p>';
            if ($response->getStatusCode() >= 500) {
                echo '<p class="error">❌ Admin login returns 500</p>';
                if ($response->exception) {
                    echo '<pre>' . htmlspecialchars($response->exception->getMessage()) . '</pre>';
                }
            } else {
                echo '<p class="success">✅ Admin login accessible</p>';
            }
        } catch (Exception $e) {
            echo '<p class="error">❌ Error: ' . $e->getMessage() . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        }
        
    } catch (Exception $e) {
        echo '<div class="error">';
        echo '<h2>Fatal Error</h2>';
        echo '<p>' . $e->getMessage() . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</div>';
    }
    ?>
    
    <h2>Quick Fix Actions</h2>
    <p>Run these commands:</p>
    <pre>
sudo chown -R www-data:www-data /var/www/api-gateway/storage /var/www/api-gateway/bootstrap/cache
sudo chmod -R 775 /var/www/api-gateway/storage /var/www/api-gateway/bootstrap/cache
php artisan optimize:clear
php artisan config:cache
sudo systemctl restart php8.3-fpm
    </pre>
</body>
</html>