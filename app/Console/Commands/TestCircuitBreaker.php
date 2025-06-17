<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CalcomV2Service;
use App\Services\CircuitBreaker\CircuitBreaker;

class TestCircuitBreaker extends Command
{
    protected $signature = 'test:circuit-breaker {--service=calcom}';
    protected $description = 'Test circuit breaker functionality';

    public function handle()
    {
        $service = $this->option('service');
        
        $this->info("Testing Circuit Breaker for: {$service}");
        $this->info("=====================================");
        
        // Test 1: Normal operation
        $this->testNormalOperation();
        
        // Test 2: Simulate failures
        $this->testFailureScenario();
        
        // Test 3: Check circuit state
        $this->checkCircuitState();
        
        $this->info("\nTest completed!");
    }
    
    private function testNormalOperation()
    {
        $this->info("\n1. Testing normal operation...");
        
        try {
            $calcom = new CalcomV2Service();
            $eventTypes = $calcom->getEventTypes();
            
            if ($eventTypes) {
                $count = count($eventTypes['event_types'] ?? $eventTypes);
                $this->info("✓ Success: Retrieved {$count} event types");
            } else {
                $this->warn("⚠ No event types returned");
            }
        } catch (\Exception $e) {
            $this->error("✗ Error: " . $e->getMessage());
        }
    }
    
    private function testFailureScenario()
    {
        $this->info("\n2. Simulating API failures...");
        
        // Temporarily use invalid API key to trigger failures
        $invalidService = new CalcomV2Service('invalid_key');
        
        for ($i = 1; $i <= 6; $i++) {
            try {
                $this->info("Attempt {$i}...");
                $result = $invalidService->getUsers();
                
                if (isset($result['users']) && empty($result['users'])) {
                    $this->warn("→ Fallback activated (empty array)");
                }
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'circuit breaker')) {
                    $this->error("→ Circuit breaker OPEN!");
                } else {
                    $this->warn("→ Request failed: " . substr($e->getMessage(), 0, 50));
                }
            }
            
            sleep(1); // Brief pause between attempts
        }
    }
    
    private function checkCircuitState()
    {
        $this->info("\n3. Checking circuit breaker status...");
        
        $status = CircuitBreaker::getStatus();
        
        $this->table(
            ['Service', 'State', 'Failures', 'Last Failure'],
            array_map(function($service, $data) {
                return [
                    $service,
                    strtoupper($data['state']),
                    $data['failures'],
                    $data['last_failure'] ? date('Y-m-d H:i:s', $data['last_failure']) : 'N/A'
                ];
            }, array_keys($status), $status)
        );
    }
}