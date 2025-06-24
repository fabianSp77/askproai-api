<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Applying immediate connection pool fixes...\n";

// 1. Update .env file
$envFile = base_path('.env');
$envContent = file_get_contents($envFile);

// Disable persistent connections
$envContent = preg_replace('/^DB_PERSISTENT=.*/m', 'DB_PERSISTENT=false', $envContent);
if (!preg_match('/^DB_PERSISTENT=/m', $envContent)) {
    $envContent .= "\n# Connection Pool Fix\nDB_PERSISTENT=false\n";
}

// Add pool configuration
$poolConfig = [
    'DB_POOL_MAX=20',
    'DB_TIMEOUT=5',
    'DB_READ_TIMEOUT=30',
];

foreach ($poolConfig as $config) {
    [$key, $value] = explode('=', $config);
    if (!preg_match("/^{$key}=/m", $envContent)) {
        $envContent .= "{$config}\n";
    }
}

file_put_contents($envFile, $envContent);
echo "✅ Updated .env file\n";

// 2. Clear config cache
Artisan::call('config:clear');
echo "✅ Cleared configuration cache\n";

// 3. Kill long-running connections
try {
    DB::statement("
        SELECT CONCAT('KILL ', id, ';') 
        FROM information_schema.processlist 
        WHERE command = 'Sleep' 
        AND time > 120
    ");
    echo "✅ Cleaned up idle connections\n";
} catch (\Exception $e) {
    echo "⚠️  Could not clean connections: " . $e->getMessage() . "\n";
}

echo "\nConnection pool fixes applied!\n";
echo "Please restart your web server and queue workers for changes to take effect.\n";