<?php

namespace App\Services\Monitoring;

use App\Models\Call;
use App\Models\Appointment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

/**
 * Data Consistency Monitor
 *
 * Real-time detection and alerting of data inconsistencies in appointment bookings.
 *
 * FEATURES:
 * - Real-time inconsistency detection
 * - Multiple detection rules
 * - Alert routing (Slack, Email, Metrics)
 * - Daily validation reports
 * - Automatic issue tracking
 *
 * DETECTION RULES:
 * 1. Session outcome vs appointment_made mismatch
 * 2. appointment_made=1 but no appointment in DB
 * 3. Calls without direction field
 * 4. Orphaned appointments (no call link)
 * 5. Recent creation failures
 *
 * USAGE:
 * // Check single call
 * $inconsistencies = $monitor->checkCall($call);
 *
 * // Check recent calls
 * $summary = $monitor->detectInconsistencies();
 *
 * // Alert
 * $monitor->alertInconsistency('phantom_booking', $context);
 */
class DataConsistencyMonitor
{
    // Alert severity levels
    private const SEVERITY_CRITICAL = 'critical';
    private const SEVERITY_WARNING = 'warning';
    private const SEVERITY_INFO = 'info';

    // Detection time windows
    private const RECENT_HOURS = 1;
    private const REPORT_DAYS = 1;

    // Alert throttling (prevent spam)
    private const ALERT_THROTTLE_MINUTES = 5;

    /**
     * Detect all types of inconsistencies
     *
     * @return array Summary of detected inconsistencies
     */
    public function detectInconsistencies(): array
    {
        Log::info('🔍 Running data consistency checks');

        $summary = [
            'timestamp' => now()->toIso8601String(),
            'inconsistencies' => [],
            'totals' => [
                'critical' => 0,
                'warning' => 0,
                'info' => 0
            ]
        ];

        // Rule 1: Session outcome vs appointment_made mismatch
        $sessionMismatches = $this->detectSessionOutcomeMismatch();
        if (!empty($sessionMismatches)) {
            $summary['inconsistencies']['session_outcome_mismatch'] = $sessionMismatches;
            $summary['totals']['critical'] += count($sessionMismatches);
        }

        // Rule 2: appointment_made=1 but no appointment exists
        $missingAppointments = $this->detectMissingAppointments();
        if (!empty($missingAppointments)) {
            $summary['inconsistencies']['missing_appointments'] = $missingAppointments;
            $summary['totals']['critical'] += count($missingAppointments);
        }

        // Rule 3: Calls without direction
        $missingDirections = $this->detectMissingDirections();
        if (!empty($missingDirections)) {
            $summary['inconsistencies']['missing_directions'] = $missingDirections;
            $summary['totals']['warning'] += count($missingDirections);
        }

        // Rule 4: Orphaned appointments
        $orphanedAppointments = $this->detectOrphanedAppointments();
        if (!empty($orphanedAppointments)) {
            $summary['inconsistencies']['orphaned_appointments'] = $orphanedAppointments;
            $summary['totals']['warning'] += count($orphanedAppointments);
        }

        // Rule 5: Recent failures
        $recentFailures = $this->detectRecentFailures();
        if (!empty($recentFailures)) {
            $summary['inconsistencies']['recent_failures'] = $recentFailures;
            $summary['totals']['info'] += count($recentFailures);
        }

        Log::info('📊 Data consistency check completed', [
            'critical' => $summary['totals']['critical'],
            'warning' => $summary['totals']['warning'],
            'info' => $summary['totals']['info']
        ]);

        return $summary;
    }

