<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing ML Dashboard Livewire Request ===\n\n";

// First, let's check what the actual component name should be
echo "1. Finding component name:\n";
$routes = Route::getRoutes();
foreach ($routes as $route) {
    if (strpos($route->uri(), 'training-dashboard') !== false) {
        echo "   - Route: " . $route->uri() . "\n";
        echo "   - Action: " . $route->getActionName() . "\n";
    }
}
echo "\n";

// Create a proper Livewire update request
$componentName = 'filament.admin.pages.m-l-training-dashboard-livewire';
$componentPath = 'admin/m-l-training-dashboard-livewire';

// Create snapshot data
$snapshot = [
    'data' => [
        'trainingStats' => [],
        'modelInfo' => [],
        'recentPredictions' => [],
        'activeJobs' => [],
        'showInstructions' => true,
        'sentimentDistribution' => [],
        'performanceMetrics' => [],
        'agentStats' => [],
    ],
    'memo' => [
        'id' => 'ml-training-dashboard-' . uniqid(),
        'name' => $componentName,
        'path' => $componentPath,
        'method' => 'GET',
        'children' => [],
        'scripts' => [],
        'assets' => [],
        'errors' => [],
        'locale' => 'en',
    ]
];

// Create the request payload
$payload = [
    'components' => [
        [
            'snapshot' => json_encode($snapshot),
            'updates' => [],
            'calls' => [
                [
                    'path' => '',
                    'method' => 'startTraining',
                    'params' => [
                        [
                            'require_audio' => true,
                            'exclude_test_calls' => true,
                            'duration_filter' => 'min_30',
                        ]
                    ]
                ]
            ]
        ]
    ]
];

echo "2. Request payload structure:\n";
echo "   - Component name: {$componentName}\n";
echo "   - Component path: {$componentPath}\n";
echo "   - Method to call: startTraining\n";
echo "\n";

// Make the request
echo "3. Making Livewire update request:\n";
try {
    $request = Request::create('/livewire/update', 'POST', $payload);
    $request->headers->set('X-Livewire', 'true');
    $request->headers->set('X-CSRF-TOKEN', csrf_token());
    $request->headers->set('Accept', 'application/json');
    $request->headers->set('Content-Type', 'application/json');
    
    // Set up session
    $session = app('session.store');
    $session->start();
    $request->setLaravelSession($session);
    
    $response = $app->handle($request);
    
    echo "   - Status: " . $response->getStatusCode() . "\n";
    echo "   - Content-Type: " . $response->headers->get('Content-Type') . "\n";
    
    $content = $response->getContent();
    $decoded = json_decode($content, true);
    
    if ($response->getStatusCode() !== 200) {
        echo "   - Error Response:\n";
        if ($decoded) {
            echo "     " . json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "     " . substr($content, 0, 1000) . "\n";
        }
        
        // Check Laravel log for the actual error
        echo "\n4. Checking error log:\n";
        $log = file_get_contents(storage_path('logs/laravel.log'));
        $lines = explode("\n", $log);
        $recentLines = array_slice($lines, -50);
        $errorFound = false;
        
        foreach ($recentLines as $line) {
            if (strpos($line, 'MLTraining') !== false || strpos($line, 'Livewire') !== false) {
                echo "   " . $line . "\n";
                $errorFound = true;
            }
        }
        
        if (!$errorFound) {
            echo "   No specific errors found in log\n";
        }
    } else {
        echo "   - Success!\n";
        if ($decoded && isset($decoded['components'])) {
            echo "   - Response has " . count($decoded['components']) . " components\n";
        }
    }
    
} catch (\Exception $e) {
    echo "   - Exception: " . $e->getMessage() . "\n";
    echo "   - File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   - Trace:\n";
    $trace = collect($e->getTrace())->take(5);
    foreach ($trace as $i => $frame) {
        echo "     #{$i} " . ($frame['file'] ?? 'unknown') . ":" . ($frame['line'] ?? '?') . " " . ($frame['function'] ?? '') . "\n";
    }
}

echo "\n=== Test Complete ===\n";