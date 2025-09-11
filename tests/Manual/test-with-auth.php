<?php

use Illuminate\Support\Facades\Auth;
use App\Models\User;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Login as admin
$admin = User::where('email', 'admin@askproai.de')->first();
if (!$admin) {
    // Try to find any admin user
    $admin = User::first();
}

if ($admin) {
    Auth::login($admin);
    echo "✅ Authenticated as: {$admin->email}\n\n";
} else {
    echo "❌ No admin user found. Creating test admin...\n";
    $admin = User::create([
        'name' => 'Test Admin',
        'email' => 'test@admin.com',
        'password' => bcrypt('password'),
    ]);
    Auth::login($admin);
}

// Test all resources
$resources = [
    'appointments' => \App\Models\Appointment::class,
    'branches' => \App\Models\Branch::class,
    'calls' => \App\Models\Call::class,
    'companies' => \App\Models\Company::class,
    'customers' => \App\Models\Customer::class,
    'services' => \App\Models\Service::class,
    'staff' => \App\Models\Staff::class,
    'users' => \App\Models\User::class,
    'working-hours' => \App\Models\WorkingHour::class,
    'integrations' => \App\Models\Integration::class,
];

echo "🔍 Testing Admin Portal Pages with Authentication\n";
echo str_repeat("=", 60) . "\n\n";

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

foreach ($resources as $resource => $modelClass) {
    echo "📁 Resource: " . ucfirst($resource) . "\n";
    
    // Check if model exists and get first record
    $model = null;
    if (class_exists($modelClass)) {
        $model = $modelClass::first();
        if ($model) {
            echo "  📊 Found sample record: ID = {$model->id}\n";
        } else {
            echo "  ⚠️  No records found in database\n";
        }
    }
    
    // Test List page
    $listPath = "/admin/{$resource}";
    $request = Illuminate\Http\Request::create($listPath, 'GET');
    $request->setUserResolver(function () use ($admin) { return $admin; });
    
    try {
        $response = $httpKernel->handle($request);
        $statusCode = $response->getStatusCode();
        $content = $response->getContent();
        
        // Check for Flowbite components
        $hasFlowbite = (
            strpos($content, 'flowbite-table') !== false ||
            strpos($content, 'flowbite-form') !== false ||
            strpos($content, 'flowbite-card') !== false ||
            strpos($content, 'x-admin.flowbite') !== false
        );
        
        echo "  " . ($statusCode == 200 ? "✅" : "❌") . " List: {$listPath} - HTTP {$statusCode}";
        if ($hasFlowbite) echo " [Flowbite ✓]";
        echo "\n";
        
    } catch (\Exception $e) {
        echo "  ❌ List: {$listPath} - Error: " . get_class($e) . "\n";
    }
    
    // Test Create page
    $createPath = "/admin/{$resource}/create";
    $request = Illuminate\Http\Request::create($createPath, 'GET');
    $request->setUserResolver(function () use ($admin) { return $admin; });
    
    try {
        $response = $httpKernel->handle($request);
        $statusCode = $response->getStatusCode();
        $content = $response->getContent();
        
        $hasFlowbite = (strpos($content, 'flowbite-form') !== false || strpos($content, 'x-admin.flowbite') !== false);
        
        echo "  " . ($statusCode == 200 ? "✅" : "❌") . " Create: {$createPath} - HTTP {$statusCode}";
        if ($hasFlowbite) echo " [Flowbite ✓]";
        echo "\n";
        
    } catch (\Exception $e) {
        echo "  ❌ Create: {$createPath} - Error: " . get_class($e) . "\n";
    }
    
    // Test View/Edit pages if record exists
    if ($model) {
        $viewPath = "/admin/{$resource}/{$model->id}";
        $editPath = "/admin/{$resource}/{$model->id}/edit";
        
        // Test View
        $request = Illuminate\Http\Request::create($viewPath, 'GET');
        $request->setUserResolver(function () use ($admin) { return $admin; });
        
        try {
            $response = $httpKernel->handle($request);
            $statusCode = $response->getStatusCode();
            echo "  " . ($statusCode == 200 ? "✅" : "⚠️") . " View: {$viewPath} - HTTP {$statusCode}\n";
        } catch (\Exception $e) {
            echo "  ⚠️  View: {$viewPath} - " . get_class($e) . "\n";
        }
        
        // Test Edit
        $request = Illuminate\Http\Request::create($editPath, 'GET');
        $request->setUserResolver(function () use ($admin) { return $admin; });
        
        try {
            $response = $httpKernel->handle($request);
            $statusCode = $response->getStatusCode();
            $content = $response->getContent();
            
            $hasFlowbite = (strpos($content, 'flowbite-form') !== false || strpos($content, 'x-admin.flowbite') !== false);
            
            echo "  " . ($statusCode == 200 ? "✅" : "⚠️") . " Edit: {$editPath} - HTTP {$statusCode}";
            if ($hasFlowbite) echo " [Flowbite ✓]";
            echo "\n";
        } catch (\Exception $e) {
            echo "  ⚠️  Edit: {$editPath} - " . get_class($e) . "\n";
        }
    }
    
    echo "\n";
}

echo str_repeat("=", 60) . "\n";
echo "✨ Test completed!\n";