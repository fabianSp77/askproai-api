<?php

namespace App\Jobs;

use App\Models\CallbackRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Check Callback SLA Compliance Job
 *
 * Monitors callback request response times and triggers alerts when SLA thresholds are breached.
 * Runs every 5 minutes via scheduler to ensure timely escalation.
 *
 * SLA Thresholds:
 * - Warning: 60 minutes (no response)
 * - Critical: 90 minutes (breach)
 * - Escalation: 120 minutes (manager notification)
 */
class CheckCallbackSlaJob implements ShouldQueue
{
    use Queueable;

    /**
     * SLA threshold in minutes for warning alert
     */
    const WARNING_THRESHOLD = 60;

    /**
     * SLA threshold in minutes for critical alert
     */
    const CRITICAL_THRESHOLD = 90;

    /**
     * SLA threshold in minutes for manager escalation
     */
    const ESCALATION_THRESHOLD = 120;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        // Job runs without parameters (scheduled)
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('CheckCallbackSlaJob: Starting SLA check', [
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);

        // Check for callbacks approaching/breaching SLA
        $warningCallbacks = $this->getCallbacksApproachingSla(self::WARNING_THRESHOLD, self::CRITICAL_THRESHOLD);
        $criticalCallbacks = $this->getCallbacksBreachingSla(self::CRITICAL_THRESHOLD, self::ESCALATION_THRESHOLD);
        $escalationCallbacks = $this->getCallbacksForEscalation(self::ESCALATION_THRESHOLD);

        // Send notifications
        if ($warningCallbacks->isNotEmpty()) {
            $this->handleWarningAlerts($warningCallbacks);
        }

        if ($criticalCallbacks->isNotEmpty()) {
            $this->handleCriticalAlerts($criticalCallbacks);
        }

        if ($escalationCallbacks->isNotEmpty()) {
            $this->handleEscalationAlerts($escalationCallbacks);
        }

        // Update metrics
        $this->updateMetrics([
            'warning' => $warningCallbacks->count(),
            'critical' => $criticalCallbacks->count(),
            'escalation' => $escalationCallbacks->count(),
        ]);

        Log::info('CheckCallbackSlaJob: Completed SLA check', [
            'warning_count' => $warningCallbacks->count(),
            'critical_count' => $criticalCallbacks->count(),
            'escalation_count' => $escalationCallbacks->count(),
        ]);
    }

    /**
     * Get callbacks approaching SLA threshold (60-90 min)
     */
    protected function getCallbacksApproachingSla(int $minMinutes, int $maxMinutes)
    {
        $minTime = Carbon::now()->subMinutes($maxMinutes);
        $maxTime = Carbon::now()->subMinutes($minMinutes);

        return CallbackRequest::whereBetween('created_at', [$minTime, $maxTime])
            ->whereIn('status', [
                CallbackRequest::STATUS_PENDING,
                CallbackRequest::STATUS_ASSIGNED,
            ])
            ->whereNull('contacted_at')
            ->with(['branch', 'assignedTo'])
            ->get();
    }

    /**
     * Get callbacks breaching SLA threshold (90-120 min)
     */
    protected function getCallbacksBreachingSla(int $minMinutes, int $maxMinutes)
    {
        $minTime = Carbon::now()->subMinutes($maxMinutes);
        $maxTime = Carbon::now()->subMinutes($minMinutes);

        return CallbackRequest::whereBetween('created_at', [$minTime, $maxTime])
            ->whereIn('status', [
                CallbackRequest::STATUS_PENDING,
                CallbackRequest::STATUS_ASSIGNED,
            ])
            ->whereNull('contacted_at')
            ->with(['branch', 'assignedTo'])
            ->get();
    }

