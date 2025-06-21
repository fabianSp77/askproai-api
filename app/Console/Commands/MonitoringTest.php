<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Monitoring\HealthCheckService;
use App\Services\Monitoring\AlertingService;
use App\Services\Monitoring\PerformanceMonitor;
use App\Services\Monitoring\SecurityMonitor;

class MonitoringTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:test 
                            {--health : Test health checks}
                            {--alert : Test alerting}
                            {--performance : Test performance monitoring}
                            {--security : Test security monitoring}
                            {--all : Test all monitoring components}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test monitoring and alerting systems';

    protected HealthCheckService $healthCheck;
    protected AlertingService $alerting;
    protected PerformanceMonitor $performance;
    protected SecurityMonitor $security;

    public function __construct(
        HealthCheckService $healthCheck,
        AlertingService $alerting,
        PerformanceMonitor $performance,
        SecurityMonitor $security
    ) {
        parent::__construct();
        $this->healthCheck = $healthCheck;
        $this->alerting = $alerting;
        $this->performance = $performance;
        $this->security = $security;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing AskProAI Monitoring Systems...');
        $this->newLine();

        if ($this->option('all') || $this->option('health')) {
            $this->testHealthChecks();
        }

        if ($this->option('all') || $this->option('alert')) {
            $this->testAlerting();
        }

        if ($this->option('all') || $this->option('performance')) {
            $this->testPerformance();
        }

        if ($this->option('all') || $this->option('security')) {
            $this->testSecurity();
        }

        $this->newLine();
        $this->info('Monitoring tests completed!');
    }

    /**
     * Test health checks
     */
    private function testHealthChecks(): void
    {
        $this->info('Testing Health Checks...');
        
        $results = $this->healthCheck->check();
        
        $this->line('Status: ' . ($results['status'] === 'healthy' ? 
            $this->getColoredText('HEALTHY', 'green') : 
            $this->getColoredText('UNHEALTHY', 'red')
        ));
        
        $this->newLine();
        $this->table(
            ['Check', 'Status', 'Duration (ms)', 'Message'],
            collect($results['checks'])->map(function ($check) {
                return [
                    $check['name'],
                    $this->getStatusBadge($check['status']),
                    $check['duration'] ?? 'N/A',
                    $check['message'] ?? '',
                ];
            })
        );
    }

    /**
     * Test alerting
     */
    private function testAlerting(): void
    {
        $this->info('Testing Alerting System...');
        
        $this->warn('Sending test alert for payment_failure...');
        $this->alerting->alert('payment_failure', [
            'count' => 5,
            'window' => 5,
        ]);
        
        $this->info('Test alert sent! Check configured channels.');
        
        $activeAlerts = $this->alerting->getActiveAlerts();
        if (!empty($activeAlerts)) {
            $this->newLine();
            $this->line('Active Alerts:');
            foreach ($activeAlerts as $alert) {
                $this->line("- [{$alert['severity']}] {$alert['rule']}: {$alert['message']}");
            }
        }
    }

    /**
     * Test performance monitoring
     */
    private function testPerformance(): void
    {
        $this->info('Testing Performance Monitoring...');
        
        // Simulate a transaction
        $this->performance->startTransaction('test_command');
        
        // Simulate some work
        usleep(100000); // 100ms
        $this->performance->checkpoint('midpoint');
        
        usleep(50000); // 50ms
        $this->performance->checkpoint('endpoint');
        
        $this->performance->endTransaction('test_command', [
            'test' => true,
        ]);
        
        // Get metrics
        $metrics = $this->performance->getMetrics('test_command');
        
        $this->line('Transaction Metrics:');
        $this->line('- Count: ' . $metrics['count']);
        $this->line('- Avg Duration: ' . round($metrics['total_duration'] / max($metrics['count'], 1), 2) . 'ms');
        
        // Test API monitoring
        $this->newLine();
        $this->line('Testing API Call Monitoring...');
        
        try {
            $this->performance->monitorApiCall('stripe', function () {
                // Simulate API call
                usleep(200000); // 200ms
                return true;
            });
            $this->info('API call monitoring successful');
        } catch (\Exception $e) {
            $this->error('API call monitoring failed: ' . $e->getMessage());
        }
    }

    /**
     * Test security monitoring
     */
    private function testSecurity(): void
    {
        $this->info('Testing Security Monitoring...');
        
        // Create a mock request
        $request = request();
        
        // Test security event logging
        $this->security->logEvent('test_security_event', $request, [
            'test' => true,
            'command' => 'monitoring:test',
        ]);
        
        $this->line('Security event logged successfully');
        
        // Get security metrics
        $metrics = $this->security->getMetrics();
        
        $this->newLine();
        $this->line('Security Metrics:');
        $this->line('- Failed logins (24h): ' . $metrics['failed_logins_24h']);
        $this->line('- Suspicious activities (24h): ' . $metrics['suspicious_activities_24h']);
        $this->line('- Blocked IPs: ' . $metrics['blocked_ips']);
        $this->line('- Rate limit violations (1h): ' . $metrics['rate_limit_violations_1h']);
    }

    /**
     * Get colored text
     */
    private function getColoredText(string $text, string $color): string
    {
        $colors = [
            'green' => "\033[32m",
            'red' => "\033[31m",
            'yellow' => "\033[33m",
            'reset' => "\033[0m",
        ];
        
        return ($colors[$color] ?? '') . $text . $colors['reset'];
    }

    /**
     * Get status badge
     */
    private function getStatusBadge(string $status): string
    {
        return match ($status) {
            'ok' => $this->getColoredText('✓ OK', 'green'),
            'warning' => $this->getColoredText('⚠ WARNING', 'yellow'),
            'error' => $this->getColoredText('✗ ERROR', 'red'),
            default => $status,
        };
    }
}