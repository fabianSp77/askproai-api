<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Benutzer-Rollen Check ===" . PHP_EOL;
echo PHP_EOL;

$users = DB::table('users')
    ->select('id', 'name', 'email')
    ->limit(10)
    ->get();

foreach ($users as $user) {
    $roles = DB::table('model_has_roles')
        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
        ->where('model_has_roles.model_id', $user->id)
        ->where('model_has_roles.model_type', 'App\Models\User')
        ->pluck('roles.name')
        ->toArray();
    
    echo "User: {$user->name} ({$user->email})" . PHP_EOL;
    echo "Rollen: " . (empty($roles) ? 'KEINE' : implode(', ', $roles)) . PHP_EOL;
    echo PHP_EOL;
}