    /**
     * Check single call for inconsistencies
     *
     * @param Call $call
     * @return array List of inconsistencies found
     */
    public function checkCall(Call $call): array
    {
        $inconsistencies = [];

        // Check 1: Session outcome vs appointment_made
        if ($call->session_outcome === 'appointment_booked' && !$call->appointment_made) {
            $inconsistencies[] = [
                'type' => 'session_outcome_mismatch',
                'severity' => self::SEVERITY_CRITICAL,
                'description' => 'session_outcome is appointment_booked but appointment_made is false',
                'call_id' => $call->id,
                'session_outcome' => $call->session_outcome,
                'appointment_made' => $call->appointment_made
            ];
        }

        // Check 2: appointment_made but no appointment exists
        if ($call->appointment_made) {
            $appointmentExists = Appointment::where('call_id', $call->id)->exists();
            if (!$appointmentExists) {
                $inconsistencies[] = [
                    'type' => 'missing_appointment',
                    'severity' => self::SEVERITY_CRITICAL,
                    'description' => 'appointment_made is true but no appointment exists',
                    'call_id' => $call->id,
                    'appointment_made' => $call->appointment_made
                ];
            }
        }

        // Check 3: Missing direction
        if (is_null($call->direction)) {
            $inconsistencies[] = [
                'type' => 'missing_direction',
                'severity' => self::SEVERITY_WARNING,
                'description' => 'Call direction is NULL',
                'call_id' => $call->id
            ];
        }

        // Check 4: Inconsistent link status
        if ($call->appointment_made && $call->appointment_link_status !== 'linked') {
            $inconsistencies[] = [
                'type' => 'inconsistent_link_status',
                'severity' => self::SEVERITY_WARNING,
                'description' => 'appointment_made is true but link_status is not linked',
                'call_id' => $call->id,
                'appointment_made' => $call->appointment_made,
                'appointment_link_status' => $call->appointment_link_status
            ];
        }

        return $inconsistencies;
    }

    /**
     * Rule 1: Detect session_outcome vs appointment_made mismatch
     *
     * @return array
     */
    private function detectSessionOutcomeMismatch(): array
    {
        $calls = DB::table('calls')
            ->select('id', 'retell_call_id', 'session_outcome', 'appointment_made', 'created_at')
            ->where('session_outcome', 'appointment_booked')
            ->where('appointment_made', false)
            ->where('created_at', '>=', now()->subHours(self::RECENT_HOURS))
            ->get();

        $mismatches = [];
        foreach ($calls as $call) {
            $mismatches[] = [
                'call_id' => $call->id,
                'retell_call_id' => $call->retell_call_id,
                'session_outcome' => $call->session_outcome,
                'appointment_made' => $call->appointment_made,
                'created_at' => $call->created_at,
                'severity' => self::SEVERITY_CRITICAL
            ];

            // Alert for each mismatch
            $this->alertInconsistency('session_outcome_mismatch', [
                'call_id' => $call->id,
                'retell_call_id' => $call->retell_call_id
            ]);
        }

        return $mismatches;
    }

    /**
     * Rule 2: Detect appointment_made=1 but no appointment in DB
     *
     * @return array
     */
    private function detectMissingAppointments(): array
    {
        $calls = DB::table('calls as c')
            ->leftJoin('appointments as a', 'a.call_id', '=', 'c.id')
            ->select('c.id', 'c.retell_call_id', 'c.appointment_made', 'c.created_at')
            ->where('c.appointment_made', true)
            ->whereNull('a.id')
            ->where('c.created_at', '>=', now()->subHours(self::RECENT_HOURS))
            ->get();

        $missing = [];
        foreach ($calls as $call) {
            $missing[] = [
                'call_id' => $call->id,
                'retell_call_id' => $call->retell_call_id,
                'appointment_made' => $call->appointment_made,
                'appointment_exists' => false,
                'created_at' => $call->created_at,
                'severity' => self::SEVERITY_CRITICAL
            ];

            // Alert for each missing appointment
            $this->alertInconsistency('missing_appointment', [
                'call_id' => $call->id,
                'retell_call_id' => $call->retell_call_id
            ]);
        }

        return $missing;
    }

    /**
     * Rule 3: Detect calls without direction field
     *
     * @return array
     */
    private function detectMissingDirections(): array
    {
        $calls = DB::table('calls')
            ->select('id', 'retell_call_id', 'direction', 'created_at')
            ->whereNull('direction')
            ->where('created_at', '>=', now()->subHours(self::RECENT_HOURS))
            ->get();

        $missing = [];
        foreach ($calls as $call) {
            $missing[] = [
                'call_id' => $call->id,
                'retell_call_id' => $call->retell_call_id,
                'direction' => null,
                'created_at' => $call->created_at,
                'severity' => self::SEVERITY_WARNING,
                'auto_fixable' => true
            ];
        }

        return $missing;
    }

