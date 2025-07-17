<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;

$user = PortalUser::where('email', 'demo@example.com')->first();
if ($user) {
    $user->password = Hash::make('password');
    $user->save();
    echo "✅ Password reset for demo@example.com\n";
} else {
    echo "❌ User not found\n";
}