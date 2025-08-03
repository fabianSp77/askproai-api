<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;

$user = PortalUser::where('email', 'demo@askproai.de')->first();

if ($user) {
    $user->password = Hash::make('password');
    $user->is_active = 1;
    $user->save();
    echo "✅ Demo user password reset to 'password'\n";
    echo "Email: demo@askproai.de\n";
    echo "Password: password\n";
    echo "Company ID: " . $user->company_id . "\n";
} else {
    echo "❌ Demo user not found\n";
}