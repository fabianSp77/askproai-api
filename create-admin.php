<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

try {
    $user = User::updateOrCreate(
        ['email' => 'admin@example.com'],
        [
            'name' => 'Admin User',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]
    );
    
    echo "Admin user created/updated successfully!\n";
    echo "Email: admin@example.com\n";
    echo "Password: password\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}