<?php

namespace App\Services\ServiceGateway\OutputHandlers;

use App\Models\ServiceCase;
use Illuminate\Support\Facades\Log;

/**
 * HybridOutputHandler
 *
 * Composite handler that delivers service case notifications via both
 * email and webhook simultaneously. Provides redundancy and multi-channel
 * delivery for critical cases.
 *
 * Features:
 * - Parallel delivery to email and webhook
 * - Succeeds if at least one channel succeeds
 * - Independent channel failure handling
 * - Consolidated test results
 * - Comprehensive logging for both channels
 *
 * Flow:
 * 1. Deliver via email handler
 * 2. Deliver via webhook handler
 * 3. Return success if either succeeded
 * 4. Log combined results
 *
 * Use Cases:
 * - Critical incidents requiring multiple notification channels
 * - Redundancy for high-priority cases
 * - Gradual migration from email to webhook
 * - Compliance requirements for audit trails
 *
 * @package App\Services\ServiceGateway\OutputHandlers
 */
class HybridOutputHandler implements OutputHandlerInterface
{
    /**
     * Create hybrid handler with email and webhook dependencies.
     *
     * @param \App\Services\ServiceGateway\OutputHandlers\EmailOutputHandler $emailHandler
     * @param \App\Services\ServiceGateway\OutputHandlers\WebhookOutputHandler $webhookHandler
     */
    public function __construct(
        private EmailOutputHandler $emailHandler,
        private WebhookOutputHandler $webhookHandler,
    ) {}

    /**
     * Deliver service case notification via both email and webhook.
     *
     * Attempts delivery through both channels. Returns true if at least
     * one channel succeeds. This provides redundancy and ensures delivery
     * even if one channel fails.
     *
     * @param \App\Models\ServiceCase $case Service case to notify about
     * @return bool True if at least one channel succeeded
     */
    public function deliver(ServiceCase $case): bool
    {
        Log::info('[HybridOutputHandler] Starting hybrid delivery', [
            'case_id' => $case->id,
            'case_type' => $case->case_type,
            'priority' => $case->priority,
        ]);

        $results = [
            'email' => false,
            'webhook' => false,
        ];

        // Attempt email delivery
        try {
            $results['email'] = $this->emailHandler->deliver($case);

            Log::info('[HybridOutputHandler] Email delivery result', [
                'case_id' => $case->id,
                'success' => $results['email'],
            ]);
        } catch (\Exception $e) {
            Log::error('[HybridOutputHandler] Email delivery exception', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
            ]);
            $results['email'] = false;
        }

        // Attempt webhook delivery
        try {
            $results['webhook'] = $this->webhookHandler->deliver($case);

            Log::info('[HybridOutputHandler] Webhook delivery result', [
                'case_id' => $case->id,
                'success' => $results['webhook'],
            ]);
        } catch (\Exception $e) {
            Log::error('[HybridOutputHandler] Webhook delivery exception', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
            ]);
            $results['webhook'] = false;
        }

        // Succeed if at least one channel succeeded
        $overallSuccess = $results['email'] || $results['webhook'];

        Log::info('[HybridOutputHandler] Hybrid delivery completed', [
            'case_id' => $case->id,
            'email_success' => $results['email'],
            'webhook_success' => $results['webhook'],
            'overall_success' => $overallSuccess,
        ]);

        // Log warning if only one channel succeeded
        if ($overallSuccess && ($results['email'] xor $results['webhook'])) {
            Log::warning('[HybridOutputHandler] Partial delivery - only one channel succeeded', [
                'case_id' => $case->id,
                'email_success' => $results['email'],
                'webhook_success' => $results['webhook'],
            ]);
        }

        // Log critical error if both channels failed
        if (!$overallSuccess) {
            Log::critical('[HybridOutputHandler] Complete delivery failure - both channels failed', [
                'case_id' => $case->id,
                'category_id' => $case->category_id,
            ]);
        }

        return $overallSuccess;
    }

    /**
     * Test both email and webhook delivery configurations.
     *
     * Returns consolidated test results from both channels.
     * Provides diagnostic information for troubleshooting.
     *
     * @param \App\Models\ServiceCase $case Service case to test
     * @return array Combined test results from both handlers
     */
    public function test(ServiceCase $case): array
    {
        Log::info('[HybridOutputHandler] Testing hybrid configuration', [
            'case_id' => $case->id,
        ]);

        $results = [
            'handler' => 'hybrid',
            'case_id' => $case->id,
            'channels' => [],
            'overall_status' => 'failed',
            'can_deliver' => false,
            'issues' => [],
        ];

        // Test email configuration
        try {
            $emailTest = $this->emailHandler->test($case);
            $results['channels']['email'] = $emailTest;
        } catch (\Exception $e) {
            $results['channels']['email'] = [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
            $results['issues'][] = "Email test failed: {$e->getMessage()}";
        }

        // Test webhook configuration
        try {
            $webhookTest = $this->webhookHandler->test($case);
            $results['channels']['webhook'] = $webhookTest;
        } catch (\Exception $e) {
            $results['channels']['webhook'] = [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
            $results['issues'][] = "Webhook test failed: {$e->getMessage()}";
        }

        // Determine overall readiness
        $emailReady = ($results['channels']['email']['can_deliver'] ?? false);
        $webhookReady = ($results['channels']['webhook']['can_deliver'] ?? false);

        if ($emailReady && $webhookReady) {
            $results['overall_status'] = 'ready';
            $results['can_deliver'] = true;
        } else if ($emailReady || $webhookReady) {
            $results['overall_status'] = 'partial';
            $results['can_deliver'] = true;
            $results['issues'][] = 'Only one channel is ready - delivery will be partial';
        } else {
            $results['overall_status'] = 'failed';
            $results['can_deliver'] = false;
            $results['issues'][] = 'Both channels are not ready - delivery will fail';
        }

        Log::info('[HybridOutputHandler] Configuration test completed', [
            'case_id' => $case->id,
            'email_ready' => $emailReady,
            'webhook_ready' => $webhookReady,
            'overall_status' => $results['overall_status'],
        ]);

        return $results;
    }

    /**
     * Get the output handler type identifier.
     *
     * @return string Handler type
     */
    public function getType(): string
    {
        return 'hybrid';
    }
}
