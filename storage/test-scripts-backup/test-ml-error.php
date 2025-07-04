<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Authenticate as the user
    $user = \App\Models\User::where('email', 'fabian@askproai.de')->first();
    if ($user) {
        \Auth::login($user);
        echo "Logged in as: " . $user->email . "\n";
        echo "User ID: " . $user->id . "\n";
        echo "Tenant ID: " . $user->tenant_id . "\n";
        echo "Company ID (from accessor): " . $user->company_id . "\n";
        
        // Try to access the dashboard page
        $dashboard = new \App\Filament\Admin\Pages\StaticMLDashboard();
        $dashboard->mount();
        $viewData = $dashboard->getViewData();
        
        echo "\nView data retrieved successfully:\n";
        print_r($viewData);
    } else {
        echo "User not found\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}