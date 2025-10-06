<?php

namespace App\Jobs;

use App\Models\CallbackRequest;
use App\Services\Appointments\CallbackManagementService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * EscalateOverdueCallbacksJob
 *
 * Scheduled hourly job to identify and escalate overdue callback requests.
 * Finds callbacks that exceed their max_hours threshold and creates
 * escalations to ensure no customer requests are lost.
 */
class EscalateOverdueCallbacksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('callbacks');
    }

    /**
     * Execute the job.
     */
    public function handle(CallbackManagementService $callbackService): void
    {
        $startTime = microtime(true);

        try {
            Log::info('🔍 Starting overdue callback escalation check');

            // Find all overdue callbacks
            $overdueCallbacks = $this->getOverdueCallbacks();

            if ($overdueCallbacks->isEmpty()) {
                Log::info('✅ No overdue callbacks found');
                return;
            }

            Log::info('⚠️ Found overdue callbacks', [
                'count' => $overdueCallbacks->count(),
            ]);

            $escalatedCount = 0;
            $failedCount = 0;

            foreach ($overdueCallbacks as $callback) {
                try {
                    $this->escalateCallback($callback, $callbackService);
                    $escalatedCount++;

                } catch (\Exception $e) {
                    $failedCount++;

                    Log::error('❌ Failed to escalate callback', [
                        'callback_id' => $callback->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $duration = round((microtime(true) - $startTime) * 1000);

            Log::info('✅ Overdue callback escalation complete', [
                'total_found' => $overdueCallbacks->count(),
                'escalated' => $escalatedCount,
                'failed' => $failedCount,
                'duration_ms' => $duration,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Overdue callback job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get all overdue callback requests
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getOverdueCallbacks()
    {
        return CallbackRequest::overdue()
            ->with(['customer', 'branch', 'service', 'assignedTo', 'escalations'])
            ->get()
            ->filter(function ($callback) {
                // Additional check: has it already been escalated recently?
                return !$this->hasRecentEscalation($callback);
            });
    }

    /**
     * Escalate a single callback
     *
     * @param CallbackRequest $callback
     * @param CallbackManagementService $callbackService
     * @return void
     */
    protected function escalateCallback(CallbackRequest $callback, CallbackManagementService $callbackService): void
    {
        $reason = $this->determineEscalationReason($callback);

        $escalation = $callbackService->escalate($callback, $reason);

        Log::info('⬆️ Callback escalated', [
            'callback_id' => $callback->id,
            'escalation_id' => $escalation->id,
            'reason' => $reason,
            'overdue_hours' => $this->calculateOverdueHours($callback),
        ]);
    }

    /**
     * Determine escalation reason based on callback state
     *
     * @param CallbackRequest $callback
     * @return string
     */
    protected function determineEscalationReason(CallbackRequest $callback): string
    {
        // Check for SLA breach (expired)
        if ($callback->is_overdue) {
            return 'sla_breach';
        }

        // Check for multiple failed contact attempts
        $contactAttempts = $callback->metadata['contact_attempts'] ?? 0;
        if ($contactAttempts >= config('callbacks.max_contact_attempts', 3)) {
            return 'multiple_attempts_failed';
        }

        // Default to SLA breach
        return 'sla_breach';
    }

    /**
     * Check if callback has been escalated recently
     *
     * @param CallbackRequest $callback
     * @return bool
     */
    protected function hasRecentEscalation(CallbackRequest $callback): bool
    {
        if ($callback->escalations->isEmpty()) {
            return false;
        }

        $lastEscalation = $callback->escalations->sortByDesc('created_at')->first();

        // Don't re-escalate within 4 hours
        $escalationCooldown = config('callbacks.escalation_cooldown_hours', 4);

        return $lastEscalation->created_at->greaterThan(
            Carbon::now()->subHours($escalationCooldown)
        );
    }

    /**
     * Calculate how many hours a callback is overdue
     *
     * @param CallbackRequest $callback
     * @return float
     */
    protected function calculateOverdueHours(CallbackRequest $callback): float
    {
        if (!$callback->expires_at) {
            return 0;
        }

        $now = Carbon::now();

        if ($callback->expires_at->greaterThan($now)) {
            return 0;
        }

        return $callback->expires_at->diffInHours($now, true);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('🔥 Overdue callback escalation job permanently failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Optionally alert administrators
        // Could send urgent notification here
    }
}
