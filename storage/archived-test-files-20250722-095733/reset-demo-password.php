<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Find and update demo user password
$user = \App\Models\PortalUser::where('email', 'demo@askproai.de')->first();

if ($user) {
    $user->password = bcrypt('demo123');
    $user->save();
    
    echo "Demo user password updated:\n";
    echo "Email: demo@askproai.de\n";
    echo "Password: demo123\n";
    echo "ID: " . $user->id . "\n";
    echo "Company ID: " . $user->company_id . "\n";
    echo "Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
} else {
    echo "Demo user not found\!\n";
}
EOF < /dev/null
