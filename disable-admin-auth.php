<?php
// Temporarily disable admin authentication

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Disabling Admin Authentication ===\n\n";

// Check if AdminPanelProvider exists
$providerPath = __DIR__ . '/app/Providers/Filament/AdminPanelProvider.php';

if (!file_exists($providerPath)) {
    die("AdminPanelProvider not found!\n");
}

$content = file_get_contents($providerPath);

// Backup original
file_put_contents($providerPath . '.backup', $content);
echo "✓ Backup created: AdminPanelProvider.php.backup\n";

// Comment out authentication middleware
$patterns = [
    '/->authMiddleware\([^)]*\)/' => '//->authMiddleware([])',
    '/->middleware\(\[(.*?)Authenticate::class(.*?)\]\)/' => '->middleware([$1/* Authenticate::class */$2])',
    '/Authenticate::class,/' => '// Authenticate::class,',
    '/AuthenticateSession::class,/' => '// AuthenticateSession::class,',
];

foreach ($patterns as $pattern => $replacement) {
    $content = preg_replace($pattern, $replacement, $content);
}

// Also try to disable login requirement
$content = str_replace(
    '->login()',
    '->login()->requiresAuthentication(false)',
    $content
);

// Write modified content
file_put_contents($providerPath, $content);

echo "✓ Authentication middleware disabled in AdminPanelProvider\n";
echo "\n";
echo "Now clearing all caches...\n";

// Clear caches
exec('php artisan config:clear');
exec('php artisan cache:clear');
exec('php artisan view:clear');
exec('php artisan filament:clear-cached-components');

echo "\n✓ All caches cleared!\n";
echo "\n=== IMPORTANT ===\n";
echo "Admin authentication has been DISABLED!\n";
echo "Anyone can access /admin without login.\n";
echo "\nTo re-enable auth, run:\n";
echo "cp " . $providerPath . ".backup " . $providerPath . "\n";
echo "php artisan optimize:clear\n";