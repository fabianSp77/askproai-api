<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "\n🔍 Finding All Admin Users\n";
echo str_repeat("=", 50) . "\n\n";

// Get all admin users
$adminUsers = \App\Models\User::all();

echo "Found " . $adminUsers->count() . " admin users:\n\n";

foreach ($adminUsers as $user) {
    echo "ID: {$user->id}\n";
    echo "Name: {$user->name}\n";
    echo "Email: {$user->email}\n";
    echo "Created: {$user->created_at}\n";
    echo "Last Login: " . ($user->last_login_at ?? 'Never') . "\n";
    echo str_repeat("-", 30) . "\n";
}

// Check for common admin emails
echo "\n🔍 Checking common admin emails:\n";
$commonEmails = [
    'admin@askproai.de',
    'fabian@askproai.de',
    'fabianspitzer@icloud.com',
    'support@askproai.de',
];

foreach ($commonEmails as $email) {
    $user = \App\Models\User::where('email', $email)->first();
    if ($user) {
        echo "✓ Found: $email (ID: {$user->id})\n";
    } else {
        echo "✗ Not found: $email\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Which email address are you trying to use?\n";