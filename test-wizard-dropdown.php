<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

// Test the branch dropdown loading
echo "=== Testing Branch Dropdown Loading ===\n\n";

// Test 1: Check if we can get branches for a company
$companyId = 85;
echo "1. Testing branch query for company $companyId:\n";

try {
    $branches = \App\Models\Branch::withoutGlobalScopes()
        ->where('company_id', $companyId)
        ->where('is_active', true)
        ->pluck('name', 'id')
        ->toArray();
    
    echo "   ✅ Found " . count($branches) . " branches:\n";
    foreach ($branches as $id => $name) {
        echo "      - [$id] $name\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 2: Simulate what happens in the Select field
echo "\n2. Testing Select field options callable:\n";

$get = function($field) use ($companyId) {
    return $field === 'company_id' ? $companyId : null;
};

$options = (function (callable $get) {
    $companyId = $get('company_id');
    if (!$companyId) {
        return [];
    }
    
    try {
        $branches = \App\Models\Branch::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('name', 'id')
            ->toArray();
        
        return $branches;
    } catch (\Exception $e) {
        return [];
    }
})($get);

echo "   Options returned: " . count($options) . " items\n";
if (count($options) > 0) {
    echo "   ✅ Options callable works correctly\n";
} else {
    echo "   ❌ Options callable returned empty array\n";
}

// Test 3: Check if user has company_id
echo "\n3. Testing user context:\n";
$user = \App\Models\User::first();
if ($user) {
    echo "   User: {$user->name}\n";
    echo "   Company ID: " . ($user->company_id ?? 'null') . "\n";
    if ($user->company_id) {
        $company = \App\Models\Company::find($user->company_id);
        echo "   Company: " . ($company ? $company->name : 'not found') . "\n";
    }
} else {
    echo "   ❌ No user found\n";
}

echo "\n✅ Test complete!\n";