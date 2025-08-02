<?php

namespace Tests\ChaosEngineering;

use Tests\TestCase;
use App\Services\ChaosMonkey\ChaosMonkeyService;
use App\Services\ResilienceTestingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class ChaosEngineeringTest extends TestCase
{
    protected $chaosMonkey;
    protected $resilienceTester;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->chaosMonkey = new ChaosMonkeyService();
        $this->resilienceTester = new ResilienceTestingService();
    }

    /**
     * Test: System resilience under database failures
     */
    public function test_database_failure_resilience()
    {
        // Simulate database connection failure
        $this->chaosMonkey->simulateDatabaseFailure(5); // 5 second outage
        
        // System should switch to read replica
        $response = $this->getJson('/api/dashboard');
        $response->assertStatus(200);
        $response->assertHeader('X-Database-Fallback', 'read-replica');
        
        // Verify writes are queued
        $response = $this->postJson('/api/appointments', [
            'customer_id' => 1,
            'service_id' => 1,
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s')
        ]);
        
        $response->assertStatus(202); // Accepted for later processing
        $response->assertJson([
            'message' => 'Request queued due to temporary database issue',
            'retry_after' => 60
        ]);
        
        // Verify system recovers
        $this->chaosMonkey->restoreDatabase();
        
        // Check queued writes are processed
        $this->artisan('queue:work --stop-when-empty');
        
        $appointment = \App\Models\Appointment::latest()->first();
        $this->assertNotNull($appointment);
    }

    /**
     * Test: Cascading service failures
     */
    public function test_cascading_failure_prevention()
    {
        // Simulate external service failures
        Http::fake([
            'cal.com/*' => Http::response(null, 500),
            'retellai.com/*' => Http::response(null, 503),
            'stripe.com/*' => Http::response(null, 502)
        ]);
        
        // System should not cascade failures
        $response = $this->getJson('/api/health');
        $response->assertStatus(200);
        
        $health = $response->json();
        $this->assertEquals('degraded', $health['status']);
        $this->assertFalse($health['services']['calendar']['healthy']);
        $this->assertFalse($health['services']['ai_phone']['healthy']);
        $this->assertFalse($health['services']['payments']['healthy']);
        
        // Core functionality should still work
        $response = $this->getJson('/api/appointments');
        $response->assertStatus(200);
        
        // Verify circuit breakers are open
        $this->assertTrue(Cache::get('circuit_breaker:cal.com'));
        $this->assertTrue(Cache::get('circuit_breaker:retellai.com'));
        $this->assertTrue(Cache::get('circuit_breaker:stripe.com'));
    }

    /**
     * Test: Memory leak simulation
     */
    public function test_memory_leak_detection_and_recovery()
    {
        // Start memory monitoring
        $initialMemory = memory_get_usage();
        
        // Simulate memory leak
        $this->chaosMonkey->simulateMemoryLeak(100 * 1024 * 1024); // 100MB leak
        
        // System should detect and alert
        $response = $this->getJson('/api/system/metrics');
        $response->assertStatus(200);
        
        $metrics = $response->json();
        $this->assertGreaterThan(80, $metrics['memory_usage_percentage']);
        $this->assertTrue($metrics['alerts']['high_memory_usage']);
        
        // Trigger automatic cleanup
        $this->artisan('system:cleanup-memory');
        
        // Verify memory recovered
        $currentMemory = memory_get_usage();
        $this->assertLessThan($initialMemory + (50 * 1024 * 1024), $currentMemory);
    }

    /**
     * Test: Network partition simulation
     */
    public function test_network_partition_handling()
    {
        // Simulate network partition between services
        $this->chaosMonkey->createNetworkPartition([
            'web' => ['database' => false, 'redis' => true],
            'queue' => ['database' => true, 'redis' => false]
        ]);
        
        // Web requests should use cache
        $response = $this->getJson('/api/customers');
        $response->assertStatus(200);
        $response->assertHeader('X-Data-Source', 'cache');
        
        // Queue should process with database only
        Queue::push(new \App\Jobs\ProcessAppointmentJob(1));
        $this->artisan('queue:work --stop-when-empty');
        
        // Verify split-brain prevention
        $this->assertFalse(Cache::has('appointment:1:lock'));
        $this->assertTrue(DB::table('appointment_locks')->where('id', 1)->exists());
    }

    /**
     * Test: CPU spike simulation
     */
    public function test_cpu_spike_throttling()
    {
        // Simulate CPU spike
        $this->chaosMonkey->simulateCpuSpike(90); // 90% CPU usage
        
        // Make concurrent requests
        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->getJson('/api/analytics/heavy-computation');
        }
        
        // Some requests should be throttled
        $throttled = array_filter($responses, fn($r) => $r->status() === 503);
        $this->assertGreaterThan(0, count($throttled));
        
        // Successful requests should have degraded performance flag
        $successful = array_filter($responses, fn($r) => $r->status() === 200);
        foreach ($successful as $response) {
            $response->assertHeader('X-Performance-Mode', 'degraded');
        }
    }

    /**
     * Test: Random service delays
     */
    public function test_random_latency_injection()
    {
        // Enable chaos mode with random delays
        $this->chaosMonkey->enableLatencyInjection([
            'min' => 100, // 100ms
            'max' => 2000, // 2s
            'probability' => 0.3 // 30% of requests
        ]);
        
        // Measure response times
        $responseTimes = [];
        for ($i = 0; $i < 20; $i++) {
            $start = microtime(true);
            $response = $this->getJson('/api/appointments');
            $responseTimes[] = (microtime(true) - $start) * 1000; // Convert to ms
            
            $response->assertStatus(200);
        }
        
        // Verify some requests were delayed
        $delayedRequests = array_filter($responseTimes, fn($time) => $time > 100);
        $this->assertGreaterThan(3, count($delayedRequests));
        
        // Verify timeout handling
        $timedOut = array_filter($responseTimes, fn($time) => $time > 5000);
        $this->assertCount(0, $timedOut); // No requests should fully timeout
    }

    /**
     * Test: Disk space exhaustion
     */
    public function test_disk_space_exhaustion_handling()
    {
        // Simulate low disk space
        $this->chaosMonkey->simulateLowDiskSpace(95); // 95% full
        
        // System should prevent new uploads
        $response = $this->postJson('/api/uploads', [
            'file' => \Illuminate\Http\UploadedFile::fake()->image('test.jpg', 1000, 1000)
        ]);
        
        $response->assertStatus(507); // Insufficient Storage
        $response->assertJson([
            'error' => 'Insufficient storage space',
            'available_space' => '5%'
        ]);
        
        // But critical operations should continue
        $response = $this->postJson('/api/appointments/1/complete');
        $response->assertSuccessful();
        
        // Cleanup should be triggered
        $this->assertTrue(Queue::hasJob(\App\Jobs\CleanupOldFilesJob::class));
    }

    /**
     * Test: Clock drift simulation
     */
    public function test_clock_drift_resilience()
    {
        // Simulate clock drift between servers
        $this->chaosMonkey->simulateClockDrift([
            'web' => '+5 minutes',
            'queue' => '-3 minutes',
            'database' => '+1 minute'
        ]);
        
        // Create time-sensitive operation
        $appointment = \App\Models\Appointment::factory()->create([
            'starts_at' => now()->addMinutes(10)
        ]);
        
        // Verify system uses synchronized time
        $response = $this->getJson("/api/appointments/{$appointment->id}");
        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertArrayHasKey('time_sync_warning', $data);
        $this->assertEquals('Clock drift detected, using NTP time', $data['time_sync_warning']);
    }

    /**
     * Test: Zombie process handling
     */
    public function test_zombie_process_cleanup()
    {
        // Create zombie processes
        for ($i = 0; $i < 5; $i++) {
            $this->chaosMonkey->createZombieProcess('queue:work');
        }
        
        // System should detect zombies
        $response = $this->getJson('/api/system/processes');
        $response->assertStatus(200);
        
        $processes = $response->json('data.processes');
        $zombies = array_filter($processes, fn($p) => $p['state'] === 'zombie');
        $this->assertCount(5, $zombies);
        
        // Trigger cleanup
        $this->artisan('system:cleanup-zombies');
        
        // Verify cleanup
        $response = $this->getJson('/api/system/processes');
        $processes = $response->json('data.processes');
        $zombies = array_filter($processes, fn($p) => $p['state'] === 'zombie');
        $this->assertCount(0, $zombies);
    }

    /**
     * Test: Complete system chaos
     */
    public function test_multi_failure_chaos_scenario()
    {
        // Enable multiple chaos scenarios simultaneously
        $this->chaosMonkey->enableChaosMode([
            'database_failures' => true,
            'network_delays' => true,
            'cpu_spikes' => true,
            'memory_leaks' => true,
            'disk_issues' => true,
            'clock_drift' => true
        ]);
        
        // System should remain operational (degraded)
        $criticalEndpoints = [
            '/api/health',
            '/api/appointments',
            '/api/customers',
            '/api/emergency-contact'
        ];
        
        foreach ($criticalEndpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $this->assertIn($response->status(), [200, 202, 503]); // Not 500s
            
            if ($response->status() === 503) {
                $response->assertJson([
                    'error' => 'Service temporarily unavailable',
                    'retry_after' => 60
                ]);
            }
        }
        
        // Verify graceful degradation
        $response = $this->getJson('/api/system/status');
        $status = $response->json('data');
        
        $this->assertEquals('degraded', $status['overall_status']);
        $this->assertGreaterThan(0, count($status['active_issues']));
        $this->assertArrayHasKey('recovery_eta', $status);
        
        // Verify self-healing initiated
        $this->assertTrue(Queue::hasJob(\App\Jobs\SystemRecoveryJob::class));
    }
}
