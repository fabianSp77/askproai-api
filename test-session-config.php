<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

// Boot the application
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Session Configuration:\n";
echo "=====================\n";
echo "Domain: " . config('session.domain') . "\n";
echo "Driver: " . config('session.driver') . "\n";
echo "Cookie: " . config('session.cookie') . "\n";
echo "Secure: " . (config('session.secure') ? 'Yes' : 'No') . "\n";
echo "\n✅ Configuration loaded successfully!\n";