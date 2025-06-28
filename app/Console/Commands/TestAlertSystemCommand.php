<?php

namespace App\Console\Commands;

use App\Services\Monitoring\UnifiedAlertingService;
use Illuminate\Console\Command;

class TestAlertSystemCommand extends Command
{
    protected $signature = 'alerts:test 
                            {channel? : The channel to test (email, slack, sms, or all)}
                            {--rule= : Test a specific alert rule}
                            {--severity=medium : Set the alert severity (critical, high, medium, low)}';

    protected $description = 'Test the alert system by sending test alerts';

    public function handle(UnifiedAlertingService $alertingService): int
    {
        $channel = $this->argument('channel');
        $rule = $this->option('rule');
        $severity = $this->option('severity');

        $this->info('🚨 Testing AskProAI Alert System...');
        $this->newLine();

        if ($rule) {
            // Test specific rule
            $this->testSpecificRule($alertingService, $rule);
        } else {
            // Send test alert
            $this->info('Sending test alert' . ($channel ? " to {$channel}" : ' to all channels'));
            $this->info("Severity: {$severity}");

            try {
                $alertingService->testAlert($channel);

                $this->newLine();
                $this->info('✅ Test alert sent successfully!');

                if (! $channel || $channel === 'email') {
                    $this->line('📧 Check email inbox for test alert');
                }
                if (! $channel || $channel === 'slack') {
                    $this->line('💬 Check Slack channel for test alert');
                }
                if (! $channel || $channel === 'sms') {
                    $this->line('📱 Check SMS (if configured) for test alert');
                }
            } catch (\Exception $e) {
                $this->error('❌ Failed to send test alert: ' . $e->getMessage());

                return Command::FAILURE;
            }
        }

        $this->newLine();
        $this->info('Alert configuration:');
        $this->table(
            ['Channel', 'Status', 'Configuration'],
            $this->getChannelStatus()
        );

        return Command::SUCCESS;
    }

    /**
     * Test a specific alert rule.
     */
    private function testSpecificRule(UnifiedAlertingService $alertingService, string $rule): void
    {
        $this->info("Testing alert rule: {$rule}");

        // Simulate data for different rules
        $testData = match ($rule) {
            'payment_failure' => ['count' => 5, 'window' => 5],
            'security_breach_attempt' => ['count' => 10, 'ip' => '192.168.1.100', 'window' => 60],
            'stripe_webhook_failure' => ['count' => 7, 'window' => 5],
            'high_error_rate' => ['rate' => 15.5, 'window' => 5],
            'database_connection_failure' => ['count' => 3, 'window' => 60],
            'queue_backlog' => ['count' => 1500, 'threshold' => 1000],
            'portal_downtime' => [],
            default => []
        };

        try {
            $alertingService->alert($rule, $testData);
            $this->info("✅ Alert rule '{$rule}' triggered successfully!");
        } catch (\Exception $e) {
            $this->error("❌ Failed to trigger alert rule '{$rule}': " . $e->getMessage());
        }
    }

    /**
     * Get channel configuration status.
     */
    private function getChannelStatus(): array
    {
        $config = config('monitoring.alerts.channels');
        $status = [];

        // Email
        $emailConfig = $config['email'] ?? [];
        $status[] = [
            'Email',
            ($emailConfig['enabled'] ?? false) ? '✅ Enabled' : '❌ Disabled',
            count($emailConfig['recipients'] ?? []) . ' recipients configured',
        ];

        // Slack
        $slackConfig = $config['slack'] ?? [];
        $status[] = [
            'Slack',
            ($slackConfig['enabled'] ?? false) ? '✅ Enabled' : '❌ Disabled',
            ($slackConfig['webhook_url'] ?? false) ? 'Webhook configured' : 'No webhook URL',
        ];

        // SMS
        $smsConfig = $config['sms'] ?? [];
        $status[] = [
            'SMS',
            ($smsConfig['enabled'] ?? false) ? '✅ Enabled' : '❌ Disabled',
            count($smsConfig['recipients'] ?? []) . ' recipients configured',
        ];

        return $status;
    }
}
