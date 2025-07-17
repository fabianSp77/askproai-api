<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;

// Set password for test user
$email = 'admin+1@askproai.de';
$newPassword = 'Test123!';

$user = PortalUser::where('email', $email)->first();

if ($user) {
    $user->password = Hash::make($newPassword);
    $user->save();
    
    echo "Password updated successfully!\n";
    echo "Email: {$email}\n";
    echo "Password: {$newPassword}\n";
    echo "Login URL: https://api.askproai.de/business/login\n";
} else {
    echo "User not found!\n";
}