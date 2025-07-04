<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CircuitBreaker\CircuitBreakerManager;
use App\Services\CalcomV2Service;
use App\Services\RetellV2Service;
use App\Services\StripeServiceWithCircuitBreaker;

class CircuitBreakerStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'circuit-breaker:status 
                            {--test : Test circuit breakers with real API calls}
                            {--reset : Reset all circuit breakers}
                            {--service= : Specific service to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and manage circuit breaker status for all external services';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $manager = CircuitBreakerManager::getInstance();
        
        if ($this->option('reset')) {
            $this->resetBreakers($manager);
            return;
        }
        
        if ($this->option('test')) {
            $this->testBreakers();
            return;
        }
        
        $this->displayStatus($manager);
    }
    
    private function displayStatus(CircuitBreakerManager $manager): void
    {
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║                 Circuit Breaker Status                          ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();
        
        $status = $manager->getAllStatus();
        $service = $this->option('service');
        
        if ($service) {
            if (!isset($status[$service])) {
                $this->error("Service '{$service}' not found");
                return;
            }
            $status = [$service => $status[$service]];
        }
        
        foreach ($status as $serviceName => $serviceStatus) {
            $this->displayServiceStatus($serviceName, $serviceStatus);
        }
        
        // Display recent metrics
        $this->displayRecentMetrics();
    }
    
    private function displayServiceStatus(string $service, array $status): void
    {
        $state = $status['state'] ?? 'unknown';
        $available = $status['available'] ?? false;
        $healthScore = $status['health_score'] ?? 0;
        
        $stateIcon = match($state) {
            'closed' => '🟢',
            'open' => '🔴',
            'half_open' => '🟡',
            default => '⚪'
        };
        
        $this->line("═══════════════════════════════════════════");
        $this->line("{$stateIcon} <fg=cyan>{$this->getServiceName($service)}</>");
        $this->line("═══════════════════════════════════════════");
        
        $this->table(
            ['Property', 'Value'],
            [
                ['State', $this->formatState($state)],
                ['Available', $available ? '<fg=green>Yes</>' : '<fg=red>No</>'],
                ['Health Score', $this->formatHealthScore($healthScore)],
            ]
        );
    }
    
    private function testBreakers(): void
    {
        $this->info('Testing circuit breakers with real API calls...');
        $this->newLine();
        
        // Test Cal.com
        $this->testCalcom();
        
        // Test Retell.ai
        $this->testRetell();
        
        // Test Stripe
        $this->testStripe();
    }
    
    private function testCalcom(): void
    {
        $this->info('Testing Cal.com...');
        
        try {
            $service = app(CalcomV2Service::class);
            $result = $service->getMe();
            
            if ($result) {
                $this->info('✅ Cal.com API is working');
            } else {
                $this->warn('⚠️ Cal.com API returned empty response');
            }
        } catch (\Exception $e) {
            $this->error('❌ Cal.com API failed: ' . $e->getMessage());
        }
    }
    
    private function testRetell(): void
    {
        $this->info('Testing Retell.ai...');
        
        try {
            $service = app(RetellV2Service::class);
            $result = $service->listAgents(['limit' => 1]);
            
            if ($result) {
                $this->info('✅ Retell.ai API is working');
            } else {
                $this->warn('⚠️ Retell.ai API returned empty response');
            }
        } catch (\Exception $e) {
            $this->error('❌ Retell.ai API failed: ' . $e->getMessage());
        }
    }
    
    private function testStripe(): void
    {
        $this->info('Testing Stripe...');
        
        try {
            $service = app(StripeServiceWithCircuitBreaker::class);
            
            if ($service->isAvailable()) {
                $this->info('✅ Stripe API is available');
            } else {
                $this->warn('⚠️ Stripe circuit breaker is open');
            }
        } catch (\Exception $e) {
            $this->error('❌ Stripe test failed: ' . $e->getMessage());
        }
    }
    
    private function resetBreakers(CircuitBreakerManager $manager): void
    {
        if (!$this->confirm('Are you sure you want to reset all circuit breakers?')) {
            return;
        }
        
        foreach (['calcom', 'retell', 'stripe'] as $service) {
            $manager->reset($service);
            $this->info("✅ Reset circuit breaker for {$service}");
        }
        
        $this->newLine();
        $this->info('All circuit breakers have been reset.');
    }
    
    private function displayRecentMetrics(): void
    {
        $this->newLine();
        $this->info('Recent Metrics (Last Hour):');
        
        $metrics = \DB::table('circuit_breaker_metrics')
            ->select('service', 
                \DB::raw('COUNT(*) as total'),
                \DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success'),
                \DB::raw('AVG(duration_ms) as avg_duration')
            )
            ->where('created_at', '>=', now()->subHour())
            ->groupBy('service')
            ->get();
        
        if ($metrics->isEmpty()) {
            $this->line('No metrics available in the last hour.');
            return;
        }
        
        $tableData = [];
        foreach ($metrics as $metric) {
            $successRate = $metric->total > 0 ? ($metric->success / $metric->total) * 100 : 0;
            
            $tableData[] = [
                $this->getServiceName($metric->service),
                $metric->total,
                $metric->success,
                number_format($successRate, 1) . '%',
                number_format($metric->avg_duration, 0) . 'ms'
            ];
        }
        
        $this->table(
            ['Service', 'Total Calls', 'Successful', 'Success Rate', 'Avg Duration'],
            $tableData
        );
    }
    
    private function getServiceName(string $service): string
    {
        return match($service) {
            'calcom' => 'Cal.com',
            'retell' => 'Retell.ai',
            'stripe' => 'Stripe',
            default => ucfirst($service)
        };
    }
    
    private function formatState(string $state): string
    {
        return match($state) {
            'closed' => '<fg=green>Closed</>',
            'open' => '<fg=red>Open</>',
            'half_open' => '<fg=yellow>Half Open</>',
            default => '<fg=gray>Unknown</>'
        };
    }
    
    private function formatHealthScore(int $score): string
    {
        if ($score >= 90) {
            return "<fg=green>{$score}%</>";
        } elseif ($score >= 70) {
            return "<fg=yellow>{$score}%</>";
        } else {
            return "<fg=red>{$score}%</>";
        }
    }
}