<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestWebhookSignature extends Command
{
    protected $signature = 'webhook:test-signature 
                            {--event=call_started : Event type to test}
                            {--phone=+493083793369 : Phone number to use}';

    protected $description = 'Test webhook signature verification';

    public function handle()
    {
        $eventType = $this->option('event');
        $phoneNumber = $this->option('phone');
        
        // Get webhook secret from env
        $webhookSecret = config('services.retell.webhook_secret');
        if (!$webhookSecret) {
            $this->error('Webhook secret not configured');
            return 1;
        }

        $this->info("Testing webhook signature...");
        $this->info("Event: {$eventType}");
        $this->info("Phone: {$phoneNumber}");
        $this->info("Secret: " . substr($webhookSecret, 0, 10) . '...');

        // Create test payload
        $payload = [
            'event' => $eventType,
            'timestamp' => time() * 1000,
            'call' => [
                'call_id' => 'test_' . uniqid(),
                'call_type' => 'inbound',
                'from_number' => '+491234567890',
                'to_number' => $phoneNumber,
                'direction' => 'inbound',
                'call_status' => $eventType === 'call_ended' ? 'ended' : 'ongoing',
                'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
                'start_timestamp' => time() * 1000,
            ]
        ];

        // Create signature (Retell uses HMAC-SHA256)
        $jsonPayload = json_encode($payload);
        $signature = hash_hmac('sha256', $jsonPayload, $webhookSecret);

        $this->info("\nPayload:");
        $this->line(json_encode($payload, JSON_PRETTY_PRINT));
        $this->info("\nSignature: {$signature}");

        // Test different signature formats
        $this->info("\nTesting signature variations...");
        
        // Test 1: Direct signature
        $url = 'https://api.askproai.de/api/retell/webhook';
        $response1 = $this->testWebhook($url, $payload, $signature);
        $this->info("Direct signature: " . ($response1['success'] ? '✓ Success' : '✗ Failed'));
        
        // Test 2: With "sha256=" prefix
        $response2 = $this->testWebhook($url, $payload, 'sha256=' . $signature);
        $this->info("With sha256= prefix: " . ($response2['success'] ? '✓ Success' : '✗ Failed'));
        
        // Test 3: Uppercase
        $response3 = $this->testWebhook($url, $payload, strtoupper($signature));
        $this->info("Uppercase: " . ($response3['success'] ? '✓ Success' : '✗ Failed'));
        
        // Test alternative URLs
        $this->info("\nTesting alternative webhook URLs...");
        
        $urls = [
            'https://api.askproai.de/api/retell/webhook',
            'https://api.askproai.de/webhooks/retell',
            'https://api.askproai.de/api/webhooks/retell',
        ];
        
        foreach ($urls as $testUrl) {
            $response = $this->testWebhook($testUrl, $payload, $signature);
            $this->info("{$testUrl}: " . ($response['success'] ? '✓' : '✗') . " {$response['status']}");
        }
        
        return 0;
    }
    
    private function testWebhook($url, $payload, $signature)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Retell-Signature' => $signature,
            ])->post($url, $payload);
            
            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->body(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 'Error: ' . $e->getMessage(),
                'body' => null,
            ];
        }
    }
}