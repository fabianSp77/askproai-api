<?php
// EMERGENCY FIX - Page Expired / CSRF Issues

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== EMERGENCY CSRF/SESSION FIX ===\n\n";

// 1. Check current session configuration
echo "1. Current Configuration:\n";
echo "   - SESSION_DOMAIN: " . config('session.domain') . "\n";
echo "   - SESSION_DRIVER: " . config('session.driver') . "\n";
echo "   - SESSION_SAME_SITE: " . config('session.same_site') . "\n";
echo "   - SESSION_SECURE: " . (config('session.secure') ? 'true' : 'false') . "\n";
echo "   - APP_URL: " . config('app.url') . "\n";

// 2. Update .env with correct settings
echo "\n2. Updating .env configuration...\n";
$envPath = __DIR__ . '/.env';
$env = file_get_contents($envPath);

// Ensure SESSION_DOMAIN is set correctly
if (!preg_match('/SESSION_DOMAIN=/', $env)) {
    $env .= "\nSESSION_DOMAIN=askproai.de\n";
    echo "   - Added SESSION_DOMAIN=askproai.de\n";
} else {
    $env = preg_replace('/SESSION_DOMAIN=.*/', 'SESSION_DOMAIN=askproai.de', $env);
    echo "   - Updated SESSION_DOMAIN=askproai.de\n";
}

// Change SESSION_SAME_SITE to lax
if (!preg_match('/SESSION_SAME_SITE=/', $env)) {
    $env .= "SESSION_SAME_SITE=lax\n";
    echo "   - Added SESSION_SAME_SITE=lax\n";
} else {
    $env = preg_replace('/SESSION_SAME_SITE=.*/', 'SESSION_SAME_SITE=lax', $env);
    echo "   - Updated SESSION_SAME_SITE=lax\n";
}

// Ensure SESSION_SECURE is false for testing
if (!preg_match('/SESSION_SECURE_COOKIE=/', $env)) {
    $env .= "SESSION_SECURE_COOKIE=false\n";
    echo "   - Added SESSION_SECURE_COOKIE=false\n";
}

file_put_contents($envPath, $env);

// 3. Clear EVERYTHING
echo "\n3. Clearing all caches and sessions...\n";
exec('rm -rf storage/framework/sessions/*');
exec('rm -rf storage/framework/cache/*');
exec('rm -rf storage/framework/views/*');
exec('rm -rf bootstrap/cache/*');
echo "   - Cleared all session files\n";
echo "   - Cleared all cache files\n";
echo "   - Cleared all view cache\n";
echo "   - Cleared bootstrap cache\n";

// 4. Regenerate app key (this will invalidate all sessions)
echo "\n4. Regenerating application key...\n";
exec('php artisan key:generate --force');
echo "   - New app key generated\n";

// 5. Create fresh admin user
echo "\n5. Creating fresh admin user...\n";
use App\Models\User;
use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;

// Admin panel user
$adminUser = User::where('email', 'admin@askproai.de')->first();
if (!$adminUser) {
    $adminUser = User::create([
        'name' => 'Admin',
        'email' => 'admin@askproai.de',
        'password' => Hash::make('Admin123!'),
        'company_id' => 1
    ]);
    echo "   ✅ Created admin panel user: admin@askproai.de / Admin123!\n";
} else {
    $adminUser->password = Hash::make('Admin123!');
    $adminUser->save();
    echo "   ✅ Updated admin panel user: admin@askproai.de / Admin123!\n";
}

// Business portal user
$portalUser = PortalUser::withoutGlobalScopes()->where('email', 'portal@askproai.de')->first();
if (!$portalUser) {
    $portalUser = PortalUser::create([
        'name' => 'Portal Admin',
        'email' => 'portal@askproai.de',
        'password' => Hash::make('Portal123!'),
        'company_id' => 1,
        'is_active' => true,
        'role' => 'admin',
        'permissions' => json_encode(['all' => true])
    ]);
    echo "   ✅ Created portal user: portal@askproai.de / Portal123!\n";
} else {
    $portalUser->password = Hash::make('Portal123!');
    $portalUser->is_active = true;
    $portalUser->save();
    echo "   ✅ Updated portal user: portal@askproai.de / Portal123!\n";
}

// 6. NO cache rebuild - let Laravel build it fresh
echo "\n6. Skipping cache rebuild - let Laravel handle it fresh\n";

echo "\n=== WICHTIG ===\n";
echo "1. Löschen Sie ALLE Cookies für askproai.de in Ihrem Browser\n";
echo "2. Verwenden Sie ein Inkognito/Private Fenster\n";
echo "3. Warten Sie 30 Sekunden bevor Sie sich einloggen\n\n";

echo "LOGIN DATEN:\n";
echo "-------------\n";
echo "Admin Panel: https://api.askproai.de/admin/login\n";
echo "Email: admin@askproai.de\n";
echo "Passwort: Admin123!\n\n";

echo "Business Portal: https://api.askproai.de/business/login\n";
echo "Email: portal@askproai.de\n";
echo "Passwort: Portal123!\n\n";

echo "DONE!\n";