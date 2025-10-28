<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\AppointmentPhase;
use App\Models\Service;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * MonitorProcessingTimeHealth
 *
 * Monitors health metrics for Processing Time / Split Appointments feature.
 * Run hourly during business hours to detect issues early.
 *
 * Schedule in app/Console/Kernel.php:
 *   $schedule->command('monitor:processing-time-health')
 *       ->hourly()
 *       ->between('8:00', '20:00')
 *       ->timezone('Europe/Berlin');
 *
 * Manual execution:
 *   php artisan monitor:processing-time-health
 *   php artisan monitor:processing-time-health --details
 */
class MonitorProcessingTimeHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:processing-time-health
                            {--details : Output detailed diagnostics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor Processing Time feature health metrics';

    /**
     * Success rate threshold for phase creation (percentage)
     */
    const SUCCESS_RATE_THRESHOLD = 95;

    /**
     * Minimum appointments required to trigger alerts
     * (prevents false positives with low volume)
     */
    const MIN_APPOINTMENTS_FOR_ALERT = 10;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing Time Health Monitor - ' . now()->format('Y-m-d H:i:s'));
        $this->newLine();

        // Check feature flag status
        if ($this->option('details')) {
            $this->checkFeatureFlags();
        }

        // Calculate phase creation metrics
        $metrics = $this->calculateMetrics();

        // Display metrics
        $this->displayMetrics($metrics);

        // Check for alerts
        $hasAlerts = $this->checkAlerts($metrics);

        // Check for orphaned appointments
        $orphanedCount = $this->checkOrphanedAppointments();

        if ($hasAlerts || $orphanedCount > 0) {
            return 1; // Exit code 1 = issues detected
        }

        $this->info('✅ All health checks passed');
        return 0; // Exit code 0 = healthy
    }

    /**
     * Check feature flag configuration
     */
    protected function checkFeatureFlags(): void
    {
        $this->info('Feature Flag Status:');
        $this->line('  Master Toggle: ' . (config('features.processing_time_enabled') ? '✅ ENABLED' : '❌ DISABLED'));
        $this->line('  Auto Phases: ' . (config('features.processing_time_auto_create_phases') ? '✅ ENABLED' : '❌ DISABLED'));
        $this->line('  Show UI: ' . (config('features.processing_time_show_ui') ? '✅ ENABLED' : '❌ DISABLED'));
        $this->line('  Cal.com Sync: ' . (config('features.processing_time_calcom_sync_enabled') ? '✅ ENABLED' : '❌ DISABLED'));

        $serviceWhitelist = config('features.processing_time_service_whitelist', []);
        $companyWhitelist = config('features.processing_time_company_whitelist', []);

        $this->line('  Service Whitelist: ' .
            (empty($serviceWhitelist) ? 'EMPTY (all allowed)' : count($serviceWhitelist) . ' services'));
        $this->line('  Company Whitelist: ' .
            (empty($companyWhitelist) ? 'EMPTY (all allowed)' : implode(',', $companyWhitelist)));

        $this->newLine();
    }

    /**
     * Calculate health metrics
     */
    protected function calculateMetrics(): array
    {
        $startOfDay = now()->startOfDay();

        // Total Processing Time appointments created today
        $total = Appointment::whereHas('service', function($q) {
            $q->where('has_processing_time', true);
        })->where('created_at', '>=', $startOfDay)->count();

        // Appointments with phases successfully created
        $withPhases = Appointment::whereHas('service', function($q) {
            $q->where('has_processing_time', true);
        })->whereHas('phases')->where('created_at', '>=', $startOfDay)->count();

        // Calculate success rate
        $successRate = $total > 0 ? round(($withPhases / $total) * 100, 2) : 100;

        // Get phase distribution
        $phaseDistribution = AppointmentPhase::selectRaw('
            phase_type,
            COUNT(*) as count
        ')
        ->where('created_at', '>=', $startOfDay)
        ->groupBy('phase_type')
        ->pluck('count', 'phase_type')
        ->toArray();

        return [
            'total' => $total,
            'with_phases' => $withPhases,
            'success_rate' => $successRate,
            'phase_distribution' => $phaseDistribution,
        ];
    }

    /**
     * Display metrics
     */
    protected function displayMetrics(array $metrics): void
    {
        $this->info('Phase Creation Metrics (Today):');
        $this->line('  Total Appointments: ' . $metrics['total']);
        $this->line('  With Phases: ' . $metrics['with_phases']);

        // Color-coded success rate
        $successRate = $metrics['success_rate'];
        if ($successRate >= self::SUCCESS_RATE_THRESHOLD) {
            $this->line('  Success Rate: <fg=green>' . $successRate . '%</>');
        } elseif ($successRate >= 90) {
            $this->line('  Success Rate: <fg=yellow>' . $successRate . '%</> ⚠️');
        } else {
            $this->line('  Success Rate: <fg=red>' . $successRate . '%</> 🚨');
        }

        // Phase distribution
        if (!empty($metrics['phase_distribution'])) {
            $this->newLine();
            $this->info('Phase Distribution:');
            foreach ($metrics['phase_distribution'] as $type => $count) {
                $this->line('  ' . ucfirst($type) . ': ' . $count);
            }
        }

        $this->newLine();
    }

    /**
     * Check for alert conditions
     */
    protected function checkAlerts(array $metrics): bool
    {
        $hasAlerts = false;

        // Alert if success rate below threshold (only if we have enough data)
        if ($metrics['total'] >= self::MIN_APPOINTMENTS_FOR_ALERT &&
            $metrics['success_rate'] < self::SUCCESS_RATE_THRESHOLD) {

            $this->error('🚨 ALERT: Low phase creation success rate');
            $this->line('  Current: ' . $metrics['success_rate'] . '%');
            $this->line('  Threshold: ' . self::SUCCESS_RATE_THRESHOLD . '%');
            $this->line('  Recommendation: Check logs for AppointmentPhaseObserver errors');

            Log::warning('Processing Time: Low phase creation success rate', [
                'success_rate' => $metrics['success_rate'],
                'total_appointments' => $metrics['total'],
                'with_phases' => $metrics['with_phases'],
                'alert_level' => 'HIGH',
                'threshold' => self::SUCCESS_RATE_THRESHOLD,
            ]);

            $hasAlerts = true;
        }

        return $hasAlerts;
    }

    /**
     * Check for orphaned appointments (processing time service but no phases)
     */
    protected function checkOrphanedAppointments(): int
    {
        $orphaned = Appointment::whereHas('service', function($q) {
            $q->where('has_processing_time', true);
        })
        ->whereDoesntHave('phases')
        ->where('created_at', '>=', now()->startOfDay())
        ->get();

        if ($orphaned->count() > 0) {
            $this->warn('⚠️  Found ' . $orphaned->count() . ' orphaned appointments without phases');

            if ($this->option('details')) {
                $this->line('  Appointment IDs: ' . $orphaned->pluck('id')->implode(', '));
            }

            Log::info('Processing Time: Found orphaned appointments without phases', [
                'count' => $orphaned->count(),
                'appointment_ids' => $orphaned->pluck('id')->toArray(),
            ]);
        }

        return $orphaned->count();
    }
}
