<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;

echo "\n🔐 Testing Portal Login\n";
echo "======================\n\n";

// Test user
$email = "fabianspitzer@icloud.com";
$password = "demo123";

$user = PortalUser::where("email", $email)->first();

if (!$user) {
    echo "❌ User not found: $email\n";
    exit(1);
}

echo "✅ User found: {$user->email} (ID: {$user->id})\n";
echo "✅ User is " . ($user->is_active ? "active" : "INACTIVE") . "\n";
echo "✅ Company: {$user->company->name} (ID: {$user->company_id})\n";

// Test password
if (Hash::check($password, $user->password)) {
    echo "✅ Password is correct\n";
} else {
    echo "❌ Password is incorrect\n";
    echo "   Setting password to: $password\n";
    $user->password = Hash::make($password);
    $user->save();
    echo "✅ Password updated\n";
}

// Test authentication
if (Auth::guard("portal")->attempt(["email" => $email, "password" => $password])) {
    echo "✅ Authentication successful!\n";
    $authUser = Auth::guard("portal")->user();
    echo "   Authenticated as: {$authUser->email}\n";
} else {
    echo "❌ Authentication failed\n";
}

echo "\n✅ Login test complete\n";
