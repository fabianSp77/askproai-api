<?php

namespace Tests\Feature;

use App\Debug\MemoryDumper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Test suite to reproduce memory exhaustion issues
 *
 * Strategy: Systematically vary conditions to identify trigger
 */
class MemoryReproductionTest extends TestCase
{
    private const MEMORY_LIMIT = 2048 * 1024 * 1024; // 2GB
    private const WARNING_THRESHOLD = 0.75; // 75% of limit

    /**
     * Test 1: Fresh session vs existing session
     */
    public function test_memory_with_fresh_session()
    {
        $this->withoutMiddleware();

        $memoryBefore = memory_get_usage(true);

        $response = $this->get('/filament/admin');

        $memoryAfter = memory_get_usage(true);
        $delta = $memoryAfter - $memoryBefore;

        Log::info('Fresh session memory test', [
            'before_mb' => round($memoryBefore / 1024 / 1024, 2),
            'after_mb' => round($memoryAfter / 1024 / 1024, 2),
            'delta_mb' => round($delta / 1024 / 1024, 2),
        ]);

        $this->assertLessThan(
            self::MEMORY_LIMIT * self::WARNING_THRESHOLD,
            $memoryAfter,
            'Memory usage exceeded warning threshold with fresh session'
        );
    }

    /**
     * Test 2: Session with varying data sizes
     */
    public function test_memory_with_large_session()
    {
        $this->withoutMiddleware();

        // Simulate large session data (like permissions, navigation state)
        session()->put('large_data', [
            'permissions' => array_fill(0, 1000, 'permission_' . str_repeat('x', 100)),
            'navigation' => array_fill(0, 100, ['label' => str_repeat('x', 1000)]),
            'user_data' => str_repeat('x', 1024 * 1024), // 1MB
        ]);

        $sessionSize = strlen(serialize(session()->all()));
        $memoryBefore = memory_get_usage(true);

        $response = $this->get('/filament/admin');

        $memoryAfter = memory_get_usage(true);
        $delta = $memoryAfter - $memoryBefore;

        Log::info('Large session memory test', [
            'session_size_mb' => round($sessionSize / 1024 / 1024, 2),
            'before_mb' => round($memoryBefore / 1024 / 1024, 2),
            'after_mb' => round($memoryAfter / 1024 / 1024, 2),
            'delta_mb' => round($delta / 1024 / 1024, 2),
        ]);

        $this->assertLessThan(
            self::MEMORY_LIMIT,
            $memoryAfter,
            'Memory exhausted with large session'
        );
    }

    /**
     * Test 3: Repeated requests (session accumulation)
     */
    public function test_memory_with_repeated_requests()
    {
        $this->withoutMiddleware();

        $iterations = 10;
        $memoryProgression = [];

        for ($i = 0; $i < $iterations; $i++) {
            $memoryBefore = memory_get_usage(true);

            $this->get('/filament/admin');

            $memoryAfter = memory_get_usage(true);
            $memoryProgression[] = [
                'iteration' => $i + 1,
                'memory_mb' => round($memoryAfter / 1024 / 1024, 2),
                'delta_mb' => round(($memoryAfter - $memoryBefore) / 1024 / 1024, 2),
            ];

            // Check for accumulation pattern
            if ($i > 0) {
                $growth = $memoryProgression[$i]['memory_mb'] - $memoryProgression[$i - 1]['memory_mb'];

                if ($growth > 50) { // 50MB growth per request
                    Log::warning('Memory accumulation detected', [
                        'iteration' => $i + 1,
                        'growth_mb' => round($growth, 2),
                    ]);
                }
            }
        }

        Log::info('Repeated requests memory progression', $memoryProgression);

        // Check if memory keeps growing linearly (leak indicator)
        $firstHalf = array_slice($memoryProgression, 0, 5);
        $secondHalf = array_slice($memoryProgression, 5, 5);

        $firstAvg = array_sum(array_column($firstHalf, 'delta_mb')) / 5;
        $secondAvg = array_sum(array_column($secondHalf, 'delta_mb')) / 5;

        $this->assertLessThan(
            $firstAvg * 1.5,
            $secondAvg,
            'Memory leak detected: growth accelerating'
        );
    }

