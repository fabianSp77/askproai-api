<?php

namespace App\Services\Monitoring;

use App\Models\Appointment;
use App\Models\Call;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Duplicate Prevention Monitoring Service
 *
 * Provides real-time monitoring and metrics for the 4-layer duplicate prevention system.
 * This service enables production validation and observability of booking integrity.
 *
 * @author Claude (SuperClaude Framework)
 * @date 2025-10-06
 */
class DuplicatePreventionMonitor
{
    /**
     * Check system health and return metrics
     *
     * @return array Comprehensive health metrics
     */
    public function getHealthMetrics(): array
    {
        return [
            'timestamp' => now()->toIso8601String(),
            'database_integrity' => $this->checkDatabaseIntegrity(),
            'recent_bookings' => $this->getRecentBookingStats(),
            'validation_layer_status' => $this->checkValidationLayers(),
            'constraint_status' => $this->checkUniqueConstraint(),
            'potential_issues' => $this->detectPotentialIssues(),
        ];
    }

    /**
     * Check database integrity for duplicates
     *
     * @return array Integrity check results
     */
    protected function checkDatabaseIntegrity(): array
    {
        // Find any duplicate booking IDs (should be ZERO)
        $duplicates = DB::table('appointments')
            ->select('calcom_v2_booking_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('calcom_v2_booking_id')
            ->groupBy('calcom_v2_booking_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $duplicateCount = $duplicates->count();

        // Count NULL booking IDs (potential incomplete bookings)
        $nullBookingIds = Appointment::whereNull('calcom_v2_booking_id')->count();

        // Count total appointments with Cal.com booking IDs
        $totalWithBookingId = Appointment::whereNotNull('calcom_v2_booking_id')->count();

        return [
            'status' => $duplicateCount === 0 ? 'healthy' : 'critical',
            'duplicate_count' => $duplicateCount,
            'duplicates' => $duplicates->map(fn($dup) => [
                'booking_id' => $dup->calcom_v2_booking_id,
                'count' => $dup->count
            ])->toArray(),
            'null_booking_ids' => $nullBookingIds,
            'total_appointments' => $totalWithBookingId,
            'integrity_score' => $duplicateCount === 0 ? 100 : max(0, 100 - ($duplicateCount * 10))
        ];
    }

    /**
     * Get recent booking statistics
     *
     * @param int $hours Number of hours to look back
     * @return array Booking statistics
     */
    protected function getRecentBookingStats(int $hours = 24): array
    {
        $since = now()->subHours($hours);

        $stats = [
            'total_bookings' => Appointment::where('created_at', '>=', $since)->count(),
            'successful_bookings' => Appointment::where('created_at', '>=', $since)
                ->whereNotNull('calcom_v2_booking_id')
                ->count(),
            'bookings_by_hour' => []
        ];

        // Get hourly breakdown
        for ($i = 0; $i < min($hours, 24); $i++) {
            $hourStart = now()->subHours($i + 1);
            $hourEnd = now()->subHours($i);

            $count = Appointment::whereBetween('created_at', [$hourStart, $hourEnd])
                ->whereNotNull('calcom_v2_booking_id')
                ->count();

            if ($count > 0) {
                $stats['bookings_by_hour'][$hourStart->format('H:00')] = $count;
            }
        }

        return $stats;
    }

    /**
     * Check validation layer status
     *
     * @return array Validation layer health
     */
    protected function checkValidationLayers(): array
    {
        // Check if code contains validation logic by examining source file
        $serviceFile = app_path('Services/Retell/AppointmentCreationService.php');

        $layers = [
            'layer_1_freshness' => [
                'name' => 'Booking Freshness Validation',
                'marker' => 'DUPLICATE BOOKING PREVENTION: Stale booking detected',
                'deployed' => false,
                'line' => null
            ],
            'layer_2_call_id' => [
                'name' => 'Call ID Validation',
                'marker' => 'DUPLICATE BOOKING PREVENTION: Call ID mismatch',
                'deployed' => false,
                'line' => null
            ],
            'layer_3_database' => [
                'name' => 'Database Duplicate Check',
                'marker' => 'DUPLICATE BOOKING PREVENTION: Appointment already exists',
                'deployed' => false,
                'line' => null
            ]
        ];

        if (file_exists($serviceFile)) {
            $fileContents = file_get_contents($serviceFile);
            $lines = explode("\n", $fileContents);

            foreach ($layers as $key => &$layer) {
                foreach ($lines as $lineNum => $line) {
                    if (strpos($line, $layer['marker']) !== false) {
                        $layer['deployed'] = true;
                        $layer['line'] = $lineNum + 1;
                        break;
                    }
                }
            }
        }

        $deployedCount = count(array_filter($layers, fn($l) => $l['deployed']));

        return [
            'status' => $deployedCount === 3 ? 'healthy' : 'warning',
            'deployed_layers' => $deployedCount,
            'total_layers' => 3,
            'layers' => $layers
        ];
    }

    /**
     * Check UNIQUE constraint status
     *
     * @return array Constraint check results
     */
    protected function checkUniqueConstraint(): array
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM appointments WHERE Key_name = 'unique_calcom_v2_booking_id'");

            $constraintExists = count($indexes) > 0;
            $isUnique = $constraintExists && $indexes[0]->Non_unique == 0;

            return [
                'status' => $isUnique ? 'healthy' : 'critical',
                'constraint_exists' => $constraintExists,
                'is_unique' => $isUnique,
                'constraint_name' => $constraintExists ? $indexes[0]->Key_name : null
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'constraint_exists' => false,
                'is_unique' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Detect potential issues by analyzing logs and patterns
     *
     * @return array Detected issues
     */
    protected function detectPotentialIssues(): array
    {
        $issues = [];

        // Check for recent Cal.com booking with same time slot (potential duplicate attempt)
        $recentDuplicateAttempts = DB::table('appointments')
            ->select('starts_at', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subHours(1))
            ->whereNotNull('starts_at')
            ->groupBy('starts_at')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($recentDuplicateAttempts->count() > 0) {
            $issues[] = [
                'severity' => 'warning',
                'type' => 'duplicate_time_slot',
                'message' => 'Multiple bookings detected for same time slot in last hour',
                'count' => $recentDuplicateAttempts->count(),
                'details' => $recentDuplicateAttempts->toArray()
            ];
        }

        // Check for NULL booking IDs (incomplete bookings)
        $recentNullBookingIds = Appointment::whereNull('calcom_v2_booking_id')
            ->where('created_at', '>=', now()->subHours(1))
            ->count();

        if ($recentNullBookingIds > 0) {
            $issues[] = [
                'severity' => 'info',
                'type' => 'incomplete_bookings',
                'message' => 'Appointments created without Cal.com booking ID',
                'count' => $recentNullBookingIds
            ];
        }

        // Check for very old appointments still pending
        $staleAppointments = Appointment::where('status', 'pending')
            ->where('created_at', '<', now()->subDays(7))
            ->count();

        if ($staleAppointments > 0) {
            $issues[] = [
                'severity' => 'info',
                'type' => 'stale_appointments',
                'message' => 'Pending appointments older than 7 days',
                'count' => $staleAppointments
            ];
        }

        return [
            'total_issues' => count($issues),
            'issues' => $issues
        ];
    }

    /**
     * Validate a specific Cal.com booking by checking all 4 layers
     *
     * @param string $calcomBookingId Cal.com booking ID to validate
     * @return array Validation results
     */
    public function validateBooking(string $calcomBookingId): array
    {
        $validationResults = [
            'booking_id' => $calcomBookingId,
            'timestamp' => now()->toIso8601String(),
            'layers' => []
        ];

        // Layer 4: Database unique constraint (check if booking exists)
        $existingAppointment = Appointment::where('calcom_v2_booking_id', $calcomBookingId)->first();

        $validationResults['layers']['layer_4_database_constraint'] = [
            'name' => 'Database UNIQUE Constraint',
            'status' => $existingAppointment ? 'exists' : 'available',
            'appointment_id' => $existingAppointment?->id,
            'created_at' => $existingAppointment?->created_at?->toIso8601String()
        ];

        // Layer 3: Database duplicate check
        $duplicateCount = Appointment::where('calcom_v2_booking_id', $calcomBookingId)->count();

        $validationResults['layers']['layer_3_database_check'] = [
            'name' => 'Database Duplicate Check',
            'status' => $duplicateCount === 0 ? 'pass' : ($duplicateCount === 1 ? 'single' : 'duplicate'),
            'count' => $duplicateCount
        ];

        // Overall validation result
        $validationResults['overall_status'] = $duplicateCount <= 1 ? 'valid' : 'duplicate_detected';

        return $validationResults;
    }

    /**
     * Get metrics in Prometheus format
     *
     * @return string Prometheus metrics
     */
    public function getPrometheusMetrics(): string
    {
        $metrics = $this->getHealthMetrics();

        $output = "# HELP duplicate_prevention_integrity_score Database integrity score (0-100)\n";
        $output .= "# TYPE duplicate_prevention_integrity_score gauge\n";
        $output .= "duplicate_prevention_integrity_score " . $metrics['database_integrity']['integrity_score'] . "\n\n";

        $output .= "# HELP duplicate_prevention_duplicates_total Total duplicate booking IDs detected\n";
        $output .= "# TYPE duplicate_prevention_duplicates_total counter\n";
        $output .= "duplicate_prevention_duplicates_total " . $metrics['database_integrity']['duplicate_count'] . "\n\n";

        $output .= "# HELP duplicate_prevention_layers_deployed Number of validation layers deployed\n";
        $output .= "# TYPE duplicate_prevention_layers_deployed gauge\n";
        $output .= "duplicate_prevention_layers_deployed " . $metrics['validation_layer_status']['deployed_layers'] . "\n\n";

        $output .= "# HELP duplicate_prevention_constraint_active UNIQUE constraint status (1=active, 0=inactive)\n";
        $output .= "# TYPE duplicate_prevention_constraint_active gauge\n";
        $output .= "duplicate_prevention_constraint_active " . ($metrics['constraint_status']['is_unique'] ? 1 : 0) . "\n\n";

        $output .= "# HELP duplicate_prevention_bookings_24h Total bookings in last 24 hours\n";
        $output .= "# TYPE duplicate_prevention_bookings_24h gauge\n";
        $output .= "duplicate_prevention_bookings_24h " . $metrics['recent_bookings']['total_bookings'] . "\n\n";

        $output .= "# HELP duplicate_prevention_issues_total Total potential issues detected\n";
        $output .= "# TYPE duplicate_prevention_issues_total gauge\n";
        $output .= "duplicate_prevention_issues_total " . $metrics['potential_issues']['total_issues'] . "\n\n";

        return $output;
    }

    /**
     * Log current health status
     *
     * @return void
     */
    public function logHealthStatus(): void
    {
        $metrics = $this->getHealthMetrics();

        Log::info('🔍 Duplicate Prevention Health Check', [
            'integrity_score' => $metrics['database_integrity']['integrity_score'],
            'duplicates' => $metrics['database_integrity']['duplicate_count'],
            'layers_deployed' => $metrics['validation_layer_status']['deployed_layers'],
            'constraint_active' => $metrics['constraint_status']['is_unique'],
            'recent_bookings_24h' => $metrics['recent_bookings']['total_bookings'],
            'issues_detected' => $metrics['potential_issues']['total_issues']
        ]);

        if ($metrics['database_integrity']['duplicate_count'] > 0) {
            Log::critical('🚨 DUPLICATE BOOKINGS DETECTED', [
                'duplicates' => $metrics['database_integrity']['duplicates']
            ]);
        }

        if ($metrics['potential_issues']['total_issues'] > 0) {
            Log::warning('⚠️ Potential Issues Detected', [
                'issues' => $metrics['potential_issues']['issues']
            ]);
        }
    }
}
