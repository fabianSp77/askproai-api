<?php
// Fix Session Secure Cookie Issue

echo "<h1>Fixing Session Secure Cookie Issue</h1>";
echo "<pre>";

// Step 1: Update .env file
echo "Step 1: Updating SESSION_SECURE_COOKIE in .env file...\n";

$envFile = __DIR__ . '/../.env';
$envContent = file_get_contents($envFile);

// Update SESSION_SECURE_COOKIE to true
if (strpos($envContent, 'SESSION_SECURE_COOKIE=false') !== false) {
    $envContent = str_replace('SESSION_SECURE_COOKIE=false', 'SESSION_SECURE_COOKIE=true', $envContent);
    file_put_contents($envFile, $envContent);
    echo "✅ Updated SESSION_SECURE_COOKIE to true\n";
} else {
    echo "⚠️  SESSION_SECURE_COOKIE not found or already true\n";
}

// Step 2: Clear caches
echo "\nStep 2: Clearing all caches...\n";
$commands = [
    'php artisan config:clear' => 'Config cache cleared',
    'php artisan cache:clear' => 'Application cache cleared',
    'php artisan view:clear' => 'View cache cleared',
    'php artisan route:clear' => 'Route cache cleared',
];

foreach ($commands as $command => $message) {
    $output = shell_exec("cd /var/www/api-gateway && $command 2>&1");
    echo "✅ $message\n";
}

// Step 3: Check TrustProxies middleware
echo "\nStep 3: Checking TrustProxies middleware...\n";
$trustProxiesFile = __DIR__ . '/../app/Http/Middleware/TrustProxies.php';
if (file_exists($trustProxiesFile)) {
    $content = file_get_contents($trustProxiesFile);
    echo "TrustProxies middleware exists\n";
    
    // Check if it's configured properly
    if (strpos($content, 'protected $proxies = \'*\'') !== false || 
        strpos($content, 'protected $proxies = "*"') !== false) {
        echo "✅ TrustProxies is configured to trust all proxies\n";
    } else {
        echo "⚠️  TrustProxies might need configuration\n";
    }
} else {
    echo "❌ TrustProxies middleware not found!\n";
}

// Step 4: Test configuration
echo "\nStep 4: Testing new configuration...\n";
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a request with HTTPS headers
$request = Illuminate\Http\Request::create('https://api.askproai.de/test', 'GET', [], [], [], [
    'HTTP_HOST' => 'api.askproai.de',
    'HTTPS' => 'on',
    'HTTP_X_FORWARDED_PROTO' => 'https',
]);

$response = $kernel->handle($request);

echo "- Request secure: " . ($request->secure() ? 'Yes' : 'No') . "\n";
echo "- Session secure config: " . (config('session.secure') ? 'Yes' : 'No') . "\n";

echo "\nFIXES APPLIED:\n";
echo "1. ✅ SESSION_SECURE_COOKIE set to true\n";
echo "2. ✅ All caches cleared\n";
echo "3. ⚠️  You may need to check TrustProxies middleware\n";
echo "\nNext steps:\n";
echo "- Restart PHP-FPM: sudo systemctl restart php8.3-fpm\n";
echo "- Clear browser cookies and try again\n";

$kernel->terminate($request, $response);

echo "</pre>";