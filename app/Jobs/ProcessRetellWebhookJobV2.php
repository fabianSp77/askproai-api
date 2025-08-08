<?php

namespace App\Jobs;

use App\Helpers\RetellDataExtractor;
use App\Models\Branch;
use App\Models\Call;
use App\Models\WebhookEvent;
use App\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ProcessRetellWebhookJobV2 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     * Smart retry with exponential backoff
     */
    public $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     * Exponential backoff: 2, 4, 8, 16, 32 seconds
     */
    public $backoff = [2, 4, 8, 16, 32];

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public $maxExceptions = 3;

    /**
     * Timeout in seconds (prevent long-running jobs)
     */
    public $timeout = 120;

    /**
     * @var int
     */
    protected $webhookEventId;

    /**
     * @var string
     */
    protected $correlationId;

    /**
     * Business logic errors that should NOT be retried
     */
    protected const NON_RETRYABLE_ERRORS = [
        'no_available_users_found_error',
        'invalid_event_type',
        'invalid_agent_id',
        'insufficient_permissions',
        'invalid_api_key',
        'account_suspended',
        'feature_not_enabled',
        'quota_exceeded',
        'invalid_phone_number',
        'blacklisted_number'
    ];

    /**
     * HTTP status codes that should NOT be retried
     */
    protected const NON_RETRYABLE_STATUS_CODES = [
        400, // Bad Request
        401, // Unauthorized
        403, // Forbidden
        404, // Not Found
        405, // Method Not Allowed
        409, // Conflict
        422, // Unprocessable Entity
    ];

    /**
     * Create a new job instance.
     */
    public function __construct(int $webhookEventId, string $correlationId)
    {
        $this->webhookEventId = $webhookEventId;
        $this->correlationId = $correlationId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        
        Log::info('[Retell Job V2] Processing webhook', [
            'correlation_id' => $this->correlationId,
            'webhook_event_id' => $this->webhookEventId,
            'attempt' => $this->attempts()
        ]);

        try {
            // Load webhook event
            $webhookEvent = WebhookEvent::find($this->webhookEventId);
            
            if (!$webhookEvent) {
                Log::error('[Retell Job V2] Webhook event not found', [
                    'correlation_id' => $this->correlationId,
                    'webhook_event_id' => $this->webhookEventId
                ]);
                return; // Don't retry - data is gone
            }

            // Check if already processed (defensive)
            if ($webhookEvent->status === 'processed' || $webhookEvent->status === 'completed') {
                Log::info('[Retell Job V2] Already processed', [
                    'correlation_id' => $this->correlationId,
                    'webhook_event_id' => $this->webhookEventId
                ]);
                return; // Don't retry - already done
            }

            // Load full payload from separate table if needed
            $data = $this->loadFullPayload($webhookEvent);
            
            if (!$data) {
                throw new \Exception('Unable to load webhook payload');
            }

            $event = $data['event'] ?? $data['event_type'] ?? null;
            
            // Extract retell_call_id (proper idempotency key)
            $retellCallId = $data['retell_call_id'] ?? $data['call_id'] ?? null;
            if (!$retellCallId && isset($data['call'])) {
                $retellCallId = $data['call']['retell_call_id'] ?? $data['call']['call_id'] ?? null;
            }

            // Process based on event type
            switch ($event) {
                case 'call_started':
                    $this->handleCallStarted($data, $retellCallId);
                    break;
                    
                case 'call_ended':
                case 'call_analyzed':
                    $this->handleCallEnded($data, $retellCallId);
                    break;
                    
                default:
                    Log::info('[Retell Job V2] Ignoring event type', [
                        'correlation_id' => $this->correlationId,
                        'event' => $event
                    ]);
            }

            // Mark as processed
            $webhookEvent->update([
                'status' => 'processed',
                'processed_at' => now(),
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);

            Log::info('[Retell Job V2] Processing complete', [
                'correlation_id' => $this->correlationId,
                'webhook_event_id' => $this->webhookEventId,
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);

        } catch (\Exception $e) {
            $this->handleException($e, $webhookEvent ?? null);
        }
    }

    /**
     * Handle exceptions with smart retry logic
     */
    protected function handleException(\Exception $e, ?WebhookEvent $webhookEvent): void
    {
        $errorMessage = $e->getMessage();
        $shouldRetry = $this->shouldRetry($e);
        
        Log::error('[Retell Job V2] Processing failed', [
            'correlation_id' => $this->correlationId,
            'webhook_event_id' => $this->webhookEventId,
            'error' => $errorMessage,
            'attempt' => $this->attempts(),
            'will_retry' => $shouldRetry,
            'trace' => $e->getTraceAsString()
        ]);

        // Update webhook event status
        if ($webhookEvent) {
            $webhookEvent->update([
                'status' => $shouldRetry ? 'retrying' : 'failed',
                'error_message' => $errorMessage,
                'failed_at' => now(),
                'retry_count' => $this->attempts()
            ]);
        }

        // Only re-throw if we should retry (transient error)
        if ($shouldRetry) {
            throw $e; // Will trigger retry with backoff
        }
        
        // Don't retry business logic errors
        Log::warning('[Retell Job V2] Not retrying due to business error', [
            'error' => $errorMessage,
            'correlation_id' => $this->correlationId
        ]);
    }

    /**
     * Determine if exception should trigger retry
     */
    protected function shouldRetry(\Exception $e): bool
    {
        $message = strtolower($e->getMessage());
        
        // Check for non-retryable business errors
        foreach (self::NON_RETRYABLE_ERRORS as $error) {
            if (str_contains($message, $error)) {
                return false;
            }
        }
        
        // Check for HTTP status codes (if it's an HTTP exception)
        if (method_exists($e, 'getCode')) {
            $code = $e->getCode();
            if (in_array($code, self::NON_RETRYABLE_STATUS_CODES)) {
                return false;
            }
        }
        
        // Check for specific error patterns
        if (str_contains($message, 'constraint violation')) {
            return false; // Database constraint - won't fix itself
        }
        
        if (str_contains($message, 'does not exist')) {
            return false; // Missing resource - won't appear
        }
        
        // Transient errors that SHOULD be retried
        $transientPatterns = [
            'timeout',
            'timed out',
            'connection reset',
            'connection refused',
            'network',
            'rate limit',
            'too many requests',
            '429',
            '500',
            '502',
            '503',
            '504',
            'temporary',
            'try again',
            'database is locked',
            'deadlock'
        ];
        
        foreach ($transientPatterns as $pattern) {
            if (str_contains($message, $pattern)) {
                return true;
            }
        }
        
        // Default: retry on generic exceptions (might be transient)
        return $this->attempts() < 3; // But limit retries for unknown errors
    }

    /**
     * Load full payload from raw payloads table
     */
    protected function loadFullPayload(WebhookEvent $webhookEvent): ?array
    {
        // First check if we have minimal payload
        if (is_array($webhookEvent->payload) && isset($webhookEvent->payload['retell_call_id'])) {
            // Load full payload from separate table
            $rawPayload = DB::table('webhook_raw_payloads')
                ->where('webhook_event_id', $webhookEvent->id)
                ->first();
                
            if ($rawPayload) {
                return json_decode($rawPayload->payload, true);
            }
        }
        
        // Fallback to payload in webhook_events (for backward compatibility)
        return $webhookEvent->payload;
    }

    /**
     * Handle call_started event
     */
    protected function handleCallStarted(array $data, string $retellCallId): void
    {
        // Use atomic operation to prevent duplicates
        DB::transaction(function () use ($data, $retellCallId) {
            // Check if call already exists using retell_call_id (proper field)
            $existingCall = Call::withoutGlobalScope(TenantScope::class)
                ->withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
                ->where('retell_call_id', $retellCallId)
                ->lockForUpdate() // Prevent race condition
                ->first();

            if ($existingCall) {
                Log::info('[Retell Job V2] Call already exists', [
                    'correlation_id' => $this->correlationId,
                    'retell_call_id' => $retellCallId
                ]);
                return;
            }

            // Find branch
            $branch = $this->resolveBranch($data);
            
            if (!$branch) {
                throw new \Exception("Unable to resolve branch for call - this is a business error, don't retry");
            }

            // Extract call data
            $callData = RetellDataExtractor::extractCallData($data);
            
            // PII Redaction for raw_data
            $callData['raw_data'] = $this->redactPII($data);
            
            // Ensure we use retell_call_id properly
            $callData['retell_call_id'] = $retellCallId;
            $callData['call_id'] = $data['call_id'] ?? $retellCallId;
            $callData['company_id'] = $branch->company_id;
            $callData['branch_id'] = $branch->id;
            $callData['call_status'] = 'in_progress';

            // Create call
            $call = Call::withoutGlobalScope(TenantScope::class)
                ->withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
                ->create($callData);

            Log::info('[Retell Job V2] Call created', [
                'correlation_id' => $this->correlationId,
                'call_id' => $call->id,
                'retell_call_id' => $retellCallId
            ]);
        });
    }

    /**
     * Handle call_ended/call_analyzed event
     */
    protected function handleCallEnded(array $data, string $retellCallId): void
    {
        DB::transaction(function () use ($data, $retellCallId) {
            // Find existing call using retell_call_id with lock
            $call = Call::withoutGlobalScope(TenantScope::class)
                ->withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
                ->where('retell_call_id', $retellCallId)
                ->lockForUpdate()
                ->first();

            if (!$call) {
                // Try to create it if it doesn't exist
                Log::warning('[Retell Job V2] Call not found for update, creating', [
                    'correlation_id' => $this->correlationId,
                    'retell_call_id' => $retellCallId
                ]);
                
                $this->handleCallStarted($data, $retellCallId);
                
                // Reload the call
                $call = Call::withoutGlobalScope(TenantScope::class)
                    ->withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
                    ->where('retell_call_id', $retellCallId)
                    ->first();
            }

            if ($call) {
                // Extract update data
                $updateData = RetellDataExtractor::extractUpdateData($data);
                
                // PII Redaction for raw_data
                $updateData['raw_data'] = $this->redactPII($data);
                
                // Preserve customer_data if it exists
                if (isset($call->metadata['customer_data'])) {
                    $preservedMetadata = $call->metadata;
                    if (isset($updateData['metadata'])) {
                        $updateData['metadata'] = array_merge(
                            $updateData['metadata'],
                            ['customer_data' => $preservedMetadata['customer_data']],
                            ['customer_data_collected' => $preservedMetadata['customer_data_collected'] ?? false]
                        );
                    }
                }
                
                // Update status
                $updateData['call_status'] = 'ended';
                
                // Update call
                $call->update($updateData);

                Log::info('[Retell Job V2] Call updated', [
                    'correlation_id' => $this->correlationId,
                    'call_id' => $call->id,
                    'retell_call_id' => $retellCallId,
                    'duration' => $updateData['duration_sec'] ?? null
                ]);
                
                // Dispatch language mismatch detection if needed
                if ($call->detected_language && !$call->language_mismatch) {
                    \App\Jobs\DetectLanguageMismatchJob::dispatch($call->id)
                        ->onQueue('default')
                        ->delay(now()->addSeconds(5));
                }
            }
        });
    }

    /**
     * Resolve branch from phone number
     */
    protected function resolveBranch(array $data): ?Branch
    {
        $toNumber = $data['to_number'] ?? $data['to'] ?? $data['call']['to_number'] ?? null;
        
        if (!$toNumber) {
            return null;
        }

        // Clean number
        $cleanNumber = preg_replace('/[^0-9+]/', '', $toNumber);
        
        // Search patterns
        $searchPatterns = [
            $cleanNumber,
            substr($cleanNumber, -11),
            substr($cleanNumber, -10),
            '%' . substr($cleanNumber, -10) . '%'
        ];
        
        foreach ($searchPatterns as $pattern) {
            $branch = Branch::withoutGlobalScope(TenantScope::class)
                ->withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
                ->where('phone_number', 'LIKE', $pattern)
                ->first();
                
            if ($branch) {
                return $branch;
            }
        }

        // Fallback to first branch
        return Branch::withoutGlobalScope(TenantScope::class)
            ->withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
            ->first();
    }

    /**
     * Redact PII from raw data for GDPR compliance
     */
    protected function redactPII(array $data): array
    {
        $redacted = $data;
        
        // Patterns to redact
        $patterns = [
            // Email addresses
            '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/' => '***@***.***',
            // Phone numbers (various formats)
            '/\+?[0-9]{10,15}/' => '+XX-XXXX-XXXX',
            '/\b\d{3}[-.]?\d{3}[-.]?\d{4}\b/' => 'XXX-XXX-XXXX',
            // German phone formats
            '/\+49\s?[0-9\s\-\/]{10,}/' => '+49-XXXX-XXXXX',
            // Credit card numbers
            '/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/' => 'XXXX-XXXX-XXXX-XXXX',
            // Social security (US format)
            '/\b\d{3}-\d{2}-\d{4}\b/' => 'XXX-XX-XXXX'
        ];
        
        // Recursively redact in arrays and strings
        array_walk_recursive($redacted, function(&$value) use ($patterns) {
            if (is_string($value)) {
                foreach ($patterns as $pattern => $replacement) {
                    $value = preg_replace($pattern, $replacement, $value);
                }
            }
        });
        
        // Keep specific fields unredacted if needed for processing
        if (isset($data['retell_call_id'])) {
            $redacted['retell_call_id'] = $data['retell_call_id'];
        }
        if (isset($data['call_id'])) {
            $redacted['call_id'] = $data['call_id'];
        }
        
        return $redacted;
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[Retell Job V2] Job failed permanently', [
            'correlation_id' => $this->correlationId,
            'webhook_event_id' => $this->webhookEventId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Update webhook event
        WebhookEvent::where('id', $this->webhookEventId)->update([
            'status' => 'failed_permanently',
            'error_message' => $exception->getMessage(),
            'failed_at' => now()
        ]);
        
        // TODO: Send alert to monitoring system
        // Alert::send('Webhook processing failed permanently', [
        //     'webhook_event_id' => $this->webhookEventId,
        //     'error' => $exception->getMessage()
        // ]);
    }
}