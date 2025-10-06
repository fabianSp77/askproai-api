<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Tenant;
use App\Filament\Resources\TenantResource;
use Illuminate\Support\Facades\Auth;

echo "\n🔍 DEBUGGING 500 ERROR ON MANDANTEN PAGE\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Simulate authenticated session
$user = User::first();
if ($user) {
    Auth::login($user);
    echo "✅ Simulated login as: {$user->email}\n\n";
}

// Test 1: Check if TenantResource can generate table
echo "📊 Testing TenantResource Table Generation:\n";
try {
    $resource = TenantResource::class;

    // Create a mock table to test the resource
    $table = new \Filament\Tables\Table();
    $configuredTable = $resource::table($table);

    $columns = $configuredTable->getColumns();
    echo "  ✅ Table columns: " . count($columns) . "\n";

    // Check each column for potential issues
    $problematicColumns = [];
    foreach ($columns as $column) {
        $name = $column->getName();

        // Check if this column exists in the database or is a computed field
        if (strpos($name, '.') === false && !in_array($name, ['id', 'name', 'status', 'created_at', 'updated_at'])) {
            // Test if we can access this field on a tenant
            try {
                $tenant = Tenant::first();
                if ($tenant) {
                    $value = $tenant->{$name};
                }
            } catch (\Exception $e) {
                $problematicColumns[] = $name;
            }
        }
    }

    if (!empty($problematicColumns)) {
        echo "  ⚠️ Potentially problematic columns: " . implode(', ', $problematicColumns) . "\n";
    } else {
        echo "  ✅ All columns accessible\n";
    }

} catch (\Exception $e) {
    echo "  ❌ Table generation error: " . $e->getMessage() . "\n";
    echo "  Stack trace (first 5 lines):\n";
    $trace = explode("\n", $e->getTraceAsString());
    foreach (array_slice($trace, 0, 5) as $line) {
        echo "    " . $line . "\n";
    }
}

// Test 2: Check for specific tooltip/column issues
echo "\n🔧 Checking Column Configurations:\n";
try {
    $table = new \Filament\Tables\Table();
    $configuredTable = TenantResource::table($table);

    foreach ($configuredTable->getColumns() as $column) {
        $name = $column->getName();

        // Check if column has tooltip that might cause issues
        try {
            $tooltipCallable = null;
            $reflection = new ReflectionClass($column);

            // Check for tooltip property
            if ($reflection->hasProperty('tooltip')) {
                $prop = $reflection->getProperty('tooltip');
                $prop->setAccessible(true);
                $tooltipValue = $prop->getValue($column);

                if ($tooltipValue !== null && !is_string($tooltipValue) && is_callable($tooltipValue)) {
                    echo "  ⚠️ Column '{$name}' has dynamic tooltip\n";
                }
            }
        } catch (\Exception $e) {
            echo "  ⚠️ Could not check tooltip for column '{$name}'\n";
        }
    }

} catch (\Exception $e) {
    echo "  ❌ Column check error: " . $e->getMessage() . "\n";
}

// Test 3: Verify database schema matches model expectations
echo "\n📋 Database Schema Verification:\n";
$expectedColumns = [
    'id', 'company_id', 'name', 'domain', 'status', 'is_active',
    'pricing_plan', 'monthly_fee', 'balance_cents', 'created_at', 'updated_at'
];

$actualColumns = \Illuminate\Support\Facades\Schema::getColumnListing('tenants');
$missingColumns = array_diff($expectedColumns, $actualColumns);

if (empty($missingColumns)) {
    echo "  ✅ All expected columns present\n";
} else {
    echo "  ❌ Missing columns: " . implode(', ', $missingColumns) . "\n";
}

// Test 4: Try to render the actual list page
echo "\n🖥️ Testing ListTenants Page Rendering:\n";
try {
    $listPage = TenantResource\Pages\ListTenants::class;

    // Check if the page class exists
    if (class_exists($listPage)) {
        echo "  ✅ ListTenants page class exists\n";

        // Try to get the table from the page
        $page = new $listPage();

        // Check if we can get the table configuration
        if (method_exists($page, 'table')) {
            echo "  ✅ Page has table method\n";
        }
    }
} catch (\Exception $e) {
    echo "  ❌ Page rendering error: " . $e->getMessage() . "\n";
}

// Test 5: Query test with relationships
echo "\n🔗 Testing Queries with Relationships:\n";
try {
    // This is what Filament might do
    $query = Tenant::query();

    // Apply eager loading that might be in TenantResource
    $tenants = $query->with(['company', 'users'])->paginate(10);

    echo "  ✅ Can paginate with relationships: " . $tenants->count() . " records\n";

    // Test accessing relationship methods
    $firstTenant = $tenants->first();
    if ($firstTenant) {
        try {
            $phoneCount = $firstTenant->phoneNumbers()->count();
            echo "  ✅ Can access phoneNumbers(): $phoneCount\n";
        } catch (\Exception $e) {
            echo "  ❌ Error accessing phoneNumbers(): " . $e->getMessage() . "\n";
        }
    }

} catch (\Exception $e) {
    echo "  ❌ Query error: " . $e->getMessage() . "\n";
}

echo "\n🎯 DIAGNOSIS COMPLETE\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Clean up
Auth::logout();
unlink(__FILE__);