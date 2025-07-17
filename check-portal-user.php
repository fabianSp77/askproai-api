<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;

$email = 'admin@askproai.de';
$password = 'password123';

$user = PortalUser::where('email', $email)->first();

if ($user) {
    echo "User found:\n";
    echo "Email: {$user->email}\n";
    echo "Name: {$user->name}\n";
    echo "Is Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
    echo "Company: " . ($user->company ? $user->company->name : 'None') . "\n";
    echo "Password Hash: " . substr($user->password, 0, 20) . "...\n";
    
    // Test password
    $passwordCheck = Hash::check($password, $user->password);
    echo "Password Check: " . ($passwordCheck ? 'PASS' : 'FAIL') . "\n";
    
    // Make sure user is active
    if (!$user->is_active) {
        echo "\nActivating user...\n";
        $user->is_active = true;
        $user->save();
        echo "User activated!\n";
    }
    
    // Reset password to be sure
    echo "\nResetting password...\n";
    $user->password = Hash::make($password);
    $user->save();
    echo "Password reset!\n";
    
} else {
    echo "User not found!\n";
}