<?php

namespace App\Services\Billing;

use App\Models\Company;
use App\Models\DunningConfiguration;
use App\Models\DunningProcess;
use App\Models\Invoice;
use App\Models\WebhookEvent;
use App\Services\NotificationService;
use App\Services\StripeServiceWithCircuitBreaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DunningService
{
    protected StripeServiceWithCircuitBreaker $stripeService;
    protected NotificationService $notificationService;
    
    public function __construct(
        StripeServiceWithCircuitBreaker $stripeService,
        NotificationService $notificationService
    ) {
        $this->stripeService = $stripeService;
        $this->notificationService = $notificationService;
    }
    
    /**
     * Handle failed payment from webhook
     */
    public function handleFailedPayment(WebhookEvent $webhookEvent): DunningProcess
    {
        $invoice = $webhookEvent->payload['data']['object'] ?? [];
        
        // Find or create local invoice
        $localInvoice = Invoice::where('stripe_invoice_id', $invoice['id'])->first();
        if (!$localInvoice) {
            Log::warning('Invoice not found for dunning', ['stripe_invoice_id' => $invoice['id']]);
            throw new \Exception('Invoice not found');
        }
        
        // Get company and configuration
        $company = $localInvoice->company;
        $config = DunningConfiguration::forCompany($company);
        
        if (!$config->enabled) {
            Log::info('Dunning disabled for company', ['company_id' => $company->id]);
            return null;
        }
        
        // Check if already in dunning
        $existingProcess = DunningProcess::where('invoice_id', $localInvoice->id)
            ->whereIn('status', [DunningProcess::STATUS_ACTIVE, DunningProcess::STATUS_PAUSED])
            ->first();
            
        if ($existingProcess) {
            return $this->updateExistingProcess($existingProcess, $invoice);
        }
        
        // Create new dunning process
        return $this->createDunningProcess($localInvoice, $invoice, $config);
    }
    
    /**
     * Create new dunning process
     */
    protected function createDunningProcess(Invoice $localInvoice, array $stripeInvoice, DunningConfiguration $config): DunningProcess
    {
        return DB::transaction(function () use ($localInvoice, $stripeInvoice, $config) {
            $process = DunningProcess::create([
                'company_id' => $localInvoice->company_id,
                'invoice_id' => $localInvoice->id,
                'stripe_invoice_id' => $stripeInvoice['id'],
                'stripe_subscription_id' => $stripeInvoice['subscription'] ?? null,
                'status' => DunningProcess::STATUS_ACTIVE,
                'started_at' => now(),
                'retry_count' => 0,
                'max_retries' => $config->max_retry_attempts,
                'original_amount' => $stripeInvoice['amount_due'] / 100,
                'remaining_amount' => $stripeInvoice['amount_due'] / 100,
                'currency' => strtoupper($stripeInvoice['currency']),
                'failure_code' => $stripeInvoice['last_payment_error']['code'] ?? null,
                'failure_message' => $stripeInvoice['last_payment_error']['message'] ?? null,
                'metadata' => [
                    'attempt_count' => $stripeInvoice['attempt_count'] ?? 1,
                    'next_payment_attempt' => $stripeInvoice['next_payment_attempt'] ?? null
                ]
            ]);
            
            // Update invoice status
            $localInvoice->update([
                'dunning_status' => 'active',
                'payment_attempts' => $stripeInvoice['attempt_count'] ?? 1,
                'last_payment_attempt_at' => now()
            ]);
            
            // Log activity
            $process->logActivity(
                DunningActivity::TYPE_RETRY_FAILED,
                "Payment failed: " . ($stripeInvoice['last_payment_error']['message'] ?? 'Unknown error'),
                [
                    'stripe_error_code' => $stripeInvoice['last_payment_error']['code'] ?? null,
                    'stripe_error_type' => $stripeInvoice['last_payment_error']['type'] ?? null
                ],
                false
            );
            
            // Schedule first retry
            $retryDelay = $config->getRetryDelayForAttempt(1);
            $process->scheduleNextRetry($retryDelay);
            
            // Send payment failed email
            if ($config->shouldSendEmail('payment_failed')) {
                $this->sendPaymentFailedEmail($process);
            }
            
            return $process;
        });
    }
    
    /**
     * Update existing dunning process
     */
    protected function updateExistingProcess(DunningProcess $process, array $stripeInvoice): DunningProcess
    {
        $process->failure_code = $stripeInvoice['last_payment_error']['code'] ?? $process->failure_code;
        $process->failure_message = $stripeInvoice['last_payment_error']['message'] ?? $process->failure_message;
        $process->save();
        
        $process->logActivity(
            DunningActivity::TYPE_RETRY_FAILED,
            "Payment retry failed: " . ($stripeInvoice['last_payment_error']['message'] ?? 'Unknown error'),
            [
                'attempt_number' => $process->retry_count + 1,
                'stripe_error_code' => $stripeInvoice['last_payment_error']['code'] ?? null
            ],
            false
        );
        
        return $process;
    }
    
    /**
     * Process due retries
     */
    public function processDueRetries(): int
    {
        $processes = DunningProcess::dueForRetry()->get();
        $processedCount = 0;
        
        foreach ($processes as $process) {
            try {
                $this->retryPayment($process);
                $processedCount++;
            } catch (\Exception $e) {
                Log::error('Failed to retry payment', [
                    'dunning_process_id' => $process->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $processedCount;
    }
    
    /**
     * Retry payment for dunning process
     */
    public function retryPayment(DunningProcess $process): bool
    {
        // Check if can retry
        if (!$process->canRetry()) {
            Log::warning('Cannot retry dunning process', [
                'dunning_process_id' => $process->id,
                'status' => $process->status,
                'retry_count' => $process->retry_count
            ]);
            return false;
        }
        
        $process->markRetryAttempted();
        
        $process->logActivity(
            DunningActivity::TYPE_RETRY_ATTEMPTED,
            "Payment retry attempt #{$process->retry_count}",
            ['stripe_invoice_id' => $process->stripe_invoice_id]
        );
        
        try {
            // Retry payment via Stripe
            $result = $this->stripeService->retryInvoicePayment($process->stripe_invoice_id);
            
            if ($result['paid']) {
                // Payment successful
                $this->handleSuccessfulPayment($process);
                return true;
            } else {
                // Payment failed again
                $this->handleFailedRetry($process, $result['error'] ?? 'Payment failed');
                return false;
            }
            
        } catch (\Exception $e) {
            $this->handleFailedRetry($process, $e->getMessage());
            return false;
        }
    }
    
    /**
     * Handle successful payment
     */
    protected function handleSuccessfulPayment(DunningProcess $process): void
    {
        $process->markAsResolved('Payment successful after retry');
        
        // Update invoice
        $process->invoice->update([
            'dunning_status' => 'resolved',
            'status' => 'paid'
        ]);
        
        // Send recovery email
        $config = DunningConfiguration::forCompany($process->company);
        if ($config->shouldSendEmail('payment_recovered')) {
            $this->sendPaymentRecoveredEmail($process);
        }
    }
    
    /**
     * Handle failed retry
     */
    protected function handleFailedRetry(DunningProcess $process, string $error): void
    {
        $config = DunningConfiguration::forCompany($process->company);
        
        // Check if max retries reached
        if ($process->retry_count >= $process->max_retries) {
            $this->handleMaxRetriesReached($process, $config);
            return;
        }
        
        // Schedule next retry
        $nextRetryDelay = $config->getRetryDelayForAttempt($process->retry_count + 1);
        $process->scheduleNextRetry($nextRetryDelay);
        
        // Send retry warning email
        if ($config->shouldSendEmail('retry_warning')) {
            $this->sendRetryWarningEmail($process);
        }
        
        // Check if service should be paused
        if ($config->shouldPauseService($process->getDaysSinceFailure())) {
            $this->pauseService($process, $config);
        }
    }
    
    /**
     * Handle max retries reached
     */
    protected function handleMaxRetriesReached(DunningProcess $process, DunningConfiguration $config): void
    {
        // Check if manual review needed
        if ($config->needsManualReview($process->retry_count)) {
            $process->requestManualReview();
            
            // TODO: Notify admin team
            Log::info('Manual review requested for dunning process', [
                'dunning_process_id' => $process->id,
                'company_id' => $process->company_id
            ]);
        } else {
            // Mark as permanently failed
            $process->markAsFailed('Maximum retry attempts reached');
            
            // Pause service if configured
            if ($config->pause_service_on_failure) {
                $this->pauseService($process, $config);
            }
        }
    }
    
    /**
     * Pause service for company
     */
    protected function pauseService(DunningProcess $process, DunningConfiguration $config): void
    {
        $process->pauseService();
        
        // Send service paused email
        if ($config->shouldSendEmail('service_paused')) {
            $this->sendServicePausedEmail($process);
        }
        
        Log::warning('Service paused due to payment failure', [
            'company_id' => $process->company_id,
            'dunning_process_id' => $process->id
        ]);
    }
    
    /**
     * Manually resolve dunning process
     */
    public function manuallyResolve(DunningProcess $process, string $reason, string $resolvedBy): void
    {
        $process->status = DunningProcess::STATUS_RESOLVED;
        $process->resolved_at = now();
        $process->save();
        
        $process->logActivity(
            DunningActivity::TYPE_MANUALLY_RESOLVED,
            $reason,
            [],
            true,
            null,
            $resolvedBy
        );
        
        // Resume service if paused
        if ($process->service_paused) {
            $process->resumeService();
        }
        
        // Update invoice
        $process->invoice->update([
            'dunning_status' => 'manually_resolved'
        ]);
    }
    
    /**
     * Cancel dunning process
     */
    public function cancelDunning(DunningProcess $process, string $reason, string $cancelledBy): void
    {
        $process->status = DunningProcess::STATUS_CANCELLED;
        $process->save();
        
        $process->logActivity(
            DunningActivity::TYPE_CANCELLED,
            $reason,
            [],
            true,
            null,
            $cancelledBy
        );
        
        // Update invoice
        $process->invoice->update([
            'dunning_status' => 'cancelled'
        ]);
    }
    
    /**
     * Send payment failed email
     */
    protected function sendPaymentFailedEmail(DunningProcess $process): void
    {
        try {
            $this->notificationService->sendEmail(
                $process->company,
                'payment_failed',
                [
                    'company_name' => $process->company->name,
                    'invoice_number' => $process->invoice->number,
                    'amount' => $process->original_amount,
                    'currency' => $process->currency,
                    'failure_reason' => $process->failure_message,
                    'next_retry_date' => $process->next_retry_at?->format('Y-m-d')
                ]
            );
            
            $process->logActivity(
                DunningActivity::TYPE_EMAIL_SENT,
                'Payment failed notification sent',
                ['email_type' => 'payment_failed']
            );
        } catch (\Exception $e) {
            Log::error('Failed to send payment failed email', [
                'dunning_process_id' => $process->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Send retry warning email
     */
    protected function sendRetryWarningEmail(DunningProcess $process): void
    {
        try {
            $this->notificationService->sendEmail(
                $process->company,
                'payment_retry_warning',
                [
                    'company_name' => $process->company->name,
                    'invoice_number' => $process->invoice->number,
                    'amount' => $process->remaining_amount,
                    'currency' => $process->currency,
                    'retry_count' => $process->retry_count,
                    'max_retries' => $process->max_retries,
                    'next_retry_date' => $process->next_retry_at?->format('Y-m-d')
                ]
            );
            
            $process->logActivity(
                DunningActivity::TYPE_EMAIL_SENT,
                'Payment retry warning sent',
                ['email_type' => 'retry_warning']
            );
        } catch (\Exception $e) {
            Log::error('Failed to send retry warning email', [
                'dunning_process_id' => $process->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Send service paused email
     */
    protected function sendServicePausedEmail(DunningProcess $process): void
    {
        try {
            $this->notificationService->sendEmail(
                $process->company,
                'service_paused',
                [
                    'company_name' => $process->company->name,
                    'invoice_number' => $process->invoice->number,
                    'amount' => $process->remaining_amount,
                    'currency' => $process->currency,
                    'paused_date' => now()->format('Y-m-d H:i')
                ]
            );
            
            $process->logActivity(
                DunningActivity::TYPE_EMAIL_SENT,
                'Service paused notification sent',
                ['email_type' => 'service_paused']
            );
        } catch (\Exception $e) {
            Log::error('Failed to send service paused email', [
                'dunning_process_id' => $process->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Send payment recovered email
     */
    protected function sendPaymentRecoveredEmail(DunningProcess $process): void
    {
        try {
            $this->notificationService->sendEmail(
                $process->company,
                'payment_recovered',
                [
                    'company_name' => $process->company->name,
                    'invoice_number' => $process->invoice->number,
                    'amount' => $process->original_amount,
                    'currency' => $process->currency,
                    'recovered_date' => now()->format('Y-m-d H:i')
                ]
            );
            
            $process->logActivity(
                DunningActivity::TYPE_EMAIL_SENT,
                'Payment recovered notification sent',
                ['email_type' => 'payment_recovered']
            );
        } catch (\Exception $e) {
            Log::error('Failed to send payment recovered email', [
                'dunning_process_id' => $process->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get dunning statistics
     */
    public function getStatistics(?Company $company = null): array
    {
        $query = DunningProcess::query();
        
        if ($company) {
            $query->where('company_id', $company->id);
        }
        
        return [
            'total_processes' => $query->count(),
            'active_processes' => $query->where('status', DunningProcess::STATUS_ACTIVE)->count(),
            'resolved_processes' => $query->where('status', DunningProcess::STATUS_RESOLVED)->count(),
            'failed_processes' => $query->where('status', DunningProcess::STATUS_FAILED)->count(),
            'total_recovered' => $query->where('status', DunningProcess::STATUS_RESOLVED)->sum('original_amount'),
            'total_outstanding' => $query->whereIn('status', [DunningProcess::STATUS_ACTIVE, DunningProcess::STATUS_PAUSED])->sum('remaining_amount'),
            'recovery_rate' => $query->count() > 0 
                ? round($query->where('status', DunningProcess::STATUS_RESOLVED)->count() / $query->count() * 100, 2) 
                : 0,
            'average_retry_count' => round($query->where('status', DunningProcess::STATUS_RESOLVED)->avg('retry_count') ?? 0, 2),
            'companies_with_suspended_service' => Company::where('billing_status', 'suspended')->count()
        ];
    }
}