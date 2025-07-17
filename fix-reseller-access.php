<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;

$user = PortalUser::where('email', 'max@techpartner.de')->first();
if ($user) {
    $user->update(['can_access_child_companies' => true]);
    echo "✅ Updated reseller user: {$user->name}\n";
    echo "   Can access children: " . ($user->can_access_child_companies ? 'Yes' : 'No') . "\n";
} else {
    echo "❌ Reseller user not found\n";
}