<?php
require_once __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "=== Checking Portal Users ===\n\n";

// Check for demo user without any scopes
$users = \App\Models\PortalUser::withoutGlobalScopes()->get();

echo "Total portal users: " . $users->count() . "\n\n";

// Look for demo user
$demoUser = \App\Models\PortalUser::withoutGlobalScopes()
    ->where('email', 'demo@askproai.de')
    ->first();

if ($demoUser) {
    echo "Demo user found:\n";
    echo "  ID: " . $demoUser->id . "\n";
    echo "  Email: " . $demoUser->email . "\n";
    echo "  Company ID: " . $demoUser->company_id . "\n";
    echo "  Is Active: " . ($demoUser->is_active ? 'Yes' : 'No') . "\n";
    echo "  Created: " . $demoUser->created_at . "\n";
} else {
    echo "Demo user NOT found!\n\n";
    
    // Check if it's a User model instead
    $user = \App\Models\User::withoutGlobalScopes()
        ->where('email', 'demo@askproai.de')
        ->first();
        
    if ($user) {
        echo "Found in User model instead:\n";
        echo "  ID: " . $user->id . "\n";
        echo "  Email: " . $user->email . "\n";
        echo "  Company ID: " . $user->company_id . "\n";
        echo "  Role: " . $user->role . "\n";
        
        // Create PortalUser from User
        echo "\nCreating PortalUser from User...\n";
        $portalUser = new \App\Models\PortalUser();
        $portalUser->id = $user->id;
        $portalUser->company_id = $user->company_id;
        $portalUser->email = $user->email;
        $portalUser->password = $user->password;
        $portalUser->name = $user->name;
        $portalUser->is_active = 1;
        $portalUser->created_at = $user->created_at;
        $portalUser->updated_at = $user->updated_at;
        $portalUser->save();
        
        echo "✅ PortalUser created successfully!\n";
    }
}

// List all portal users
echo "\nAll portal users:\n";
foreach ($users as $user) {
    echo "  - " . $user->email . " (ID: " . $user->id . ", Company: " . $user->company_id . ")\n";
}

$kernel->terminate($request, $response);