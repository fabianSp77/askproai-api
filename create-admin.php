<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\User;

$user = User::where('email', 'admin@askproai.de')->first();
if (\!$user) {
    $user = User::create([
        'name' => 'Admin',
        'email' => 'admin@askproai.de',
        'password' => bcrypt('AdminPassword123\!'),
        'email_verified_at' => now(),
    ]);
    echo "Admin user created\!\n";
} else {
    $user->password = bcrypt('AdminPassword123\!');
    $user->save();
    echo "Admin password reset\!\n";
}

echo "Email: admin@askproai.de\n";
echo "Password: AdminPassword123\!\n";
EOF < /dev/null
