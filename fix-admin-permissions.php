<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "\n🔧 Fixing Admin User Permissions\n";
echo str_repeat("=", 50) . "\n\n";

// Update admin users
$admins = [
    'admin@askproai.de',
    'fabian@askproai.de'
];

foreach ($admins as $email) {
    $user = \App\Models\User::where('email', $email)->first();
    
    if ($user) {
        echo "Updating: {$email}\n";
        echo "  - Current is_admin: " . ($user->is_admin ? 'Yes' : 'No') . "\n";
        echo "  - Current super_admin: " . ($user->super_admin ? 'Yes' : 'No') . "\n";
        
        // Set admin flags
        $user->is_admin = true;
        $user->super_admin = true;
        $user->save();
        
        echo "  ✓ Updated to: is_admin=true, super_admin=true\n\n";
    } else {
        echo "  ✗ User not found: {$email}\n\n";
    }
}

echo str_repeat("=", 50) . "\n";
echo "Admin permissions updated!\n";