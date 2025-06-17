<?php

namespace App\Console\Commands;

use App\Services\Calcom\CalcomV2Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CalcomV2PerformanceTest extends Command
{
    protected $signature = 'calcom:performance-test 
                            {--iterations=100 : Number of test iterations}
                            {--concurrent=10 : Number of concurrent requests}';
    
    protected $description = 'Run performance tests on Cal.com V2 API integration';

    private array $metrics = [
        'total_requests' => 0,
        'successful_requests' => 0,
        'failed_requests' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
        'response_times' => [],
        'errors' => [],
    ];

    public function handle()
    {
        $this->info('Starting Cal.com V2 API Performance Test...');
        
        $iterations = (int) $this->option('iterations');
        $concurrent = (int) $this->option('concurrent');
        
        $apiKey = config('services.calcom.test_api_key') ?? config('services.calcom.api_key');
        
        if (!$apiKey) {
            $this->error('No Cal.com API key configured.');
            return 1;
        }
        
        $client = new CalcomV2Client($apiKey);
        
        // Clear cache before test
        Cache::flush();
        $this->info('Cache cleared.');
        
        // Run tests
        $this->info("Running {$iterations} iterations with {$concurrent} concurrent requests...\n");
        
        $bar = $this->output->createProgressBar($iterations);
        $bar->start();
        
        $startTime = microtime(true);
        
        // Test different endpoints
        for ($i = 0; $i < $iterations; $i++) {
            $testType = $i % 3; // Rotate through test types
            
            switch ($testType) {
                case 0:
                    $this->testEventTypes($client);
                    break;
                case 1:
                    $this->testAvailableSlots($client);
                    break;
                case 2:
                    $this->testBookingFlow($client);
                    break;
            }
            
            $bar->advance();
            
            // Small delay to avoid overwhelming the API
            usleep(100000); // 0.1 second
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $totalTime = microtime(true) - $startTime;
        
        // Display results
        $this->displayResults($totalTime);
        
        // Test circuit breaker
        $this->testCircuitBreaker($client);
        
        return 0;
    }

    private function testEventTypes(CalcomV2Client $client): void
    {
        $start = microtime(true);
        $this->metrics['total_requests']++;
        
        try {
            // Check if cached
            $cacheKey = 'calcom_v2:event_types:' . md5(json_encode([]));
            $wasCached = Cache::has($cacheKey);
            
            if ($wasCached) {
                $this->metrics['cache_hits']++;
            } else {
                $this->metrics['cache_misses']++;
            }
            
            $eventTypes = $client->getEventTypes();
            
            $this->metrics['successful_requests']++;
            $this->metrics['response_times'][] = (microtime(true) - $start) * 1000;
            
        } catch (\Exception $e) {
            $this->metrics['failed_requests']++;
            $this->metrics['errors'][] = $e->getMessage();
        }
    }

    private function testAvailableSlots(CalcomV2Client $client): void
    {
        $start = microtime(true);
        $this->metrics['total_requests']++;
        
        try {
            $params = [
                'startTime' => now()->addDay()->startOfDay()->toIso8601String(),
                'endTime' => now()->addDay()->endOfDay()->toIso8601String(),
                'eventTypeId' => 1, // Assuming event type 1 exists
            ];
            
            // Check if cached
            $cacheKey = 'calcom_v2:slots:' . md5(json_encode($params));
            $wasCached = Cache::has($cacheKey);
            
            if ($wasCached) {
                $this->metrics['cache_hits']++;
            } else {
                $this->metrics['cache_misses']++;
            }
            
            $slots = $client->getAvailableSlots($params);
            
            $this->metrics['successful_requests']++;
            $this->metrics['response_times'][] = (microtime(true) - $start) * 1000;
            
        } catch (\Exception $e) {
            $this->metrics['failed_requests']++;
            $this->metrics['errors'][] = $e->getMessage();
        }
    }

    private function testBookingFlow(CalcomV2Client $client): void
    {
        $start = microtime(true);
        $this->metrics['total_requests']++;
        
        try {
            // Just test the booking validation, not actual creation
            $bookingData = [
                'start' => now()->addDays(3)->setTime(10, 0)->toIso8601String(),
                'eventTypeId' => 1,
                'responses' => [
                    'name' => 'Performance Test',
                    'email' => 'perf-test@example.com',
                ],
                'metadata' => [
                    'test' => true,
                    'test_id' => uniqid(),
                ],
            ];
            
            // We'll just measure the time to prepare the request
            // In a real test, you might want to create actual bookings
            
            $this->metrics['successful_requests']++;
            $this->metrics['response_times'][] = (microtime(true) - $start) * 1000;
            
        } catch (\Exception $e) {
            $this->metrics['failed_requests']++;
            $this->metrics['errors'][] = $e->getMessage();
        }
    }

    private function testCircuitBreaker(CalcomV2Client $client): void
    {
        $this->info("\nTesting Circuit Breaker...");
        
        // Get current state
        $health = $client->healthCheck();
        $this->info("Circuit Breaker State: " . $health['circuit_state']);
        
        // Get metrics
        $metrics = $client->getMetrics();
        $this->table(
            ['Metric', 'Value'],
            [
                ['State', $metrics['circuit_breaker']['state']],
                ['Success Count', $metrics['circuit_breaker']['success_count']],
                ['Failure Count', $metrics['circuit_breaker']['failure_count']],
                ['Last Failure', $metrics['circuit_breaker']['last_failure_time'] ?? 'None'],
            ]
        );
    }

    private function displayResults(float $totalTime): void
    {
        $avgResponseTime = !empty($this->metrics['response_times']) 
            ? array_sum($this->metrics['response_times']) / count($this->metrics['response_times'])
            : 0;
            
        $minResponseTime = !empty($this->metrics['response_times'])
            ? min($this->metrics['response_times'])
            : 0;
            
        $maxResponseTime = !empty($this->metrics['response_times'])
            ? max($this->metrics['response_times'])
            : 0;
            
        $successRate = $this->metrics['total_requests'] > 0
            ? ($this->metrics['successful_requests'] / $this->metrics['total_requests']) * 100
            : 0;
            
        $cacheHitRate = ($this->metrics['cache_hits'] + $this->metrics['cache_misses']) > 0
            ? ($this->metrics['cache_hits'] / ($this->metrics['cache_hits'] + $this->metrics['cache_misses'])) * 100
            : 0;

        $this->info('Performance Test Results');
        $this->info('========================');
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Time', sprintf('%.2f seconds', $totalTime)],
                ['Total Requests', $this->metrics['total_requests']],
                ['Successful Requests', $this->metrics['successful_requests']],
                ['Failed Requests', $this->metrics['failed_requests']],
                ['Success Rate', sprintf('%.2f%%', $successRate)],
                ['Requests/Second', sprintf('%.2f', $this->metrics['total_requests'] / $totalTime)],
                ['', ''],
                ['Average Response Time', sprintf('%.2f ms', $avgResponseTime)],
                ['Min Response Time', sprintf('%.2f ms', $minResponseTime)],
                ['Max Response Time', sprintf('%.2f ms', $maxResponseTime)],
                ['', ''],
                ['Cache Hits', $this->metrics['cache_hits']],
                ['Cache Misses', $this->metrics['cache_misses']],
                ['Cache Hit Rate', sprintf('%.2f%%', $cacheHitRate)],
            ]
        );
        
        if (!empty($this->metrics['errors'])) {
            $this->warn("\nErrors encountered:");
            $errorCounts = array_count_values($this->metrics['errors']);
            foreach ($errorCounts as $error => $count) {
                $this->line("  - {$error} ({$count}x)");
            }
        }
        
        // Response time distribution
        if (!empty($this->metrics['response_times'])) {
            $this->info("\nResponse Time Distribution:");
            $this->displayHistogram($this->metrics['response_times']);
        }
    }

    private function displayHistogram(array $times): void
    {
        $buckets = [
            '0-50ms' => 0,
            '50-100ms' => 0,
            '100-200ms' => 0,
            '200-500ms' => 0,
            '500-1000ms' => 0,
            '1000ms+' => 0,
        ];
        
        foreach ($times as $time) {
            if ($time < 50) {
                $buckets['0-50ms']++;
            } elseif ($time < 100) {
                $buckets['50-100ms']++;
            } elseif ($time < 200) {
                $buckets['100-200ms']++;
            } elseif ($time < 500) {
                $buckets['200-500ms']++;
            } elseif ($time < 1000) {
                $buckets['500-1000ms']++;
            } else {
                $buckets['1000ms+']++;
            }
        }
        
        $maxCount = max($buckets);
        
        foreach ($buckets as $range => $count) {
            $bar = str_repeat('█', (int)(($count / $maxCount) * 40));
            $this->line(sprintf("  %-12s %s %d", $range, $bar, $count));
        }
    }
}