    /**
     * Get callbacks requiring manager escalation (>120 min)
     */
    protected function getCallbacksForEscalation(int $minutes)
    {
        return CallbackRequest::where('created_at', '<', Carbon::now()->subMinutes($minutes))
            ->whereIn('status', [
                CallbackRequest::STATUS_PENDING,
                CallbackRequest::STATUS_ASSIGNED,
            ])
            ->whereNull('contacted_at')
            ->with(['branch', 'assignedTo'])
            ->get();
    }

    /**
     * Handle warning alerts (60-90 min)
     */
    protected function handleWarningAlerts($callbacks): void
    {
        foreach ($callbacks as $callback) {
            // Check if we already sent warning (prevent spam)
            $cacheKey = "callback_sla_warning_{$callback->id}";
            if (Cache::has($cacheKey)) {
                continue;
            }

            Log::warning('Callback SLA Warning', [
                'callback_id' => $callback->id,
                'customer_name' => $callback->customer_name,
                'age_minutes' => $callback->created_at->diffInMinutes(Carbon::now()),
                'assigned_to' => $callback->assignedTo?->name,
            ]);

            // TODO: Send notification to assigned staff
            // Notification::send($callback->assignedTo, new CallbackSlaWarningNotification($callback));

            // Mark as notified (cache for 24 hours)
            Cache::put($cacheKey, true, 60 * 24);
        }
    }

    /**
     * Handle critical alerts (90-120 min)
     */
    protected function handleCriticalAlerts($callbacks): void
    {
        foreach ($callbacks as $callback) {
            // Check if we already sent critical alert
            $cacheKey = "callback_sla_critical_{$callback->id}";
            if (Cache::has($cacheKey)) {
                continue;
            }

            Log::error('Callback SLA Breach', [
                'callback_id' => $callback->id,
                'customer_name' => $callback->customer_name,
                'age_minutes' => $callback->created_at->diffInMinutes(Carbon::now()),
                'assigned_to' => $callback->assignedTo?->name,
                'branch' => $callback->branch?->name,
            ]);

            // TODO: Send notification to staff + supervisor
            // Notification::send($callback->assignedTo, new CallbackSlaCriticalNotification($callback));
            // Notification::send($callback->branch->supervisors, new CallbackSlaCriticalNotification($callback));

            // Mark as notified
            Cache::put($cacheKey, true, 60 * 24);
        }
    }

    /**
     * Handle escalation alerts (>120 min)
     */
    protected function handleEscalationAlerts($callbacks): void
    {
        foreach ($callbacks as $callback) {
            // Check if we already escalated
            $cacheKey = "callback_sla_escalation_{$callback->id}";
            if (Cache::has($cacheKey)) {
                continue;
            }

            Log::critical('Callback SLA Escalation Required', [
                'callback_id' => $callback->id,
                'customer_name' => $callback->customer_name,
                'phone_number' => $callback->phone_number,
                'age_minutes' => $callback->created_at->diffInMinutes(Carbon::now()),
                'assigned_to' => $callback->assignedTo?->name,
                'branch' => $callback->branch?->name,
                'priority' => $callback->priority,
            ]);

            // TODO: Send notification to managers
            // $managers = User::role('manager')->get();
            // Notification::send($managers, new CallbackSlaEscalationNotification($callback));

            // Auto-escalate callback
            $callback->escalate(
                reason: 'Automatic SLA escalation: No response after ' .
                    $callback->created_at->diffInMinutes(Carbon::now()) . ' minutes',
                escalateTo: null // Will be assigned by escalation workflow
            );

            // Mark as escalated
            Cache::put($cacheKey, true, 60 * 24);
        }
    }

    /**
     * Update SLA metrics for monitoring
     */
    protected function updateMetrics(array $counts): void
    {
        Cache::put('callback_sla_metrics', [
            'warning_count' => $counts['warning'],
            'critical_count' => $counts['critical'],
            'escalation_count' => $counts['escalation'],
            'last_check' => Carbon::now()->toIso8601String(),
        ], 60 * 5); // Cache for 5 minutes
    }
}
