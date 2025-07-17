<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "Fixing Portal User Password\n";
echo str_repeat("=", 50) . "\n\n";

$portalUser = \App\Models\PortalUser::withoutGlobalScopes()
    ->where('email', 'demo@example.com')->first();

if ($portalUser) {
    echo "✓ Found portal user: demo@example.com\n";
    echo "  Current ID: {$portalUser->id}\n";
    echo "  Current Name: {$portalUser->name}\n";
    
    // Update password
    $portalUser->password = Hash::make('demo123');
    $portalUser->save();
    
    echo "✓ Password updated to: demo123\n";
    
    // Verify
    if (Hash::check('demo123', $portalUser->fresh()->password)) {
        echo "✓ Password verification: SUCCESS\n";
    } else {
        echo "✗ Password verification: FAILED\n";
    }
} else {
    echo "✗ Portal user not found\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Done! You can now login with:\n";
echo "Email: demo@example.com\n";
echo "Password: demo123\n";