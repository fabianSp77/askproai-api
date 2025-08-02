<?php
/**
 * Clear everything and start fresh
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "🧹 CLEARING EVERYTHING\n";
echo "=====================\n\n";

// 1. Clear all caches
echo "1️⃣ Clearing all caches...\n";
\Illuminate\Support\Facades\Artisan::call('optimize:clear');
echo "   ✅ All caches cleared\n\n";

// 2. Clear all sessions
echo "2️⃣ Clearing all session files...\n";
$sessionPath = storage_path('framework/sessions');
$files = glob($sessionPath . '/*');
$count = 0;
foreach ($files as $file) {
    if (is_file($file) && basename($file) !== '.gitignore') {
        unlink($file);
        $count++;
    }
}
echo "   ✅ Deleted $count session files\n\n";

// 3. Clear compiled files
echo "3️⃣ Clearing compiled files...\n";
$compiledPath = base_path('bootstrap/cache/');
$compiledFiles = ['config.php', 'routes-v7.php', 'services.php', 'packages.php'];
foreach ($compiledFiles as $file) {
    $fullPath = $compiledPath . $file;
    if (file_exists($fullPath)) {
        unlink($fullPath);
        echo "   Deleted: $file\n";
    }
}
echo "   ✅ Compiled files cleared\n\n";

// 4. Show current configuration
echo "4️⃣ CURRENT CONFIGURATION:\n";
echo "   APP_KEY exists: " . (config('app.key') ? 'YES' : 'NO') . "\n";
echo "   Session driver: " . config('session.driver') . "\n";
echo "   Session cookie: " . config('session.cookie') . "\n";
echo "   Session encrypt: " . (config('session.encrypt') ? 'true' : 'false') . "\n";
echo "   Cookie encrypt exceptions: ";
$encryptCookies = new \App\Http\Middleware\EncryptCookies($app);
$reflection = new \ReflectionClass($encryptCookies);
$property = $reflection->getProperty('except');
$property->setAccessible(true);
$exceptions = $property->getValue($encryptCookies);
echo empty($exceptions) ? 'NONE' : implode(', ', $exceptions);
echo "\n\n";

echo "✅ EVERYTHING CLEARED!\n";
echo "===================\n";
echo "Next steps:\n";
echo "1. Clear ALL browser cookies (F12 → Application → Cookies → Clear All)\n";
echo "2. Close browser completely\n";
echo "3. Open new browser window\n";
echo "4. Test at: https://api.askproai.de/web-session-login.php\n";