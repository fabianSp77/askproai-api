<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Fixing Login Issue\n";
echo "==================\n\n";

// Read current .env file
$envPath = __DIR__ . '/.env';
$envContent = file_get_contents($envPath);

// Check current setting
if (strpos($envContent, 'SESSION_SECURE_COOKIE=true') !== false) {
    echo "Found SESSION_SECURE_COOKIE=true\n";
    echo "Changing to SESSION_SECURE_COOKIE=false for development/testing...\n\n";
    
    // Replace the setting
    $newContent = str_replace('SESSION_SECURE_COOKIE=true', 'SESSION_SECURE_COOKIE=false', $envContent);
    
    // Backup current .env
    file_put_contents($envPath . '.backup-' . date('Y-m-d-H-i-s'), $envContent);
    
    // Write new content
    file_put_contents($envPath, $newContent);
    
    echo "✅ Updated .env file\n";
    echo "✅ Created backup of original .env\n\n";
    
    // Clear config cache
    echo "Clearing configuration cache...\n";
    shell_exec('php artisan config:clear');
    shell_exec('php artisan cache:clear');
    
    echo "\n✅ Configuration cache cleared\n\n";
    
    echo "IMPORTANT: Login should now work when accessing via HTTP.\n";
    echo "For production, you should:\n";
    echo "1. Use HTTPS (recommended)\n";
    echo "2. Set SESSION_SECURE_COOKIE=true when using HTTPS\n";
} else {
    echo "SESSION_SECURE_COOKIE is already set to false or not found.\n";
    echo "Login should work via HTTP.\n";
}