<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ConfigureRetellWebhook extends Command
{
    protected $signature = 'retell:configure-webhook
                            {--url= : The webhook URL (defaults to your domain)}
                            {--events=* : Events to subscribe to}
                            {--list : List current webhook configuration}';

    protected $description = 'Configure Retell webhook endpoint for real-time call updates';

    public function handle()
    {
        $apiKey = config('services.retellai.api_key') ?? config('services.retell.api_key');
        $baseUrl = rtrim(config('services.retellai.base_url') ?? config('services.retell.base_url'), '/');

        if (!$apiKey || !$baseUrl) {
            $this->error('Retell API credentials not configured');
            return 1;
        }

        // If --list flag, show current configuration
        if ($this->option('list')) {
            return $this->listWebhookConfig($baseUrl, $apiKey);
        }

        // Get webhook URL
        $webhookUrl = $this->option('url') ?? config('app.url') . '/api/webhooks/retell';

        // Default events to subscribe to
        $events = $this->option('events') ?: [
            'call.ended',
            'call.analyzed',
            'call.completed',
            'transcript.ready',
            'recording.available'
        ];

        $this->info('🔧 Configuring Retell Webhook');
        $this->info('URL: ' . $webhookUrl);
        $this->info('Events: ' . implode(', ', $events));

        // Note: The actual API endpoint and format may vary based on Retell's documentation
        // This is a placeholder for the webhook configuration
        $this->warn('⚠️  Note: Webhook configuration must be done in Retell Dashboard');
        $this->info('');
        $this->info('Please configure the following in your Retell Dashboard:');
        $this->info('');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Webhook URL', $webhookUrl],
                ['Method', 'POST'],
                ['Content Type', 'application/json'],
                ['Events', implode(', ', $events)],
                ['Secret', config('services.retellai.webhook_secret') ? '✅ Configured' : '❌ Not Set'],
            ]
        );

        $this->info('');
        $this->info('To test the webhook, you can use:');
        $this->info('curl -X POST ' . $webhookUrl . ' \\');
        $this->info('  -H "Content-Type: application/json" \\');
        $this->info('  -H "X-Retell-Signature: test" \\');
        $this->info('  -d \'{"event": "test", "data": {}}\'');

        return 0;
    }

    private function listWebhookConfig(string $baseUrl, string $apiKey): int
    {
        $this->info('📋 Current Retell Configuration:');
        $this->table(
            ['Setting', 'Value'],
            [
                ['API Base URL', $baseUrl],
                ['API Key', substr($apiKey, 0, 10) . '...'],
                ['Webhook Secret', config('services.retellai.webhook_secret') ? '✅ Set' : '❌ Not Set'],
                ['Expected Webhook URL', config('app.url') . '/api/webhooks/retell'],
            ]
        );

        return 0;
    }
}