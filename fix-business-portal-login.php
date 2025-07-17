<?php
// FIX BUSINESS PORTAL LOGIN - Direkte Lösung

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "=== FIXING BUSINESS PORTAL LOGIN ===\n\n";

// 1. Fix .env configuration
echo "1. Checking .env configuration...\n";
$envFile = __DIR__ . '/.env';
$envContent = file_get_contents($envFile);

// Add SESSION_DOMAIN if missing
if (!str_contains($envContent, 'SESSION_DOMAIN=')) {
    echo "   - Adding SESSION_DOMAIN=.askproai.de\n";
    $envContent .= "\n# Session Configuration\nSESSION_DOMAIN=.askproai.de\n";
    file_put_contents($envFile, $envContent);
} else {
    echo "   - SESSION_DOMAIN already configured\n";
}

// Change same_site to lax
if (!str_contains($envContent, 'SESSION_SAME_SITE=')) {
    echo "   - Adding SESSION_SAME_SITE=lax\n";
    $envContent = file_get_contents($envFile);
    $envContent .= "SESSION_SAME_SITE=lax\n";
    file_put_contents($envFile, $envContent);
}

// 2. Clear all caches
echo "\n2. Clearing all caches...\n";
exec('php artisan config:clear');
exec('php artisan cache:clear');
exec('php artisan view:clear');
exec('php artisan route:clear');

// 3. Create/Update test users with WORKING passwords
echo "\n3. Creating/Updating test users...\n";
$users = [
    [
        'email' => 'admin@askproai.de',
        'password' => 'Admin123!',
        'name' => 'Admin User',
        'role' => 'admin'
    ],
    [
        'email' => 'test@askproai.de',
        'password' => 'Test123!',
        'name' => 'Test User',
        'role' => 'admin'
    ],
    [
        'email' => 'demo@askproai.de',
        'password' => 'Demo123!',
        'name' => 'Demo User',
        'role' => 'admin'
    ]
];

foreach ($users as $userData) {
    $user = PortalUser::withoutGlobalScopes()->where('email', $userData['email'])->first();
    
    if ($user) {
        // Update existing user
        $user->password = Hash::make($userData['password']);
        $user->is_active = true;
        $user->save();
        echo "   ✅ Updated: {$userData['email']} / {$userData['password']}\n";
    } else {
        // Create new user
        PortalUser::create([
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
            'name' => $userData['name'],
            'company_id' => 1,
            'is_active' => true,
            'role' => $userData['role'],
            'permissions' => json_encode([
                'calls.view_all' => true,
                'billing.view' => true,
                'billing.manage' => true,
                'appointments.view_all' => true,
                'customers.view_all' => true
            ])
        ]);
        echo "   ✅ Created: {$userData['email']} / {$userData['password']}\n";
    }
}

// 4. Test login functionality
echo "\n4. Testing login functionality...\n";
$testEmail = 'test@askproai.de';
$testPassword = 'Test123!';

$user = PortalUser::withoutGlobalScopes()->where('email', $testEmail)->first();
if ($user && Hash::check($testPassword, $user->password)) {
    echo "   ✅ Password verification successful!\n";
} else {
    echo "   ❌ Password verification FAILED!\n";
}

// 5. Clear session files
echo "\n5. Clearing old session files...\n";
$sessionPath = storage_path('framework/sessions');
$files = glob($sessionPath . '/*');
$count = count($files);
foreach ($files as $file) {
    if (is_file($file)) {
        unlink($file);
    }
}
echo "   - Deleted $count session files\n";

// 6. Rebuild configuration cache
echo "\n6. Rebuilding configuration cache...\n";
exec('php artisan config:cache');

echo "\n=== DONE! ===\n\n";
echo "Login-Daten:\n";
echo "-------------\n";
foreach ($users as $userData) {
    echo "Email: {$userData['email']}\n";
    echo "Passwort: {$userData['password']}\n\n";
}

echo "Jetzt testen auf: https://api.askproai.de/business/login\n\n";

echo "Falls immer noch Probleme:\n";
echo "1. Browser Cache/Cookies löschen\n";
echo "2. Inkognito-Fenster verwenden\n";
echo "3. PHP-FPM neustarten: sudo systemctl restart php8.3-fpm\n";