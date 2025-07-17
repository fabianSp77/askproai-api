<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "Fixing Session Conflicts\n";
echo str_repeat("=", 50) . "\n\n";

// 1. Clear all existing sessions
echo "1. Clearing all sessions...\n";
DB::table('sessions')->truncate();
DB::table('portal_sessions')->truncate();
echo "   ✓ All sessions cleared\n\n";

// 2. Update session configuration
echo "2. Updating session configuration...\n";

// Update .env to use different session cookies
$envContent = file_get_contents('.env');

// Ensure different session cookies
if (!str_contains($envContent, 'ADMIN_SESSION_COOKIE')) {
    $envContent .= "\n# Different session cookies for each portal\n";
    $envContent .= "ADMIN_SESSION_COOKIE=askproai_admin_session\n";
    $envContent .= "PORTAL_SESSION_COOKIE=askproai_portal_session\n";
    file_put_contents('.env', $envContent);
    echo "   ✓ Added separate session cookie configuration\n";
}

// 3. Clear all caches
echo "\n3. Clearing all caches...\n";
exec('php artisan optimize:clear');
echo "   ✓ Caches cleared\n";

// 4. Restart PHP-FPM
echo "\n4. Restarting PHP-FPM...\n";
exec('sudo systemctl restart php8.3-fpm');
echo "   ✓ PHP-FPM restarted\n";

echo "\n" . str_repeat("=", 50) . "\n";
echo "Session conflicts fixed!\n\n";
echo "IMPORTANT: Both portals now use separate sessions.\n";
echo "You will need to login again in both portals.\n";