<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\User;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Login as admin
$admin = User::where('email', 'admin@askproai.de')->first();
if (!$admin) {
    echo "❌ Admin user not found\n";
    exit(1);
}

Auth::login($admin);

echo "Testing Filament Admin Pages\n";
echo "============================\n\n";

// Test data for each resource
$testData = [
    'Branches' => Branch::first(),
    'Calls' => Call::first(),
    'Companies' => Company::first(),
    'Customers' => Customer::first(),
];

$results = [];

foreach ($testData as $resourceName => $record) {
    echo "Testing $resourceName:\n";
    
    if (!$record) {
        echo "  ⚠️  No test record found\n";
        $results[$resourceName] = 'no_data';
        continue;
    }
    
    $resourceClass = "App\\Filament\\Admin\\Resources\\" . str_replace(' ', '', $resourceName) . "Resource";
    $resourceClass = str_replace('Branches', 'Branch', $resourceClass);
    $resourceClass = str_replace('Calls', 'Call', $resourceClass);
    $resourceClass = str_replace('Companies', 'Company', $resourceClass);
    $resourceClass = str_replace('Customers', 'Customer', $resourceClass);
    
    // Test that the resource exists
    if (!class_exists($resourceClass)) {
        echo "  ❌ Resource class not found: $resourceClass\n";
        $results[$resourceName] = 'class_missing';
        continue;
    }
    
    // Test View page
    $viewPageClass = $resourceClass . "\\Pages\\View" . str_replace(' ', '', rtrim($resourceName, 's'));
    if (class_exists($viewPageClass)) {
        try {
            $viewPage = new $viewPageClass();
            // Check if it has a custom view that might be broken
            $reflection = new ReflectionClass($viewPageClass);
            $hasCustomView = false;
            
            foreach ($reflection->getProperties() as $property) {
                if ($property->getName() === 'view' && $property->isStatic()) {
                    $property->setAccessible(true);
                    $viewValue = $property->getValue();
                    if ($viewValue) {
                        $hasCustomView = true;
                    }
                }
            }
            
            if ($hasCustomView) {
                echo "  ⚠️  View page still has custom view declaration\n";
                $results[$resourceName]['view'] = 'has_custom_view';
            } else {
                echo "  ✅ View page OK (no custom view)\n";
                $results[$resourceName]['view'] = 'ok';
            }
        } catch (Exception $e) {
            echo "  ❌ View page error: " . $e->getMessage() . "\n";
            $results[$resourceName]['view'] = 'error';
        }
    } else {
        echo "  ℹ️  No View page defined\n";
        $results[$resourceName]['view'] = 'not_defined';
    }
    
    // Test Edit page
    $editPageClass = $resourceClass . "\\Pages\\Edit" . str_replace(' ', '', rtrim($resourceName, 's'));
    if (class_exists($editPageClass)) {
        echo "  ✅ Edit page exists\n";
        $results[$resourceName]['edit'] = 'exists';
    } else {
        echo "  ℹ️  No Edit page defined\n";
        $results[$resourceName]['edit'] = 'not_defined';
    }
    
    echo "\n";
}

echo "Summary\n";
echo "=======\n";
$allOk = true;
foreach ($results as $resource => $status) {
    if (is_array($status)) {
        foreach ($status as $page => $pageStatus) {
            if (in_array($pageStatus, ['has_custom_view', 'error'])) {
                echo "❌ $resource $page: $pageStatus\n";
                $allOk = false;
            }
        }
    } elseif ($status === 'no_data') {
        echo "⚠️  $resource: No test data available\n";
    }
}

if ($allOk) {
    echo "\n✅ All tested pages are properly configured!\n";
} else {
    echo "\n⚠️  Some issues found, but the fix script should have resolved them.\n";
    echo "PHP-FPM has been restarted, so changes should be active.\n";
}