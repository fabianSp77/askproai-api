#!/usr/bin/env php
<?php

/**
 * Load Testing Script for AskProAI
 * Tests system performance with concurrent requests
 */

echo "\033[1;34m=== ASKPROAI LOAD TEST ===\033[0m\n\n";

$baseUrl = $argv[1] ?? 'https://api.askproai.de';
$concurrentUsers = $argv[2] ?? 100;
$duration = $argv[3] ?? 60; // seconds

echo "Target: $baseUrl\n";
echo "Concurrent Users: $concurrentUsers\n";
echo "Duration: {$duration}s\n\n";

// Test scenarios
$scenarios = [
    [
        'name' => 'Health Check',
        'method' => 'GET',
        'endpoint' => '/api/health',
        'weight' => 20
    ],
    [
        'name' => 'List Agents',
        'method' => 'GET',
        'endpoint' => '/api/mcp/retell/agents/1',
        'weight' => 30
    ],
    [
        'name' => 'Check Availability',
        'method' => 'POST',
        'endpoint' => '/api/retell/check-intelligent-availability',
        'body' => [
            'date' => date('Y-m-d', strtotime('+1 day')),
            'time_preference' => 'morning',
            'duration' => 30
        ],
        'weight' => 25
    ],
    [
        'name' => 'Webhook Simulation',
        'method' => 'POST',
        'endpoint' => '/api/test/webhook',
        'body' => [
            'event_type' => 'call_ended',
            'call_id' => 'test_' . uniqid()
        ],
        'weight' => 25
    ]
];

// Results storage
$results = [
    'total_requests' => 0,
    'successful_requests' => 0,
    'failed_requests' => 0,
    'response_times' => [],
    'errors' => [],
    'scenario_stats' => []
];

// Initialize scenario stats
foreach ($scenarios as $scenario) {
    $results['scenario_stats'][$scenario['name']] = [
        'count' => 0,
        'success' => 0,
        'failed' => 0,
        'avg_response_time' => 0,
        'response_times' => []
    ];
}

/**
 * Execute single request
 */
function executeRequest($scenario, $baseUrl) {
    $url = $baseUrl . $scenario['endpoint'];
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if ($scenario['method'] === 'POST') {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($scenario['body'] ?? []));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $endTime = microtime(true);
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300 && !$error,
        'response_time' => ($endTime - $startTime) * 1000, // ms
        'http_code' => $httpCode,
        'error' => $error
    ];
}

/**
 * Select scenario based on weights
 */
function selectScenario($scenarios) {
    $totalWeight = array_sum(array_column($scenarios, 'weight'));
    $random = rand(1, $totalWeight);
    
    $currentWeight = 0;
    foreach ($scenarios as $scenario) {
        $currentWeight += $scenario['weight'];
        if ($random <= $currentWeight) {
            return $scenario;
        }
    }
    
    return $scenarios[0];
}

// Progress bar
function showProgress($current, $total) {
    $percent = ($current / $total) * 100;
    $bar = str_repeat('=', floor($percent / 2)) . '>';
    $bar = str_pad($bar, 51, ' ');
    echo "\r[{$bar}] " . number_format($percent, 1) . "%";
}

// Main test loop
echo "Starting load test...\n";
$startTime = time();
$requestCount = 0;

// Use multi-threading for concurrent requests
$children = [];

while ((time() - $startTime) < $duration) {
    // Maintain concurrent users
    while (count($children) < $concurrentUsers && (time() - $startTime) < $duration) {
        $pid = pcntl_fork();
        
        if ($pid == -1) {
            die("Could not fork\n");
        } elseif ($pid == 0) {
            // Child process
            $scenario = selectScenario($scenarios);
            $result = executeRequest($scenario, $baseUrl);
            
            // Write result to temp file
            $tempFile = "/tmp/loadtest_" . getmypid() . ".json";
            file_put_contents($tempFile, json_encode([
                'scenario' => $scenario['name'],
                'result' => $result
            ]));
            
            exit(0);
        } else {
            // Parent process
            $children[$pid] = time();
            $requestCount++;
        }
    }
    
    // Check for completed children
    foreach ($children as $pid => $startTime) {
        $status = null;
        $res = pcntl_waitpid($pid, $status, WNOHANG);
        
        if ($res == -1 || $res > 0) {
            unset($children[$pid]);
            
            // Read result
            $tempFile = "/tmp/loadtest_{$pid}.json";
            if (file_exists($tempFile)) {
                $data = json_decode(file_get_contents($tempFile), true);
                
                // Update statistics
                $results['total_requests']++;
                $scenarioName = $data['scenario'];
                $result = $data['result'];
                
                $results['scenario_stats'][$scenarioName]['count']++;
                
                if ($result['success']) {
                    $results['successful_requests']++;
                    $results['scenario_stats'][$scenarioName]['success']++;
                } else {
                    $results['failed_requests']++;
                    $results['scenario_stats'][$scenarioName]['failed']++;
                    $results['errors'][] = [
                        'scenario' => $scenarioName,
                        'error' => $result['error'] ?: "HTTP {$result['http_code']}"
                    ];
                }
                
                $results['response_times'][] = $result['response_time'];
                $results['scenario_stats'][$scenarioName]['response_times'][] = $result['response_time'];
                
                unlink($tempFile);
            }
        }
    }
    
    // Show progress
    showProgress(time() - $startTime, $duration);
    
    usleep(10000); // 10ms
}

