<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\PortalUser::where('email', 'demo@askproai.de')->first();
if ($user) {
    $user->password = bcrypt('demo123');
    $user->save();
    echo "Password set to 'demo123' for user: {$user->email}\n";
} else {
    echo "User not found\n";
}