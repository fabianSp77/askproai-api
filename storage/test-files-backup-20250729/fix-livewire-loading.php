<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Artisan;

echo "=== Livewire Loading Fix ===\n\n";

// 1. Clear Livewire cache
echo "1. Clearing Livewire cache...\n";
if (file_exists(base_path('bootstrap/cache/livewire-components.php'))) {
    unlink(base_path('bootstrap/cache/livewire-components.php'));
    echo "✅ Livewire components cache cleared\n";
} else {
    echo "ℹ️ No Livewire cache file found\n";
}

// 2. Clear view cache
echo "\n2. Clearing view cache...\n";
Artisan::call('view:clear');
echo "✅ View cache cleared\n";

// 3. Clear application cache
echo "\n3. Clearing application cache...\n";
Artisan::call('cache:clear');
echo "✅ Application cache cleared\n";

// 4. Clear route cache
echo "\n4. Clearing route cache...\n";
Artisan::call('route:clear');
echo "✅ Route cache cleared\n";

// 5. Clear config cache
echo "\n5. Clearing config cache...\n";
Artisan::call('config:clear');
echo "✅ Config cache cleared\n";

// 6. Check Livewire config
echo "\n6. Checking Livewire configuration...\n";
$livewireConfig = config('livewire');
echo "- Asset URL: " . ($livewireConfig['asset_url'] ?? 'default') . "\n";
echo "- App URL: " . config('app.url') . "\n";
echo "- Manifest path exists: " . (file_exists(public_path('livewire/manifest.json')) ? 'Yes' : 'No') . "\n";

// 7. Publish Livewire assets
echo "\n7. Publishing Livewire assets...\n";
Artisan::call('livewire:publish', ['--assets' => true]);
echo "✅ Livewire assets published\n";

// 8. Check for common issues
echo "\n8. Checking for common issues...\n";

// Check if APP_URL matches actual URL
$appUrl = config('app.url');
$currentUrl = $request->getSchemeAndHttpHost();
if ($appUrl !== $currentUrl) {
    echo "⚠️ APP_URL mismatch: config says '$appUrl' but current URL is '$currentUrl'\n";
} else {
    echo "✅ APP_URL matches current URL\n";
}

// Check session driver
$sessionDriver = config('session.driver');
echo "- Session driver: $sessionDriver\n";
if ($sessionDriver === 'array') {
    echo "⚠️ Session driver is 'array' - this won't work in production!\n";
}

// Check for HTTPS
if ($request->secure()) {
    echo "✅ Using HTTPS\n";
} else {
    echo "⚠️ Not using HTTPS - this can cause issues\n";
}

echo "\n=== Fix Complete ===\n";
echo "Now try accessing /admin/calls again.\n";
echo "If it still doesn't work, check the browser console for JavaScript errors.\n";