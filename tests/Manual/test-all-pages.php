<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Define all resource endpoints to test
$resources = [
    'appointments',
    'branches', 
    'calls',
    'companies',
    'customers',
    'services',
    'staff',
    'users',
    'working-hours',
    'integrations',
];

$pageTypes = [
    '' => 'List',
    '/create' => 'Create',
    '/1/edit' => 'Edit',
    '/1' => 'View'
];

$results = [];
$errors = [];

echo "\n🔍 Testing All Admin Portal Pages with Flowbite Implementation\n";
echo "=" . str_repeat("=", 60) . "\n\n";

foreach ($resources as $resource) {
    echo "📁 Testing Resource: " . ucfirst($resource) . "\n";
    
    foreach ($pageTypes as $suffix => $type) {
        $path = "/admin/{$resource}{$suffix}";
        
        // Create request
        $request = Illuminate\Http\Request::create($path, 'GET');
        
        try {
            // Handle request
            $response = $kernel->handle($request);
            $statusCode = $response->getStatusCode();
            
            // Check for specific Flowbite components in response
            $content = $response->getContent();
            $hasFlowbiteTable = strpos($content, 'flowbite-table') !== false;
            $hasFlowbiteForm = strpos($content, 'flowbite-form') !== false;
            $hasFlowbiteCard = strpos($content, 'flowbite-card') !== false;
            $hasFilamentPage = strpos($content, 'filament-panels::page') !== false;
            
            $flowbiteComponents = [];
            if ($hasFlowbiteTable) $flowbiteComponents[] = 'table';
            if ($hasFlowbiteForm) $flowbiteComponents[] = 'form';
            if ($hasFlowbiteCard) $flowbiteComponents[] = 'card';
            
            if ($statusCode == 200) {
                echo "  ✅ {$type} Page: {$path} - HTTP {$statusCode}";
                if (!empty($flowbiteComponents)) {
                    echo " [Flowbite: " . implode(', ', $flowbiteComponents) . "]";
                }
                echo "\n";
                $results[$resource][$type] = 'SUCCESS';
            } elseif ($statusCode == 403) {
                echo "  🔒 {$type} Page: {$path} - HTTP {$statusCode} (Auth Required)\n";
                $results[$resource][$type] = 'AUTH_REQUIRED';
            } elseif ($statusCode == 404) {
                echo "  ⚠️  {$type} Page: {$path} - HTTP {$statusCode} (Not Found)\n";
                $results[$resource][$type] = 'NOT_FOUND';
            } else {
                echo "  ❌ {$type} Page: {$path} - HTTP {$statusCode}\n";
                $results[$resource][$type] = 'ERROR';
                $errors[] = "{$resource}/{$type}: HTTP {$statusCode}";
            }
            
        } catch (\Exception $e) {
            echo "  ❌ {$type} Page: {$path} - Exception: " . $e->getMessage() . "\n";
            $results[$resource][$type] = 'EXCEPTION';
            $errors[] = "{$resource}/{$type}: " . $e->getMessage();
        }
    }
    echo "\n";
}

// Test special pages
echo "📁 Testing Special Pages\n";
$specialPages = [
    '/admin' => 'Dashboard',
    '/admin/login' => 'Login',
];

foreach ($specialPages as $path => $name) {
    $request = Illuminate\Http\Request::create($path, 'GET');
    
    try {
        $response = $kernel->handle($request);
        $statusCode = $response->getStatusCode();
        
        if ($statusCode == 200) {
            echo "  ✅ {$name}: {$path} - HTTP {$statusCode}\n";
        } elseif ($statusCode == 403) {
            echo "  🔒 {$name}: {$path} - HTTP {$statusCode} (Auth Required)\n";
        } else {
            echo "  ❌ {$name}: {$path} - HTTP {$statusCode}\n";
            $errors[] = "{$name}: HTTP {$statusCode}";
        }
    } catch (\Exception $e) {
        echo "  ❌ {$name}: {$path} - Exception: " . $e->getMessage() . "\n";
        $errors[] = "{$name}: " . $e->getMessage();
    }
}

// Summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 TEST SUMMARY\n";
echo str_repeat("=", 60) . "\n\n";

$totalTests = 0;
$successCount = 0;
$authRequiredCount = 0;
$notFoundCount = 0;
$errorCount = 0;

foreach ($results as $resource => $types) {
    foreach ($types as $type => $status) {
        $totalTests++;
        if ($status === 'SUCCESS') $successCount++;
        elseif ($status === 'AUTH_REQUIRED') $authRequiredCount++;
        elseif ($status === 'NOT_FOUND') $notFoundCount++;
        else $errorCount++;
    }
}

echo "Total Pages Tested: {$totalTests}\n";
echo "✅ Successful: {$successCount}\n";
echo "🔒 Auth Required: {$authRequiredCount}\n";
echo "⚠️  Not Found: {$notFoundCount}\n";
echo "❌ Errors: {$errorCount}\n";

if (!empty($errors)) {
    echo "\n🚨 ERRORS DETECTED:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
} else {
    echo "\n✨ All tests completed without critical errors!\n";
}

echo "\n";