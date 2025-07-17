#!/usr/bin/env php
<?php
/**
 * Fix Database Access Denied Error
 * 
 * This script fixes the common "Access denied for user" error
 * by clearing cached configurations and reloading environment settings.
 * 
 * Error Code: DB_001
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔧 Database Access Fix Script\n";
echo "============================\n\n";

try {
    // Step 1: Clear cached config
    echo "1. Clearing cached configuration...\n";
    if (file_exists(base_path('bootstrap/cache/config.php'))) {
        unlink(base_path('bootstrap/cache/config.php'));
        echo "   ✅ Config cache cleared\n";
    } else {
        echo "   ℹ️  No config cache found\n";
    }

    // Step 2: Check for .env.production files
    echo "\n2. Checking for .env.production files...\n";
    $envFiles = glob(base_path('.env*'));
    foreach ($envFiles as $file) {
        if (strpos($file, '.env.production') !== false && !strpos($file, '.template')) {
            $newName = $file . '.template';
            rename($file, $newName);
            echo "   ✅ Renamed: " . basename($file) . " → " . basename($newName) . "\n";
        }
    }

    // Step 3: Regenerate config cache
    echo "\n3. Regenerating configuration cache...\n";
    Artisan::call('config:cache');
    echo "   ✅ Configuration cached\n";

    // Step 4: Test database connection
    echo "\n4. Testing database connection...\n";
    try {
        DB::select('SELECT 1');
        echo "   ✅ Database connection successful!\n";
        
        // Get connection info (without password)
        $config = config('database.connections.mysql');
        echo "\n   Connected to:\n";
        echo "   - Host: {$config['host']}:{$config['port']}\n";
        echo "   - Database: {$config['database']}\n";
        echo "   - User: {$config['username']}\n";
        
    } catch (\Exception $e) {
        echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
        exit(1);
    }

    // Step 5: Restart services (if running as root)
    echo "\n5. Restarting services...\n";
    if (posix_getuid() === 0) {
        exec('systemctl restart php8.3-fpm 2>&1', $output, $returnCode);
        if ($returnCode === 0) {
            echo "   ✅ PHP-FPM restarted\n";
        } else {
            echo "   ⚠️  Could not restart PHP-FPM (may need manual restart)\n";
        }
    } else {
        echo "   ℹ️  Run as root to restart services automatically\n";
        echo "   Run: sudo systemctl restart php8.3-fpm\n";
    }

    echo "\n✅ Database access fix completed successfully!\n";
    echo "\nIf the problem persists:\n";
    echo "1. Check your .env file has correct database credentials\n";
    echo "2. Verify the database user has proper permissions\n";
    echo "3. Check if the database server is running\n";
    echo "4. Review /storage/logs/laravel.log for detailed errors\n";
    
    exit(0);

} catch (\Exception $e) {
    echo "\n❌ Error during fix process: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}