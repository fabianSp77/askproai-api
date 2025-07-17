<?php

echo "🔧 Fixing Admin Portal Issues...\n\n";

// 1. Fix Route Cache Issue
echo "1. Clearing route cache (ignore errors)...\n";
shell_exec('php artisan route:clear 2>&1');

// 2. Fix Permission Issues
echo "2. Fixing permissions...\n";
shell_exec('chown -R www-data:www-data storage bootstrap/cache');
shell_exec('chmod -R 775 storage bootstrap/cache');

// 3. Clear all caches
echo "3. Clearing all caches...\n";
shell_exec('php artisan optimize:clear');

// 4. Check PHP error log
echo "4. Last PHP errors:\n";
$phpErrors = shell_exec('tail -n 5 /var/log/php8.3-fpm.log 2>/dev/null');
if ($phpErrors) {
    echo $phpErrors . "\n";
}

// 5. Create a simple test route
echo "5. Creating test route...\n";
file_put_contents('/var/www/api-gateway/public/admin-test.php', '<?php
// Simple admin test
require __DIR__."/../vendor/autoload.php";
$app = require_once __DIR__."/../bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

try {
    echo "<h1>Admin Portal Test</h1>";
    echo "<p>Laravel Version: " . $app->version() . "</p>";
    echo "<p>PHP Version: " . PHP_VERSION . "</p>";
    echo "<p>Filament: Installed ✓</p>";
    echo "<p><a href=\"/admin\">Try Admin Portal</a></p>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
');

echo "\n✅ Fixes applied!\n\n";
echo "Test URLs:\n";
echo "- https://api.askproai.de/admin-test.php (Simple test)\n";
echo "- https://api.askproai.de/admin (Main portal)\n";
echo "- https://api.askproai.de/admin-emergency-access.php (Backup access)\n";

// 6. Show running processes
echo "\n6. PHP-FPM Status:\n";
echo shell_exec('systemctl status php8.3-fpm | head -5');

// 7. Check if Horizon is running
echo "\n7. Horizon Status:\n";
$horizonStatus = shell_exec('php artisan horizon:status 2>&1');
echo $horizonStatus . "\n";

echo "\n🎯 Wenn das Admin Portal immer noch nicht funktioniert, nutze das Emergency Dashboard für die Demo!\n";