<?php
/**
 * OPcache Preloader for AskProAI
 * 
 * This file preloads critical classes and functions into OPcache
 * for maximum performance in production environments.
 */

if (php_sapi_name() !== 'cli') {
    die('Preloader should only be executed via CLI');
}

$baseDir = __DIR__;
$vendorDir = $baseDir . '/vendor';

if (!file_exists($vendorDir . '/autoload.php')) {
    die('Composer autoloader not found');
}

require_once $vendorDir . '/autoload.php';

// Preload Laravel Framework Core
$laravelFiles = [
    // Core Framework
    'vendor/laravel/framework/src/Illuminate/Foundation/Application.php',
    'vendor/laravel/framework/src/Illuminate/Container/Container.php',
    'vendor/laravel/framework/src/Illuminate/Http/Request.php',
    'vendor/laravel/framework/src/Illuminate/Http/Response.php',
    'vendor/laravel/framework/src/Illuminate/Routing/Router.php',
    'vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php',
    'vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php',
    'vendor/laravel/framework/src/Illuminate/Support/Collection.php',
    'vendor/laravel/framework/src/Illuminate/Support/Facades/Facade.php',
    
    // Cache & Redis
    'vendor/laravel/framework/src/Illuminate/Cache/Repository.php',
    'vendor/laravel/framework/src/Illuminate/Cache/RedisStore.php',
    'vendor/laravel/framework/src/Illuminate/Redis/RedisManager.php',
    
    // Queue & Horizon
    'vendor/laravel/framework/src/Illuminate/Queue/Queue.php',
    'vendor/laravel/framework/src/Illuminate/Queue/RedisQueue.php',
    'vendor/laravel/horizon/src/Horizon.php',
];

// Preload Application Classes
$appFiles = [
    // Models
    'app/Models/Company.php',
    'app/Models/Branch.php',
    'app/Models/Staff.php',
    'app/Models/Customer.php',
    'app/Models/Appointment.php',
    'app/Models/Call.php',
    'app/Models/Service.php',
    
    // Services
    'app/Services/CalcomV2Service.php',
    'app/Services/RetellV2Service.php',
    'app/Services/CallDataRefresher.php',
    
    // Controllers (most used)
    'app/Http/Controllers/API/CalComController.php',
    'app/Http/Controllers/API/RetellConversationEndedController.php',
    'app/Http/Controllers/CalcomWebhookController.php',
    
    // Middleware
    'app/Http/Middleware/VerifyCalcomSignature.php',
    'app/Http/Middleware/VerifyRetellSignature.php',
    'app/Http/Middleware/IdentifyTenant.php',
];

$preloadedCount = 0;
$failedCount = 0;

foreach (array_merge($laravelFiles, $appFiles) as $file) {
    $fullPath = $baseDir . '/' . $file;
    
    if (file_exists($fullPath)) {
        try {
            opcache_compile_file($fullPath);
            $preloadedCount++;
        } catch (Throwable $e) {
            error_log("Failed to preload {$file}: " . $e->getMessage());
            $failedCount++;
        }
    }
}

// Preload Composer classes (most frequently used)
$composerClasses = [
    'Illuminate\Foundation\Application',
    'Illuminate\Container\Container',
    'Illuminate\Http\Request',
    'Illuminate\Database\Eloquent\Model',
    'Illuminate\Support\Collection',
    'Illuminate\Cache\Repository',
    'Illuminate\Queue\RedisQueue',
];

foreach ($composerClasses as $class) {
    if (class_exists($class)) {
        $preloadedCount++;
    }
}

echo "OPcache Preloader completed:\n";
echo "- Preloaded files: {$preloadedCount}\n";
echo "- Failed files: {$failedCount}\n";
echo "- Memory used: " . number_format(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";