<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\Monitoring\CalcomMetricsCollector;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Alert on Appointment Sync Failures
 *
 * 🆕 PHASE 3 FIX (2025-11-24): Automated alerting for critical sync failures
 * Detects and alerts on appointments that failed to sync to Cal.com
 *
 * Schedule in app/Console/Kernel.php:
 * $schedule->command('appointments:alert-sync-failures')->everyFifteenMinutes();
 *
 * Manual execution:
 * php artisan appointments:alert-sync-failures [--verbose] [--dry-run]
 */
class AlertAppointmentSyncFailures extends Command
{
    /**
     * Command signature
     */
    protected $signature = 'appointments:alert-sync-failures
                          {--dry-run : Show what would be alerted without actually alerting}
                          {--detailed : Show detailed appointment breakdown}';

    /**
     * Command description
     */
    protected $description = 'Alert on critical appointment sync failures (pending >1h, failed >24h, manual review required)';

    /**
     * Execute the command
     */
    public function handle(): int
    {
        $this->info('🔍 Checking appointment sync status...');
        $this->newLine();

        // Collect metrics
        $collector = new CalcomMetricsCollector();
        $allMetrics = $collector->collectAllMetrics();
        $syncMetrics = $allMetrics['synchronization']['appointments'] ?? [];

        if (empty($syncMetrics)) {
            $this->error('❌ Failed to collect sync metrics');
            return self::FAILURE;
        }

        // Extract metrics
        $healthStatus = $syncMetrics['health_status'] ?? 'unknown';
        $alerts = $syncMetrics['alerts'] ?? [];
        $pendingStale = $syncMetrics['pending_stale'] ?? 0;
        $failedAncient = $syncMetrics['failed_ancient'] ?? 0;
        $manualReview = $syncMetrics['requires_manual_review'] ?? 0;
        $successRate = $syncMetrics['success_rate_24h'] ?? 100;

        // Display health status
        $this->displayHealthStatus($healthStatus, $successRate);

        // Display alerts
        if (empty($alerts)) {
            $this->info('✅ No critical sync issues detected');
            return self::SUCCESS;
        }

        $this->warn("⚠️ Found " . count($alerts) . " sync alert(s):");
        $this->newLine();

        foreach ($alerts as $alert) {
            $this->displayAlert($alert);
        }

        // Get detailed appointment info if detailed flag set
        if ($this->option('detailed')) {
            $this->newLine();
            $this->displayDetailedInfo($pendingStale, $failedAncient, $manualReview);
        }

        // Send alerts (unless dry-run)
        if (!$this->option('dry-run')) {
            $this->sendAlerts($alerts, $syncMetrics);
        } else {
            $this->info('[DRY RUN] Skipping alert notifications');
        }

        // Return appropriate exit code
        $hasCritical = collect($alerts)->contains('severity', 'critical');
        return $hasCritical ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Display health status
     */
    private function displayHealthStatus(string $status, float $successRate): void
    {
        $color = match($status) {
            'healthy' => 'info',
            'degraded' => 'warn',
            'critical' => 'error',
            default => 'comment'
        };

        $icon = match($status) {
            'healthy' => '✅',
            'degraded' => '⚠️',
            'critical' => '🚨',
            default => '❓'
        };

        $this->{$color}("{$icon} Health Status: " . strtoupper($status) . " (Success Rate: {$successRate}%)");
        $this->newLine();
    }

    /**
     * Display single alert
     */
    private function displayAlert(array $alert): void
    {
        $severity = $alert['severity'] ?? 'info';
        $message = $alert['message'] ?? 'Unknown issue';
        $action = $alert['action'] ?? null;
        $appointmentIds = $alert['appointment_ids'] ?? [];

        // Icon based on severity
        $icon = match($severity) {
            'critical' => '🚨',
            'warning' => '⚠️',
            default => 'ℹ️'
        };

        // Display message
        $this->line("{$icon} [{$severity}] {$message}");

        // Display action if available
        if ($action) {
            $this->line("   → Action: {$action}");
        }

        // Display affected appointment IDs
        if (!empty($appointmentIds)) {
            $this->line("   → Appointments: " . implode(', ', $appointmentIds));
        }

        $this->newLine();
    }

    /**
     * Display detailed appointment information
     */
    private function displayDetailedInfo(int $pendingStale, int $failedAncient, int $manualReview): void
    {
        $this->info('📊 Detailed Breakdown:');

        // Stale pending appointments
        if ($pendingStale > 0) {
            $this->warn("⏳ Stale Pending: {$pendingStale} appointments");

            $stalePendingAppointments = Appointment::where('calcom_sync_status', 'pending')
                ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                ->where('created_at', '<', Carbon::now()->subHour())
                ->limit(10)
                ->get(['id', 'customer_id', 'created_at', 'starts_at']);

            $this->table(
                ['ID', 'Customer ID', 'Created', 'Appointment Time'],
                $stalePendingAppointments->map(fn($a) => [
                    $a->id,
                    $a->customer_id,
                    \Carbon\Carbon::parse($a->created_at)->diffForHumans(),
                    \Carbon\Carbon::parse($a->starts_at)->format('Y-m-d H:i')
                ])->toArray()
            );
        }

        // Ancient failed appointments
        if ($failedAncient > 0) {
            $this->error("❌ Ancient Failures: {$failedAncient} appointments");

            $ancientFailedAppointments = Appointment::where('calcom_sync_status', 'failed')
                ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                ->where('last_sync_attempt_at', '<', Carbon::now()->subDay())
                ->limit(10)
                ->get(['id', 'customer_id', 'last_sync_attempt_at', 'sync_error_message']);

            $this->table(
                ['ID', 'Customer ID', 'Last Sync Attempt', 'Error'],
                $ancientFailedAppointments->map(fn($a) => [
                    $a->id,
                    $a->customer_id,
                    $a->last_sync_attempt_at ? \Carbon\Carbon::parse($a->last_sync_attempt_at)->diffForHumans() : 'N/A',
                    substr($a->sync_error_message ?? 'Unknown', 0, 50)
                ])->toArray()
            );
        }

        // Manual review required
        if ($manualReview > 0) {
            $this->error("🔍 Manual Review Required: {$manualReview} appointments");

            $manualReviewAppointments = Appointment::where('requires_manual_review', true)
                ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                ->limit(10)
                ->get(['id', 'customer_id', 'manual_review_flagged_at', 'sync_error_message']);

            $this->table(
                ['ID', 'Customer ID', 'Flagged', 'Error'],
                $manualReviewAppointments->map(fn($a) => [
                    $a->id,
                    $a->customer_id,
                    $a->manual_review_flagged_at ? \Carbon\Carbon::parse($a->manual_review_flagged_at)->diffForHumans() : 'N/A',
                    substr($a->sync_error_message ?? 'Unknown', 0, 50)
                ])->toArray()
            );
        }
    }

    /**
     * Send alerts to monitoring systems
     *
     * TODO: Integrate with actual notification system (Slack, Email, PagerDuty, etc.)
     */
    private function sendAlerts(array $alerts, array $metrics): void
    {
        foreach ($alerts as $alert) {
            $severity = $alert['severity'] ?? 'info';
            $message = $alert['message'] ?? 'Unknown issue';

            // Log to Laravel log
            $logMethod = $severity === 'critical' ? 'critical' : 'warning';
            Log::channel('calcom')->{$logMethod}('🚨 Appointment Sync Alert', [
                'severity' => $severity,
                'message' => $message,
                'alert' => $alert,
                'metrics' => $metrics,
                'timestamp' => now()->toIso8601String(),
            ]);

            // TODO: Send to Slack
            // Notification::route('slack', config('services.slack.webhook'))
            //     ->notify(new AppointmentSyncAlert($alert, $metrics));

            // TODO: Send email to admins
            // Mail::to(config('monitoring.admin_emails'))
            //     ->send(new AppointmentSyncAlertMail($alert, $metrics));

            // TODO: Send to PagerDuty for critical alerts
            // if ($severity === 'critical') {
            //     PagerDuty::trigger($alert['message'], $metrics);
            // }
        }

        $this->info('✅ Alerts logged to storage/logs/laravel.log (channel: calcom)');
    }
}
