<?php

echo "=== Testing Event Type Setup Wizard Functionality ===\n\n";

// Test 1: Check if branches exist
echo "1. Checking branches for company 85:\n";
$result = shell_exec("php artisan tinker --execute=\"
\\\$branches = \\App\\Models\\Branch::withoutGlobalScopes()
    ->where('company_id', 85)
    ->where('is_active', true)
    ->get(['id', 'name']);
echo 'Active branches: ' . \\\$branches->count() . PHP_EOL;
foreach(\\\$branches as \\\$b) {
    echo '  - [' . \\\$b->id . '] ' . \\\$b->name . PHP_EOL;
}
\"");
echo $result . "\n";

// Test 2: Check Event Types
echo "2. Checking Event Types for company 85:\n";
$result = shell_exec("php artisan tinker --execute=\"
\\\$eventTypes = \\App\\Models\\CalcomEventType::withoutGlobalScopes()
    ->where('company_id', 85)
    ->get(['id', 'name', 'branch_id', 'setup_status']);
echo 'Total Event Types: ' . \\\$eventTypes->count() . PHP_EOL;
foreach(\\\$eventTypes as \\\$et) {
    echo '  - [' . \\\$et->id . '] ' . \\\$et->name . ' (Branch: ' . (\\\$et->branch_id ?? 'none') . ', Status: ' . \\\$et->setup_status . ')' . PHP_EOL;
}
\"");
echo $result . "\n";

// Test 3: Test the wizard page load
echo "3. Testing wizard page loading:\n";
try {
    require __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Create a test user
    $user = \App\Models\User::first();
    if ($user) {
        echo "   ✅ Found user: {$user->name} (Company: " . ($user->company_id ?? 'none') . ")\n";
        
        // Test page class
        $page = new \App\Filament\Admin\Pages\EventTypeSetupWizard();
        echo "   ✅ Page class instantiated successfully\n";
        
        // Test form method
        $form = app(\Filament\Forms\Form::class);
        $page->form($form);
        echo "   ✅ Form method executed without errors\n";
    } else {
        echo "   ❌ No user found in database\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Summary ===\n";
echo "The wizard should now:\n";
echo "1. Show company selection (disabled if user has company_id)\n";
echo "2. Show branch dropdown after company selection\n";
echo "3. Show event types filtered by company/branch\n";
echo "4. Use proper Filament form state management\n";
echo "5. Have security checks to prevent cross-company access\n";

echo "\n✅ Test complete!\n";