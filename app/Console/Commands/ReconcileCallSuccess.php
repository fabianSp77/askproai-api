<?php

namespace App\Console\Commands;

use App\Models\Call;
use App\Models\Appointment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reconciliation Command for False Negatives
 *
 * Corrects calls that are marked as unsuccessful but have associated appointments.
 * This is a safety net for race conditions between booking and Retell sync.
 *
 * @see RCA 2025-11-27: False Negative Bug Analysis
 */
class ReconcileCallSuccess extends Command
{
    protected $signature = 'calls:reconcile-success
                            {--dry-run : Show what would be corrected without making changes}
                            {--days=7 : Number of days to look back}
                            {--detailed : Show detailed output for each call}';

    protected $description = 'Reconcile call_successful flags for calls with existing appointments (fixes false negatives)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $days = (int) $this->option('days');
        $detailed = $this->option('detailed');

        $this->info('🔍 Starting Call Success Reconciliation');
        $this->info(str_repeat('=', 50));

        if ($dryRun) {
            $this->warn('🏃 DRY RUN MODE - No changes will be made');
        }

        // Find false negatives: calls with call_successful = false but existing appointments
        $falseNegatives = Call::where('call_successful', false)
            ->where('created_at', '>=', now()->subDays($days))
            ->whereHas('appointments', function ($query) {
                $query->whereIn('status', ['confirmed', 'scheduled', 'pending', 'booked'])
                    ->whereNull('deleted_at');
            })
            ->get();

        $this->info("📊 Found {$falseNegatives->count()} potential false negatives in last {$days} days");
        $this->newLine();

        if ($falseNegatives->isEmpty()) {
            $this->info('✅ No false negatives found. System is healthy!');
            return self::SUCCESS;
        }

        $corrected = 0;
        $errors = 0;

        foreach ($falseNegatives as $call) {
            $appointment = $call->appointments()->first();

            if ($detailed || $dryRun) {
                // Use getRawOriginal to bypass the accessor which overrides call_successful
                $rawCallSuccessful = $call->getRawOriginal('call_successful');
                $this->line("Call #{$call->id} (retell: {$call->retell_call_id})");
                $this->line("  - DB call_successful: " . ($rawCallSuccessful ? 'true' : 'false'));
                $this->line("  - Appointment #{$appointment->id}: {$appointment->status}");
                $this->line("  - Cal.com UID: " . ($appointment->calcom_v2_booking_uid ?? 'N/A'));
            }

            if (!$dryRun) {
                try {
                    DB::transaction(function () use ($call, $appointment) {
                        $call->update([
                            'call_successful' => true,
                            'appointment_made' => true,
                        ]);

                        Log::info('🔧 ReconcileCallSuccess: Corrected false negative', [
                            'call_id' => $call->id,
                            'retell_call_id' => $call->retell_call_id,
                            'appointment_id' => $appointment->id,
                            'appointment_status' => $appointment->status,
                            'calcom_uid' => $appointment->calcom_v2_booking_uid,
                        ]);
                    });

                    $corrected++;
                    $this->info("  ✅ Corrected!");
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("  ❌ Error: {$e->getMessage()}");

                    Log::error('ReconcileCallSuccess: Failed to correct false negative', [
                        'call_id' => $call->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                $this->warn("  → Would correct this call");
                $corrected++;
            }

            $this->newLine();
        }

        // Summary
        $this->info(str_repeat('=', 50));
        $this->info('📋 Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['False Negatives Found', $falseNegatives->count()],
                [$dryRun ? 'Would Correct' : 'Corrected', $corrected],
                ['Errors', $errors],
            ]
        );

        if ($corrected > 0 && !$dryRun) {
            Log::info('🔧 ReconcileCallSuccess completed', [
                'false_negatives_found' => $falseNegatives->count(),
                'corrected' => $corrected,
                'errors' => $errors,
                'days_lookback' => $days,
            ]);
        }

        // Alert if high number of corrections
        if ($corrected > 10) {
            $this->warn('⚠️  High number of corrections! Consider investigating root cause.');
            Log::warning('ReconcileCallSuccess: High correction count', [
                'corrected' => $corrected,
                'days' => $days,
            ]);
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
