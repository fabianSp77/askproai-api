<?php

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendTransactionWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min
    
    protected Transaction $transaction;
    protected string $event;

    /**
     * Create a new job instance.
     */
    public function __construct(Transaction $transaction, string $event)
    {
        $this->transaction = $transaction;
        $this->event = $event;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $webhookUrls = config("services.webhooks.urls.transaction.{$this->event}", []);
        
        if (empty($webhookUrls)) {
            return;
        }
        
        $payload = $this->buildPayload();
        $signature = $this->generateSignature($payload);
        
        foreach ($webhookUrls as $url) {
            try {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'X-Webhook-Event' => "transaction.{$this->event}",
                        'X-Webhook-Signature' => $signature,
                        'X-Webhook-Timestamp' => now()->timestamp,
                    ])
                    ->post($url, $payload);
                
                if ($response->successful()) {
                    Log::info('Webhook sent successfully', [
                        'url' => $url,
                        'event' => "transaction.{$this->event}",
                        'transaction_id' => $this->transaction->id,
                        'status' => $response->status(),
                    ]);
                } else {
                    Log::warning('Webhook failed', [
                        'url' => $url,
                        'event' => "transaction.{$this->event}",
                        'transaction_id' => $this->transaction->id,
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]);
                    
                    // Throw exception to trigger retry
                    if ($response->status() >= 500) {
                        throw new \Exception("Webhook returned {$response->status()} status");
                    }
                }
            } catch (\Exception $e) {
                Log::error('Webhook error', [
                    'url' => $url,
                    'event' => "transaction.{$this->event}",
                    'transaction_id' => $this->transaction->id,
                    'error' => $e->getMessage(),
                ]);
                
                // Re-throw to trigger retry mechanism
                throw $e;
            }
        }
    }

    /**
     * Build the webhook payload
     */
    protected function buildPayload(): array
    {
        $this->transaction->load(['tenant', 'call', 'appointment', 'topup']);
        
        return [
            'event' => "transaction.{$this->event}",
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'id' => $this->transaction->id,
                'tenant_id' => $this->transaction->tenant_id,
                'tenant_name' => $this->transaction->tenant?->name,
                'type' => $this->transaction->type,
                'type_label' => $this->transaction->getTypeLabel(),
                'amount_cents' => $this->transaction->amount_cents,
                'amount_formatted' => $this->transaction->getFormattedAmount(),
                'balance_before_cents' => $this->transaction->balance_before_cents,
                'balance_after_cents' => $this->transaction->balance_after_cents,
                'balance_formatted' => $this->transaction->getFormattedBalanceAfter(),
                'description' => $this->transaction->description,
                'metadata' => $this->transaction->metadata,
                'call_id' => $this->transaction->call_id,
                'appointment_id' => $this->transaction->appointment_id,
                'topup_id' => $this->transaction->topup_id,
                'created_at' => $this->transaction->created_at->toIso8601String(),
                'updated_at' => $this->transaction->updated_at->toIso8601String(),
            ],
            'environment' => app()->environment(),
        ];
    }

    /**
     * Generate a signature for webhook verification
     */
    protected function generateSignature(array $payload): string
    {
        $secret = config('services.webhooks.secret', 'webhook-secret-key');
        $payloadString = json_encode($payload);
        
        return hash_hmac('sha256', $payloadString, $secret);
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Transaction webhook job failed after retries', [
            'transaction_id' => $this->transaction->id,
            'event' => $this->event,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
        
        // Optional: Send notification to admin about failed webhook
        // Notification::send($admins, new WebhookFailedNotification($this->transaction, $this->event));
    }
}