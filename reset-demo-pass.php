<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;

$user = PortalUser::withoutGlobalScope(\App\Scopes\CompanyScope::class)
    ->where('email', 'demo@askproai.de')
    ->first();

if ($user) {
    $user->password = Hash::make('password');
    $user->save();
    echo "✅ Password reset successfully for demo@askproai.de\n";
    echo "New password: password\n";
} else {
    echo "❌ User not found\n";
}