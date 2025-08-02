<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CallWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    public function __construct(
        public string $webhookUrl,
        public array $payload
    ) {}

    public function handle(): void
    {
        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'AskProAI-Webhook/1.0',
                    'X-Webhook-Event' => $this->payload['event'] ?? 'unknown',
                    'X-Webhook-Timestamp' => $this->payload['timestamp'] ?? now()->toIso8601String()
                ])
                ->post($this->webhookUrl, $this->payload);

            if ($response->successful()) {
                $this->logSuccess($response);
            } else {
                $this->logFailure($response);
                
                // Throw exception to trigger retry
                throw new \Exception("Webhook returned status: {$response->status()}");
            }
        } catch (\Exception $e) {
            $this->logError($e);
            
            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    private function logSuccess($response): void
    {
        DB::table('webhook_logs')->insert([
            'url' => $this->webhookUrl,
            'event' => $this->payload['event'] ?? 'unknown',
            'payload' => json_encode($this->payload),
            'response_status' => $response->status(),
            'response_body' => substr($response->body(), 0, 1000), // Limit response size
            'success' => true,
            'created_at' => now()
        ]);

        // Update subscription retry count (reset on success)
        if (isset($this->payload['subscription_id'])) {
            DB::table('event_subscriptions')
                ->where('id', $this->payload['subscription_id'])
                ->update(['retry_count' => 0]);
        }
    }

    private function logFailure($response): void
    {
        DB::table('webhook_logs')->insert([
            'url' => $this->webhookUrl,
            'event' => $this->payload['event'] ?? 'unknown',
            'payload' => json_encode($this->payload),
            'response_status' => $response->status(),
            'response_body' => substr($response->body(), 0, 1000),
            'success' => false,
            'error' => "HTTP {$response->status()}",
            'created_at' => now()
        ]);

        // Increment retry count
        if (isset($this->payload['subscription_id'])) {
            DB::table('event_subscriptions')
                ->where('id', $this->payload['subscription_id'])
                ->increment('retry_count');
        }
    }

    private function logError(\Exception $e): void
    {
        DB::table('webhook_logs')->insert([
            'url' => $this->webhookUrl,
            'event' => $this->payload['event'] ?? 'unknown',
            'payload' => json_encode($this->payload),
            'success' => false,
            'error' => $e->getMessage(),
            'created_at' => now()
        ]);

        Log::error('Webhook call failed', [
            'url' => $this->webhookUrl,
            'event' => $this->payload['event'] ?? 'unknown',
            'error' => $e->getMessage()
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook job permanently failed', [
            'url' => $this->webhookUrl,
            'event' => $this->payload['event'] ?? 'unknown',
            'error' => $exception->getMessage()
        ]);

        // Disable subscription after too many failures
        if (isset($this->payload['subscription_id'])) {
            $subscription = DB::table('event_subscriptions')
                ->where('id', $this->payload['subscription_id'])
                ->first();

            if ($subscription && $subscription->retry_count > 10) {
                DB::table('event_subscriptions')
                    ->where('id', $this->payload['subscription_id'])
                    ->update(['active' => false]);

                Log::warning('Webhook subscription disabled due to repeated failures', [
                    'subscription_id' => $this->payload['subscription_id'],
                    'url' => $this->webhookUrl
                ]);
            }
        }
    }
}