    /**
     * Rule 4: Detect orphaned appointments (no call link)
     *
     * @return array
     */
    private function detectOrphanedAppointments(): array
    {
        $appointments = DB::table('appointments')
            ->select('id', 'calcom_v2_booking_id', 'customer_id', 'call_id', 'source', 'created_at')
            ->whereNull('call_id')
            ->where('source', 'retell_webhook')
            ->where('created_at', '>=', now()->subHours(self::RECENT_HOURS))
            ->get();

        $orphaned = [];
        foreach ($appointments as $appointment) {
            $orphaned[] = [
                'appointment_id' => $appointment->id,
                'calcom_booking_id' => $appointment->calcom_v2_booking_id,
                'customer_id' => $appointment->customer_id,
                'call_id' => null,
                'source' => $appointment->source,
                'created_at' => $appointment->created_at,
                'severity' => self::SEVERITY_WARNING,
                'auto_fixable' => true
            ];
        }

        return $orphaned;
    }

    /**
     * Rule 5: Detect recent creation failures
     *
     * @return array
     */
    private function detectRecentFailures(): array
    {
        // Find calls where appointment was requested but not confirmed (booking failed)
        $failures = DB::table('calls')
            ->select('id', 'retell_call_id', 'booking_confirmed', 'appointment_requested', 'created_at', 'disconnect_reason')
            ->where('appointment_requested', true)
            ->where('booking_confirmed', false)
            ->where('created_at', '>=', now()->subHours(self::RECENT_HOURS))
            ->get();

        $recentFailures = [];
        foreach ($failures as $failure) {
            $recentFailures[] = [
                'call_id' => $failure->id,
                'retell_call_id' => $failure->retell_call_id,
                'reason' => $failure->disconnect_reason ?? 'Unknown reason',
                'created_at' => $failure->created_at,
                'severity' => self::SEVERITY_INFO
            ];
        }

        return $recentFailures;
    }