    /**
     * Test 4: Different user permission sets
     */
    public function test_memory_with_varying_permissions()
    {
        $this->withoutMiddleware();

        $permissionCounts = [10, 100, 500, 1000];
        $results = [];

        foreach ($permissionCounts as $count) {
            // Create user with specific permission count
            $user = $this->createUserWithPermissions($count);

            $this->actingAs($user);

            $memoryBefore = memory_get_usage(true);

            $response = $this->get('/filament/admin');

            $memoryAfter = memory_get_usage(true);

            $results[] = [
                'permission_count' => $count,
                'memory_mb' => round($memoryAfter / 1024 / 1024, 2),
                'delta_mb' => round(($memoryAfter - $memoryBefore) / 1024 / 1024, 2),
            ];
        }

        Log::info('Permission count vs memory usage', $results);

        // Check if memory scales linearly with permissions (expected)
        // or exponentially (problem)
        $ratio = $results[3]['delta_mb'] / $results[0]['delta_mb'];

        $this->assertLessThan(
            20, // Should scale ~linearly (10x permissions = ~10x memory)
            $ratio,
            'Memory scaling exponentially with permissions'
        );
    }

    /**
     * Test 5: Cache state variations
     */
    public function test_memory_with_cache_variations()
    {
        $this->withoutMiddleware();

        // Test 1: Empty cache
        cache()->flush();
        $memoryEmpty = $this->measureRequestMemory('/filament/admin');

        // Test 2: Warm cache (after first request)
        $memoryWarm = $this->measureRequestMemory('/filament/admin');

        // Test 3: Cold cache with stale data
        cache()->flush();
        cache()->put('stale_key', str_repeat('x', 1024 * 1024 * 10), 3600); // 10MB
        $memoryStale = $this->measureRequestMemory('/filament/admin');

        Log::info('Cache state vs memory usage', [
            'empty_cache_mb' => $memoryEmpty,
            'warm_cache_mb' => $memoryWarm,
            'stale_cache_mb' => $memoryStale,
        ]);

        // Warm cache should use LESS memory (no regeneration)
        $this->assertLessThan($memoryEmpty, $memoryWarm);
    }

    /**
     * Test 6: Time-based variations (simulating production timing)
     */
    public function test_memory_at_different_times()
    {
        $this->withoutMiddleware();

        // Simulate different times of day
        $times = [
            'morning' => now()->setHour(8),
            'afternoon' => now()->setHour(14),
            'evening' => now()->setHour(20),
        ];

        $results = [];

        foreach ($times as $label => $time) {
            $this->travelTo($time);

            $memory = $this->measureRequestMemory('/filament/admin');

            $results[$label] = $memory;
        }

        Log::info('Time of day vs memory usage', $results);

        // Check for time-based patterns
        $variance = $this->calculateVariance(array_values($results));

        $this->assertLessThan(
            100, // Low variance = consistent, high variance = state-dependent
            $variance,
            'High memory variance across different times'
        );
    }

    // ========================================================================
    // Helper Methods
    // ========================================================================

    private function measureRequestMemory(string $url): float
    {
        $before = memory_get_usage(true);

        $this->get($url);

        $after = memory_get_usage(true);

        return round(($after - $before) / 1024 / 1024, 2);
    }

    private function createUserWithPermissions(int $count)
    {
        // Implement based on your permission system
        $user = \App\Models\User::factory()->create();

        // Add permissions (adjust based on your implementation)
        for ($i = 0; $i < $count; $i++) {
            // $user->givePermissionTo("permission_$i");
        }

        return $user;
    }

    private function calculateVariance(array $values): float
    {
        $mean = array_sum($values) / count($values);
        $variance = array_reduce(
            $values,
            fn($carry, $val) => $carry + pow($val - $mean, 2),
            0
        );

        return $variance / count($values);
    }
}
