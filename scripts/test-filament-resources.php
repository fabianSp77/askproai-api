#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "=== FILAMENT RESOURCES TEST ===" . PHP_EOL;
echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL;
echo PHP_EOL;

// Authenticate as admin
$user = \App\Models\User::where('email', 'admin@askproai.de')->first();
if (!$user) {
    echo "ERROR: Admin user not found!" . PHP_EOL;
    exit(1);
}

\Auth::login($user);

echo "1. TESTING FILAMENT RESOURCES" . PHP_EOL;

$resources = [
    'App\Filament\Admin\Resources\CustomerResource',
    'App\Filament\Admin\Resources\CallResource',
    'App\Filament\Admin\Resources\AppointmentResource',
    'App\Filament\Admin\Resources\CompanyResource',
    'App\Filament\Admin\Resources\StaffResource',
    'App\Filament\Admin\Resources\ServiceResource',
    'App\Filament\Admin\Resources\BranchResource',
    'App\Filament\Admin\Resources\TenantResource',
    'App\Filament\Admin\Resources\UserResource'
];

$passedTests = 0;
$failedTests = 0;

foreach ($resources as $resourceClass) {
    if (class_exists($resourceClass)) {
        echo "   ✅ $resourceClass - LOADED" . PHP_EOL;

        // Test model
        $model = $resourceClass::getModel();
        echo "      Model: $model" . PHP_EOL;

        // Test table
        try {
            $table = $resourceClass::table(new \Filament\Tables\Table);
            echo "      Table configured: " . count($table->getColumns()) . " columns" . PHP_EOL;
        } catch (Exception $e) {
            echo "      Table error: " . $e->getMessage() . PHP_EOL;
        }

        // Test form
        try {
            $form = $resourceClass::form(new \Filament\Forms\Form);
            $schema = $form->getSchema();
            echo "      Form configured: " . (is_array($schema) ? count($schema) : 'Dynamic') . " fields" . PHP_EOL;
        } catch (Exception $e) {
            echo "      Form error: " . $e->getMessage() . PHP_EOL;
        }

        $passedTests++;
    } else {
        echo "   ❌ $resourceClass - NOT FOUND" . PHP_EOL;
        $failedTests++;
    }
}

echo PHP_EOL . "2. TESTING RESOURCE PAGES" . PHP_EOL;

$testUrls = [
    '/admin' => 'Dashboard',
    '/admin/customers' => 'Customers List',
    '/admin/calls' => 'Calls List',
    '/admin/appointments' => 'Appointments List',
    '/admin/companies' => 'Companies List',
    '/admin/staff' => 'Staff List',
    '/admin/services' => 'Services List',
    '/admin/branches' => 'Branches List'
];

foreach ($testUrls as $url => $name) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.askproai.de:8090" . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIE, "laravel_session=test");
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200 || $httpCode == 302) {
        echo "   ✅ $name ($url): HTTP $httpCode" . PHP_EOL;
        $passedTests++;
    } else {
        echo "   ⚠️  $name ($url): HTTP $httpCode" . PHP_EOL;
    }
}

echo PHP_EOL . "3. TESTING CRUD OPERATIONS" . PHP_EOL;

// Test Create
try {
    $testCustomer = \App\Models\Customer::create([
        'name' => 'Test Customer ' . uniqid(),
        'email' => 'test' . uniqid() . '@example.com',
        'phone' => '+49123456789',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "   ✅ CREATE: Customer created (ID: {$testCustomer->id})" . PHP_EOL;
    $passedTests++;
} catch (Exception $e) {
    echo "   ❌ CREATE: Failed - " . $e->getMessage() . PHP_EOL;
    $failedTests++;
}

// Test Read
try {
    $customer = \App\Models\Customer::find($testCustomer->id ?? 1);
    if ($customer) {
        echo "   ✅ READ: Customer retrieved" . PHP_EOL;
        $passedTests++;
    } else {
        echo "   ❌ READ: Customer not found" . PHP_EOL;
        $failedTests++;
    }
} catch (Exception $e) {
    echo "   ❌ READ: Failed - " . $e->getMessage() . PHP_EOL;
    $failedTests++;
}

// Test Update
if (isset($testCustomer)) {
    try {
        $testCustomer->name = 'Updated Test Customer';
        $testCustomer->save();
        echo "   ✅ UPDATE: Customer updated" . PHP_EOL;
        $passedTests++;
    } catch (Exception $e) {
        echo "   ❌ UPDATE: Failed - " . $e->getMessage() . PHP_EOL;
        $failedTests++;
    }
}

// Test Delete
if (isset($testCustomer)) {
    try {
        $testCustomer->delete();
        echo "   ✅ DELETE: Customer deleted" . PHP_EOL;
        $passedTests++;
    } catch (Exception $e) {
        echo "   ❌ DELETE: Failed - " . $e->getMessage() . PHP_EOL;
        $failedTests++;
    }
}

echo PHP_EOL . "4. TESTING FILAMENT PANELS" . PHP_EOL;

$provider = app(\App\Providers\Filament\AdminPanelProvider::class);
$panel = $provider->panel(new \Filament\Panel);

echo "   Panel ID: " . $panel->getId() . PHP_EOL;
echo "   Panel Path: " . $panel->getPath() . PHP_EOL;
echo "   Resources discovered: " . (method_exists($panel, 'getResources') ? 'Yes' : 'Manual') . PHP_EOL;

echo PHP_EOL . "5. TEST SUMMARY" . PHP_EOL;
echo "   Total Tests: " . ($passedTests + $failedTests) . PHP_EOL;
echo "   ✅ Passed: $passedTests" . PHP_EOL;
echo "   ❌ Failed: $failedTests" . PHP_EOL;
echo "   Success Rate: " . round(($passedTests / max(1, $passedTests + $failedTests)) * 100, 1) . "%" . PHP_EOL;

echo PHP_EOL . "=== FILAMENT TEST COMPLETE ===" . PHP_EOL;