    /**
     * Alert on detected inconsistency
     *
     * @param string $type Inconsistency type
     * @param array $context Additional context
     * @return void
     */
    public function alertInconsistency(string $type, array $context = []): void
    {
        // Throttle alerts to prevent spam
        $throttleKey = "alert_throttle:{$type}:" . ($context['call_id'] ?? 'global');
        if (Cache::has($throttleKey)) {
            Log::debug('Alert throttled', ['type' => $type, 'context' => $context]);
            return;
        }

        Cache::put($throttleKey, true, now()->addMinutes(self::ALERT_THROTTLE_MINUTES));

        // Determine severity
        $severity = $this->getSeverityForType($type);

        Log::channel('consistency')->error("🚨 Data inconsistency detected: {$type}", [
            'type' => $type,
            'severity' => $severity,
            'context' => $context,
            'detected_at' => now()->toIso8601String()
        ]);

        // Store alert in database
        DB::table('data_consistency_alerts')->insert([
            'alert_type' => $type,
            'entity_type' => $context['entity_type'] ?? 'call',
            'entity_id' => $context['call_id'] ?? $context['appointment_id'] ?? null,
            'severity' => $severity,
            'description' => $this->getDescriptionForType($type),
            'metadata' => json_encode($context),
            'detected_at' => now(),
            'auto_corrected' => false,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Send alerts based on severity
        if ($severity === self::SEVERITY_CRITICAL) {
            $this->sendCriticalAlert($type, $context);
        } elseif ($severity === self::SEVERITY_WARNING) {
            $this->sendWarningAlert($type, $context);
        }

        // Increment metrics
        $this->incrementMetric("data_inconsistency_detected", ['type' => $type, 'severity' => $severity]);
    }

    /**
     * Generate daily validation report
     *
     * @return array Report data
     */
    public function generateDailyReport(): array
    {
        $startDate = now()->subDays(self::REPORT_DAYS)->startOfDay();
        $endDate = now()->endOfDay();

        Log::info('📊 Generating daily data consistency report', [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString()
        ]);

        // Total calls and appointments
        $totalCalls = Call::whereBetween('created_at', [$startDate, $endDate])->count();
        $totalAppointments = Appointment::whereBetween('created_at', [$startDate, $endDate])->count();

        // Inconsistencies by type
        $inconsistenciesByType = DB::table('data_consistency_alerts')
            ->select('alert_type', DB::raw('COUNT(*) as count'))
            ->whereBetween('detected_at', [$startDate, $endDate])
            ->groupBy('alert_type')
            ->get()
            ->pluck('count', 'alert_type')
            ->toArray();

        $totalInconsistencies = array_sum($inconsistenciesByType);

        // Auto-corrected vs manual review
        $autoCorrected = DB::table('data_consistency_alerts')
            ->whereBetween('detected_at', [$startDate, $endDate])
            ->where('auto_corrected', true)
            ->count();

        $manualReview = DB::table('data_consistency_alerts')
            ->whereBetween('detected_at', [$startDate, $endDate])
            ->where('auto_corrected', false)
            ->count();

        // Top issues
        $topIssues = DB::table('data_consistency_alerts')
            ->select('alert_type', 'description', DB::raw('COUNT(*) as count'))
            ->whereBetween('detected_at', [$startDate, $endDate])
            ->groupBy('alert_type', 'description')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Consistency rate
        $consistencyRate = $totalCalls > 0
            ? (($totalCalls - $totalInconsistencies) / $totalCalls) * 100
            : 100;

        $report = [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString()
            ],
            'summary' => [
                'total_calls' => $totalCalls,
                'total_appointments' => $totalAppointments,
                'total_inconsistencies' => $totalInconsistencies,
                'consistency_rate_pct' => round($consistencyRate, 2)
            ],
            'inconsistencies_by_type' => $inconsistenciesByType,
            'resolution' => [
                'auto_corrected' => $autoCorrected,
                'manual_review' => $manualReview
            ],
            'top_issues' => $topIssues->toArray(),
            'generated_at' => now()->toIso8601String()
        ];

        Log::info('✅ Daily report generated', [
            'consistency_rate' => $report['summary']['consistency_rate_pct'],
            'total_inconsistencies' => $totalInconsistencies
        ]);

        return $report;
    }

    /**
     * Get severity for inconsistency type
     *
     * @param string $type
     * @return string
     */
    private function getSeverityForType(string $type): string
    {
        return match($type) {
            'session_outcome_mismatch',
            'missing_appointment',
            'appointment_validation_failed' => self::SEVERITY_CRITICAL,

            'missing_direction',
            'orphaned_appointment',
            'inconsistent_link_status' => self::SEVERITY_WARNING,

            default => self::SEVERITY_INFO
        };
    }

    /**
     * Get human-readable description for type
     *
     * @param string $type
     * @return string
     */
    private function getDescriptionForType(string $type): string
    {
        return match($type) {
            'session_outcome_mismatch' => 'Session outcome does not match appointment_made flag',
            'missing_appointment' => 'Appointment marked as made but not found in database',
            'missing_direction' => 'Call direction field is NULL',
            'orphaned_appointment' => 'Appointment has no call_id link',
            'appointment_validation_failed' => 'Post-booking validation failed',
            'inconsistent_link_status' => 'Appointment link status inconsistent',
            default => "Data inconsistency detected: {$type}"
        };
    }

    /**
     * Send critical alert (Slack + immediate notification)
     *
     * @param string $type
     * @param array $context
     * @return void
     */
    private function sendCriticalAlert(string $type, array $context): void
    {
        // TODO: Integrate with Slack/notification system
        Log::critical("🚨 CRITICAL ALERT: {$type}", $context);

        // Placeholder for Slack integration
        // Notification::route('slack', config('services.slack.webhook'))
        //     ->notify(new DataConsistencyAlert($type, $context, 'critical'));
    }

    /**
     * Send warning alert (logged + included in digest)
     *
     * @param string $type
     * @param array $context
     * @return void
     */
    private function sendWarningAlert(string $type, array $context): void
    {
        Log::warning("⚠️ WARNING: {$type}", $context);

        // Warnings are included in 4-hour digest emails
    }

    /**
     * Increment monitoring metric
     *
     * @param string $metric
     * @param array $labels
     * @return void
     */
    private function incrementMetric(string $metric, array $labels = []): void
    {
        // TODO: Integrate with Prometheus/metrics system
        Log::debug("📊 Metric: {$metric}", $labels);

        // Placeholder for Prometheus integration
        // app('prometheus')->incrementCounter($metric, $labels);
    }
}
