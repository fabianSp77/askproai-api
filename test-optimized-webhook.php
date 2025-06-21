<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "🧪 Testing Optimized Webhook Performance\n";
echo "========================================\n\n";

// Test webhook payload
$testPayload = [
    'event' => 'call_ended',
    'call' => [
        'id' => 'test_' . uniqid(),
        'from_number' => '+4930123456789',
        'to_number' => '+4930183793369',
        'duration' => 120,
        'start_timestamp' => now()->subMinutes(2)->toIso8601String(),
        'end_timestamp' => now()->toIso8601String(),
    ],
    'transcript' => 'Ich möchte einen Termin für nächste Woche Donnerstag um 14 Uhr.',
    'metadata' => [
        'customer_name' => 'Test Kunde',
        'requested_service' => 'Beratung',
    ],
];

// Test endpoints
$endpoints = [
    'Original' => '/api/retell/webhook',
    'Optimized' => '/api/retell/optimized-webhook',
];

$baseUrl = config('app.url', 'http://localhost');
$results = [];

foreach ($endpoints as $name => $endpoint) {
    echo "Testing {$name} endpoint: {$endpoint}\n";
    
    $times = [];
    $errors = 0;
    
    // Run 10 tests
    for ($i = 1; $i <= 10; $i++) {
        try {
            $payload = $testPayload;
            $payload['call']['id'] = 'test_' . uniqid(); // Unique ID for each test
            
            $start = microtime(true);
            
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Test-Request' => 'true',
                ])
                ->post($baseUrl . $endpoint, $payload);
            
            $duration = (microtime(true) - $start) * 1000; // Convert to ms
            
            if ($response->successful()) {
                $times[] = $duration;
                echo "  Test {$i}: {$duration}ms ✅\n";
            } else {
                $errors++;
                echo "  Test {$i}: Failed (Status: {$response->status()}) ❌\n";
            }
            
            // Small delay between requests
            usleep(100000); // 100ms
            
        } catch (Exception $e) {
            $errors++;
            echo "  Test {$i}: Error - {$e->getMessage()} ❌\n";
        }
    }
    
    // Calculate statistics
    if (count($times) > 0) {
        $avg = array_sum($times) / count($times);
        $min = min($times);
        $max = max($times);
        
        $results[$name] = [
            'avg' => round($avg, 2),
            'min' => round($min, 2),
            'max' => round($max, 2),
            'errors' => $errors,
            'success_rate' => ((10 - $errors) / 10) * 100,
        ];
    } else {
        $results[$name] = [
            'avg' => 0,
            'min' => 0,
            'max' => 0,
            'errors' => $errors,
            'success_rate' => 0,
        ];
    }
    
    echo "\n";
}

// Display results
echo "\n📊 Performance Comparison\n";
echo "========================\n\n";

echo sprintf("%-15s %-10s %-10s %-10s %-10s %-15s\n", 
    "Endpoint", "Avg (ms)", "Min (ms)", "Max (ms)", "Errors", "Success Rate");
echo str_repeat("-", 75) . "\n";

foreach ($results as $name => $stats) {
    echo sprintf("%-15s %-10s %-10s %-10s %-10s %-15s\n",
        $name,
        $stats['avg'],
        $stats['min'],
        $stats['max'],
        $stats['errors'],
        $stats['success_rate'] . '%'
    );
}

// Calculate improvement
if (isset($results['Original']) && isset($results['Optimized']) && $results['Original']['avg'] > 0) {
    $improvement = (($results['Original']['avg'] - $results['Optimized']['avg']) / $results['Original']['avg']) * 100;
    
    echo "\n";
    echo "🚀 Performance Improvement: " . round($improvement, 2) . "%\n";
    echo "⏱️  Time Saved per Request: " . round($results['Original']['avg'] - $results['Optimized']['avg'], 2) . "ms\n";
}

// Check if optimized endpoint meets target
if (isset($results['Optimized']) && $results['Optimized']['avg'] < 50) {
    echo "\n✅ Optimized endpoint meets <50ms target!\n";
} else {
    echo "\n⚠️  Optimized endpoint does not meet <50ms target yet.\n";
}

echo "\n";