// Wait for remaining children
while (count($children) > 0) {
    $pid = pcntl_wait($status);
    unset($children[$pid]);
}

echo "\n\n";

// Calculate statistics
$avgResponseTime = count($results['response_times']) > 0 
    ? array_sum($results['response_times']) / count($results['response_times']) 
    : 0;

$minResponseTime = count($results['response_times']) > 0 
    ? min($results['response_times']) 
    : 0;

$maxResponseTime = count($results['response_times']) > 0 
    ? max($results['response_times']) 
    : 0;

// Calculate percentiles
sort($results['response_times']);
$p50 = $results['response_times'][floor(count($results['response_times']) * 0.5)] ?? 0;
$p95 = $results['response_times'][floor(count($results['response_times']) * 0.95)] ?? 0;
$p99 = $results['response_times'][floor(count($results['response_times']) * 0.99)] ?? 0;

// Display results
echo "\033[1;34m=== LOAD TEST RESULTS ===\033[0m\n\n";

echo "Total Requests: " . number_format($results['total_requests']) . "\n";
echo "Successful: \033[1;32m" . number_format($results['successful_requests']) . "\033[0m\n";
echo "Failed: \033[1;31m" . number_format($results['failed_requests']) . "\033[0m\n";
echo "Success Rate: " . number_format(($results['successful_requests'] / $results['total_requests']) * 100, 2) . "%\n";
echo "Requests/Second: " . number_format($results['total_requests'] / $duration, 2) . "\n\n";

echo "Response Times:\n";
echo "  Average: " . number_format($avgResponseTime, 2) . "ms\n";
echo "  Min: " . number_format($minResponseTime, 2) . "ms\n";
echo "  Max: " . number_format($maxResponseTime, 2) . "ms\n";
echo "  P50: " . number_format($p50, 2) . "ms\n";
echo "  P95: " . number_format($p95, 2) . "ms\n";
echo "  P99: " . number_format($p99, 2) . "ms\n\n";

echo "Scenario Breakdown:\n";
foreach ($results['scenario_stats'] as $name => $stats) {
    if ($stats['count'] > 0) {
        $avgTime = array_sum($stats['response_times']) / count($stats['response_times']);
        $successRate = ($stats['success'] / $stats['count']) * 100;
        
        echo "  $name:\n";
        echo "    Requests: " . number_format($stats['count']) . "\n";
        echo "    Success Rate: " . number_format($successRate, 2) . "%\n";
        echo "    Avg Response: " . number_format($avgTime, 2) . "ms\n";
    }
}

// Error summary
if (count($results['errors']) > 0) {
    echo "\nTop Errors:\n";
    $errorCounts = array_count_values(array_column($results['errors'], 'error'));
    arsort($errorCounts);
    
    $i = 0;
    foreach ($errorCounts as $error => $count) {
        echo "  - $error: $count times\n";
        if (++$i >= 5) break;
    }
}

// Performance assessment
echo "\n\033[1;34m=== PERFORMANCE ASSESSMENT ===\033[0m\n";

$passed = true;

// Success rate check
if (($results['successful_requests'] / $results['total_requests']) >= 0.99) {
    echo "\033[1;32m✓ Success Rate: PASSED\033[0m (>99%)\n";
} else {
    echo "\033[1;31m✗ Success Rate: FAILED\033[0m (<99%)\n";
    $passed = false;
}

// Response time check
if ($p95 < 200) {
    echo "\033[1;32m✓ Response Time P95: PASSED\033[0m (<200ms)\n";
} else {
    echo "\033[1;31m✗ Response Time P95: FAILED\033[0m (>200ms)\n";
    $passed = false;
}

// Throughput check
$rps = $results['total_requests'] / $duration;
if ($rps > 50) {
    echo "\033[1;32m✓ Throughput: PASSED\033[0m (>50 req/s)\n";
} else {
    echo "\033[1;31m✗ Throughput: FAILED\033[0m (<50 req/s)\n";
    $passed = false;
}

echo "\n";
if ($passed) {
    echo "\033[1;32m✅ LOAD TEST PASSED - System is ready for production!\033[0m\n";
} else {
    echo "\033[1;31m❌ LOAD TEST FAILED - Performance optimization needed!\033[0m\n";
}

// Save detailed report
$reportFile = "load-test-report-" . date('Y-m-d-H-i-s') . ".json";
file_put_contents($reportFile, json_encode($results, JSON_PRETTY_PRINT));
echo "\nDetailed report saved to: $reportFile\n";