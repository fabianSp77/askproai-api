<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n🔍 Portal Status Check\n";
echo str_repeat('=', 50) . "\n\n";

// 1. Check Database Connection
echo "1. Database Connection: ";
try {
    DB::connection()->getPdo();
    echo "✅ Connected\n";
} catch (\Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
}

// 2. Check Redis Connection
echo "2. Redis Connection: ";
try {
    $redis = Redis::connection();
    $redis->ping();
    echo "✅ Connected\n";
} catch (\Exception $e) {
    echo "⚠️  Failed (non-critical): " . $e->getMessage() . "\n";
}

// 3. Check MCP Gateway
echo "3. MCP Gateway: ";
try {
    $gateway = app(\App\Services\MCP\MCPGateway::class);
    $health = $gateway->health();
    echo "✅ " . $health['gateway'] . "\n";
} catch (\Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
}

// 4. Check Filament Pages
echo "4. Filament Pages:\n";
$pages = [
    'CompanyIntegrationPortal' => \App\Filament\Admin\Pages\CompanyIntegrationPortal::class,
    'RetellWebhookConfiguration' => \App\Filament\Admin\Pages\RetellWebhookConfiguration::class,
];

foreach ($pages as $name => $class) {
    echo "   - {$name}: ";
    if (class_exists($class)) {
        echo "✅ Exists\n";
    } else {
        echo "❌ Missing\n";
    }
}

// 5. Check Routes
echo "5. Critical Routes:\n";
$routes = [
    'filament.admin.pages.company-integration-portal',
    'filament.admin.pages.retell-webhook-configuration',
    'mcp.gateway',
    'mcp.gateway.retell.functions',
];

foreach ($routes as $route) {
    echo "   - {$route}: ";
    if (Route::has($route)) {
        echo "✅ Registered\n";
    } else {
        echo "❌ Missing\n";
    }
}

// 6. Check Recent Errors
echo "6. Recent Errors: ";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lastLines = `tail -n 100 {$logFile} | grep -c "ERROR\\|Exception" | grep -v "AUTH EVENT"`;
    $errorCount = intval(trim($lastLines));
    if ($errorCount > 0) {
        echo "⚠️  {$errorCount} errors in last 100 lines\n";
    } else {
        echo "✅ No recent errors\n";
    }
} else {
    echo "⚠️  Log file not found\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "Overall Status: ";

// Determine overall status
if (class_exists(\App\Filament\Admin\Pages\RetellWebhookConfiguration::class) && 
    Route::has('filament.admin.pages.retell-webhook-configuration')) {
    echo "✅ Portal should be accessible\n";
    echo "\nAccess the Retell Configuration at:\n";
    echo "  /admin/retell-webhook-configuration\n";
} else {
    echo "❌ Portal may have issues\n";
}

echo "\n";