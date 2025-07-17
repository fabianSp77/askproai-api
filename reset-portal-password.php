<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;

// Reset password for portal user
$email = 'admin@askproai.de';
$newPassword = 'password123';

$user = PortalUser::where('email', $email)->first();

if ($user) {
    $user->password = bcrypt($newPassword);
    $user->save();
    
    echo "Password reset successfully!\n";
    echo "Email: {$email}\n";
    echo "New Password: {$newPassword}\n";
    echo "Company: " . ($user->company ? $user->company->name : 'None') . "\n";
    echo "\nLogin URL: https://api.askproai.de/business/login\n";
} else {
    echo "User not found!\n";
}