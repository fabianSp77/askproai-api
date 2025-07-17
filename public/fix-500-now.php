<?php
// Emergency 500 error fix
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Emergency 500 Fix</h1>";

// 1. Clear all caches
echo "<h2>Clearing Caches...</h2>";
exec('cd /var/www/api-gateway && php artisan optimize:clear 2>&1', $output);
echo "<pre>" . implode("\n", $output) . "</pre>";

// 2. Fix permissions
echo "<h2>Fixing Permissions...</h2>";
exec('sudo chown -R www-data:www-data /var/www/api-gateway/storage /var/www/api-gateway/bootstrap/cache 2>&1', $output2);
exec('sudo chmod -R 775 /var/www/api-gateway/storage /var/www/api-gateway/bootstrap/cache 2>&1', $output3);
echo "<p>Permissions fixed</p>";

// 3. Create cache directories if missing
$dirs = [
    '/var/www/api-gateway/storage/framework/cache/data',
    '/var/www/api-gateway/storage/framework/sessions',
    '/var/www/api-gateway/storage/framework/views',
    '/var/www/api-gateway/storage/logs',
    '/var/www/api-gateway/bootstrap/cache'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
        echo "<p>Created: $dir</p>";
    }
}

// 4. Test bootstrap
echo "<h2>Testing Laravel Bootstrap...</h2>";
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo "<p class='success'>✅ Laravel bootstrapped successfully</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Bootstrap error: " . $e->getMessage() . "</p>";
}

// 5. Restart services
echo "<h2>Restarting Services...</h2>";
exec('sudo systemctl restart php8.3-fpm 2>&1', $output4);
exec('sudo systemctl restart nginx 2>&1', $output5);
echo "<p>Services restarted</p>";

echo "<h2>Complete!</h2>";
echo "<p><a href='/admin'>Try Admin Panel</a> | <a href='/business'>Try Business Portal</a></p>";

?>
<style>
body { font-family: Arial, sans-serif; margin: 40px; }
.success { color: green; }
.error { color: red; }
pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }
a { margin: 0 10px; }
</style>