<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Gateway\ApiGatewayManager;

class GatewayStatusCommand extends Command
{
    protected $signature = 'gateway:status 
                           {--json : Output in JSON format}
                           {--service= : Check specific service}';

    protected $description = 'Check API Gateway status and health';

    private ApiGatewayManager $gateway;

    public function __construct(ApiGatewayManager $gateway)
    {
        parent::__construct();
        $this->gateway = $gateway;
    }

    public function handle(): int
    {
        try {
            $service = $this->option('service');
            $json = $this->option('json');

            if ($service) {
                $status = $this->getServiceStatus($service);
            } else {
                $status = $this->gateway->getHealthStatus();
            }

            if ($json) {
                $this->line(json_encode($status, JSON_PRETTY_PRINT));
            } else {
                $this->displayStatus($status);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to get gateway status: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function getServiceStatus(string $service): array
    {
        $health = $this->gateway->getHealthStatus();
        
        if (!isset($health['services'][$service])) {
            throw new \InvalidArgumentException("Service '{$service}' not found");
        }

        return [
            'service' => $service,
            'status' => $health['services'][$service],
            'timestamp' => $health['timestamp'],
        ];
    }

    private function displayStatus(array $status): void
    {
        $this->info('API Gateway Status');
        $this->info('==================');
        
        // Overall status
        $overallStatus = $status['gateway'] ?? 'unknown';
        $statusColor = match($overallStatus) {
            'healthy' => '<fg=green>%s</>',
            'degraded' => '<fg=yellow>%s</>',
            'unhealthy' => '<fg=red>%s</>',
            default => '<fg=gray>%s</>',
        };
        
        $this->line(sprintf('Overall Status: ' . $statusColor, strtoupper($overallStatus)));
        $this->line('Timestamp: ' . ($status['timestamp'] ?? 'unknown'));
        $this->newLine();

        // Services status
        if (isset($status['services'])) {
            $this->info('Services:');
            $this->displayServicesTable($status['services']);
            $this->newLine();
        }

        // Cache status
        if (isset($status['cache'])) {
            $this->info('Cache Status:');
            $this->displayCacheStatus($status['cache']);
            $this->newLine();
        }

        // Circuit breakers
        if (isset($status['circuit_breakers'])) {
            $this->info('Circuit Breakers:');
            $this->displayCircuitBreakersStatus($status['circuit_breakers']);
        }
    }

    private function displayServicesTable(array $services): void
    {
        $headers = ['Service', 'Status', 'Health', 'Last Check'];
        $rows = [];

        foreach ($services as $name => $service) {
            $status = $service['status'] ?? 'unknown';
            $health = $service['health'] ?? 'unknown';
            $lastCheck = $service['last_check'] ?? 'never';

            $statusDisplay = match($status) {
                'healthy' => '<fg=green>healthy</>',
                'degraded' => '<fg=yellow>degraded</>',
                'unhealthy' => '<fg=red>unhealthy</>',
                default => '<fg=gray>unknown</>',
            };

            $rows[] = [$name, $statusDisplay, $health, $lastCheck];
        }

        $this->table($headers, $rows);
    }

    private function displayCacheStatus(array $cache): void
    {
        $status = $cache['status'] ?? 'unknown';
        $statusColor = $status === 'healthy' ? '<fg=green>%s</>' : '<fg=red>%s</>';
        
        $this->line(sprintf('Status: ' . $statusColor, strtoupper($status)));
        
        if (isset($cache['l1_keys'])) {
            $this->line('L1 Cache Keys: ' . number_format($cache['l1_keys']));
        }
        
        if (isset($cache['l2_keys'])) {
            $this->line('L2 Cache Keys: ' . number_format($cache['l2_keys']));
        }
        
        if (isset($cache['memory_used'])) {
            $this->line('Memory Used: ' . $cache['memory_used']);
        }
        
        if (isset($cache['hit_rate'])) {
            $hitRate = $cache['hit_rate'];
            $hitRateColor = $hitRate >= 80 ? '<fg=green>%s</>' : ($hitRate >= 60 ? '<fg=yellow>%s</>' : '<fg=red>%s</>');
            $this->line(sprintf('Hit Rate: ' . $hitRateColor, number_format($hitRate, 1) . '%'));
        }
    }

    private function displayCircuitBreakersStatus(array $circuitBreakers): void
    {
        $headers = ['Service', 'State', 'Failures', 'Successes', 'Open Since'];
        $rows = [];

        foreach ($circuitBreakers as $service => $cb) {
            $state = $cb['state'] ?? 'unknown';
            $failures = $cb['failures'] ?? 0;
            $successes = $cb['successes'] ?? 0;
            $openSince = $cb['open_since'] ? date('Y-m-d H:i:s', $cb['open_since']) : 'N/A';

            $stateDisplay = match($state) {
                'closed' => '<fg=green>closed</>',
                'half_open' => '<fg=yellow>half-open</>',
                'open' => '<fg=red>open</>',
                default => '<fg=gray>unknown</>',
            };

            $rows[] = [$service, $stateDisplay, $failures, $successes, $openSince];
        }

        $this->table($headers, $rows);
    }
}