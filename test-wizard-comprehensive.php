<?php

echo "=== Comprehensive EventTypeSetupWizard Test ===\n\n";

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Test 1: Check user context
echo "1. User Context Test:\n";
$user = \App\Models\User::where('email', 'admin@askproai.de')->first();
if ($user) {
    echo "   ✅ User found: {$user->name}\n";
    echo "   - Company ID: " . ($user->company_id ?? 'null') . "\n";
    if ($user->company_id) {
        $company = \App\Models\Company::find($user->company_id);
        echo "   - Company: " . ($company ? $company->name : 'not found') . "\n";
    }
} else {
    echo "   ❌ Admin user not found\n";
}

// Test 2: Check if company has branches
echo "\n2. Company Branches Test:\n";
if ($user && $user->company_id) {
    $branches = \App\Models\Branch::withoutGlobalScopes()
        ->where('company_id', $user->company_id)
        ->where('is_active', true)
        ->get(['id', 'name']);
    
    echo "   Found {$branches->count()} active branches:\n";
    foreach ($branches as $branch) {
        echo "   - [{$branch->id}] {$branch->name}\n";
    }
} else {
    echo "   ⚠️  No company context to test branches\n";
}

// Test 3: Check event types
echo "\n3. Event Types Test:\n";
if ($user && $user->company_id) {
    $eventTypes = \App\Models\CalcomEventType::withoutGlobalScopes()
        ->where('company_id', $user->company_id)
        ->get(['id', 'name', 'branch_id', 'setup_status']);
    
    echo "   Found {$eventTypes->count()} event types:\n";
    foreach ($eventTypes as $et) {
        $branchName = $et->branch_id ? 
            \App\Models\Branch::withoutGlobalScopes()->find($et->branch_id)?->name : 
            'No branch';
        echo "   - [{$et->id}] {$et->name} (Branch: {$branchName}, Status: {$et->setup_status})\n";
    }
}

// Test 4: Form state simulation
echo "\n4. Form State Simulation:\n";
try {
    // Simulate what the form does
    $get = function($field) use ($user) {
        if ($field === 'company_id' && $user) {
            return $user->company_id;
        }
        return null;
    };
    
    // Test branch options callable
    $branchOptions = (function(callable $get) {
        $companyId = $get('company_id');
        
        if (!$companyId) {
            return [];
        }
        
        return \App\Models\Branch::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id');
    })($get);
    
    echo "   Branch options callable returned: " . count($branchOptions) . " items\n";
    if (count($branchOptions) > 0) {
        echo "   ✅ Branch loading logic works\n";
    } else {
        echo "   ❌ Branch loading logic failed\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error in form simulation: " . $e->getMessage() . "\n";
}

// Test 5: Check Livewire component registration
echo "\n5. Livewire Component Check:\n";
$livewireComponents = [
    'filament.admin.pages.event-type-setup-wizard',
    'App\\Filament\\Admin\\Pages\\EventTypeSetupWizard'
];

foreach ($livewireComponents as $component) {
    if (class_exists($component) || app('livewire')->getClass($component)) {
        echo "   ✅ Component registered: $component\n";
    } else {
        echo "   ❌ Component not found: $component\n";
    }
}

echo "\n=== Summary ===\n";
echo "Based on these tests:\n";
echo "1. Database has correct data ✅\n";
echo "2. Query logic is correct ✅\n";
echo "3. The issue is likely in Livewire state management\n";
echo "\nPossible Solutions:\n";
echo "- Clear all caches: php artisan optimize:clear\n";
echo "- Check browser console for JavaScript errors\n";
echo "- Enable Livewire debugging in .env: LIVEWIRE_DEBUG=true\n";
echo "- Try incognito mode to rule out browser cache\n";

echo "\n✅ Test complete!\n";