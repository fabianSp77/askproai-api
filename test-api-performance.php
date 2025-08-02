#!/usr/bin/env php
<?php

/**
 * API Performance Testing Script
 * Tests response times and throughput for business portal endpoints
 */

require_once __DIR__ . '/vendor/autoload.php';

class APIPerformanceTester
{
    private string $baseUrl;
    private array $results = [];
    
    public function __construct(string $baseUrl = 'https://api.askproai.de')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }
    
    public function runPerformanceTests(): void
    {
        echo "⚡ Business Portal API Performance Testing\n";
        echo str_repeat('=', 50) . "\n\n";
        
        $endpoints = [
            'Auth Check' => '/business/api/auth/check',
            'Dashboard Stats' => '/business/api/dashboard/stats',
            'Recent Calls' => '/business/api/dashboard/recent-calls',
            'Appointments' => '/business/api/appointments',
            'Calls List' => '/business/api/calls',
            'Billing Data' => '/business/api/billing',
            'Health Check' => '/api/health',
            'CSRF Token' => '/api/csrf-token'
        ];
        
        foreach ($endpoints as $name => $endpoint) {
            $this->testEndpointPerformance($name, $endpoint);
        }
        
        $this->generateReport();
    }
    
    private function testEndpointPerformance(string $name, string $endpoint): void
    {
        echo "🎯 Testing: {$name}\n";
        
        $measurements = [];
        $errors = 0;
        $iterations = 5;
        
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $response = $this->makeRequest($endpoint);
            $end = microtime(true);
            
            $responseTime = ($end - $start) * 1000; // Convert to milliseconds
            
            if ($response['status'] >= 500) {
                $errors++;
            }
            
            $measurements[] = [
                'response_time' => $responseTime,
                'status' => $response['status'],
                'size' => strlen($response['raw_body'])
            ];
            
            // Small delay between requests
            usleep(100000); // 100ms
        }
        
        $this->results[$name] = $this->calculateStats($measurements, $errors);
        $this->displayResults($name, $this->results[$name]);
        
        echo "\n";
    }
    
    private function calculateStats(array $measurements, int $errors): array
    {
        $responseTimes = array_column($measurements, 'response_time');
        $statuses = array_column($measurements, 'status');
        $sizes = array_column($measurements, 'size');
        
        sort($responseTimes);
        
        return [
            'avg_response_time' => array_sum($responseTimes) / count($responseTimes),
            'min_response_time' => min($responseTimes),
            'max_response_time' => max($responseTimes),
            'median_response_time' => $this->getPercentile($responseTimes, 50),
            'p95_response_time' => $this->getPercentile($responseTimes, 95),
            'total_requests' => count($measurements),
            'error_count' => $errors,
            'error_rate' => ($errors / count($measurements)) * 100,
            'avg_response_size' => array_sum($sizes) / count($sizes),
            'status_codes' => array_count_values($statuses)
        ];
    }
    
    private function getPercentile(array $sortedArray, float $percentile): float
    {
        $index = ($percentile / 100) * (count($sortedArray) - 1);
        $lower = floor($index);
        $upper = ceil($index);
        
        if ($lower == $upper) {
            return $sortedArray[$lower];
        }
        
        return $sortedArray[$lower] + ($index - $lower) * ($sortedArray[$upper] - $sortedArray[$lower]);
    }
    
    private function displayResults(string $name, array $stats): void
    {
        $avgTime = number_format($stats['avg_response_time'], 1);
        $p95Time = number_format($stats['p95_response_time'], 1);
        $errorRate = number_format($stats['error_rate'], 1);
        
        // Color coding based on performance
        $timeColor = match(true) {
            $stats['avg_response_time'] < 100 => "\033[32m", // Green
            $stats['avg_response_time'] < 500 => "\033[33m", // Yellow
            default => "\033[31m" // Red
        };
        
        $errorColor = $stats['error_rate'] > 0 ? "\033[31m" : "\033[32m";
        
        echo "   📊 Avg: {$timeColor}{$avgTime}ms\033[0m | ";
        echo "P95: {$timeColor}{$p95Time}ms\033[0m | ";
        echo "Errors: {$errorColor}{$errorRate}%\033[0m | ";
        echo "Status: " . implode(',', array_keys($stats['status_codes'])) . "\n";
    }
    
    private function makeRequest(string $endpoint): array
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: APIPerformanceTester/1.0'
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        
        $body = substr($response, $headerSize);
        
        return [
            'status' => $httpCode,
            'raw_body' => $body
        ];
    }
    
    private function generateReport(): void
    {
        echo str_repeat('=', 50) . "\n";
        echo "📈 Performance Summary\n";
        echo str_repeat('=', 50) . "\n";
        
        // Overall statistics
        $allTimes = [];
        $totalErrors = 0;
        $totalRequests = 0;
        
        foreach ($this->results as $stats) {
            $allTimes[] = $stats['avg_response_time'];
            $totalErrors += $stats['error_count'];
            $totalRequests += $stats['total_requests'];
        }
        
        $overallAvg = array_sum($allTimes) / count($allTimes);
        $overallErrorRate = ($totalErrors / $totalRequests) * 100;
        
        echo sprintf("🎯 Overall Average Response Time: %.1fms\n", $overallAvg);
        echo sprintf("❌ Overall Error Rate: %.1f%% (%d/%d)\n", $overallErrorRate, $totalErrors, $totalRequests);
        
        // Performance targets
        echo "\n🎯 Performance Targets:\n";
        $this->checkPerformanceTargets();
        
        // Slowest endpoints
        echo "\n🐌 Slowest Endpoints:\n";
        $sortedByTime = $this->results;
        uasort($sortedByTime, fn($a, $b) => $b['avg_response_time'] <=> $a['avg_response_time']);
        
        $count = 0;
        foreach ($sortedByTime as $name => $stats) {
            if ($count++ >= 3) break;
            echo sprintf("   %d. %s: %.1fms\n", $count, $name, $stats['avg_response_time']);
        }
        
        // Error analysis
        $errorEndpoints = array_filter($this->results, fn($stats) => $stats['error_count'] > 0);
        if (!empty($errorEndpoints)) {
            echo "\n💥 Endpoints with Errors:\n";
            foreach ($errorEndpoints as $name => $stats) {
                echo sprintf("   • %s: %d errors (%.1f%%)\n", 
                    $name, $stats['error_count'], $stats['error_rate']);
            }
        }
        
        echo "\n✅ Performance testing completed!\n";
    }
    
    private function checkPerformanceTargets(): void
    {
        $targets = [
            'API Response Time < 200ms' => function($results) {
                $count = 0;
                foreach ($results as $stats) {
                    if ($stats['avg_response_time'] < 200) $count++;
                }
                return [$count, count($results)];
            },
            'P95 Response Time < 500ms' => function($results) {
                $count = 0;
                foreach ($results as $stats) {
                    if ($stats['p95_response_time'] < 500) $count++;
                }
                return [$count, count($results)];
            },
            'Error Rate < 5%' => function($results) {
                $count = 0;
                foreach ($results as $stats) {
                    if ($stats['error_rate'] < 5) $count++;
                }
                return [$count, count($results)];
            }
        ];
        
        foreach ($targets as $targetName => $checker) {
            [$passed, $total] = $checker($this->results);
            $percentage = ($passed / $total) * 100;
            
            $icon = $percentage >= 80 ? '✅' : ($percentage >= 60 ? '⚠️' : '❌');
            echo sprintf("   %s %s: %d/%d (%.1f%%)\n", $icon, $targetName, $passed, $total, $percentage);
        }
    }
}

// Run the performance tests
$tester = new APIPerformanceTester();
$tester->runPerformanceTests();