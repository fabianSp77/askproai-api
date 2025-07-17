<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Get admin user
$user = \App\Models\User::where('email', 'admin@askproai.de')->first();

if (!$user) {
    echo "User admin@askproai.de not found!\n";
    exit(1);
}

echo "User: " . $user->email . "\n";
echo "ID: " . $user->id . "\n";
echo "Has Super Admin role: " . ($user->hasRole('Super Admin') ? 'Yes' : 'No') . "\n";
echo "All roles: " . $user->roles->pluck('name')->join(', ') . "\n\n";

// Check BusinessPortalAdmin page access
$page = new \App\Filament\Admin\Pages\BusinessPortalAdmin();
echo "BusinessPortalAdmin canAccess: " . ($page::canAccess() ? 'Yes' : 'No') . "\n";

// Check navigation menu
echo "\nChecking navigation item visibility...\n";
$navigationItem = $page::getNavigationLabel();
echo "Navigation Label: " . $navigationItem . "\n";
echo "Navigation Group: " . $page::getNavigationGroup() . "\n";

// List all available pages
echo "\nAvailable Filament Pages:\n";
$path = app_path('Filament/Admin/Pages');
$files = glob($path . '/*.php');
foreach ($files as $file) {
    echo "- " . basename($file, '.php') . "\n";
}