<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncCallAppointmentFlags extends Command
{
    protected $signature = 'calls:sync-appointment-flags
                            {--dry-run : Show what would be synced without actually syncing}';

    protected $description = 'Sync appointment_made and converted_appointment_id flags for calls with appointments';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('🔄 Syncing Call Appointment Flags');
        $this->info('============================================================');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Find calls that have appointments but inconsistent flags
        $inconsistentCalls = DB::table('calls as c')
            ->join('appointments as a', 'a.call_id', '=', 'c.id')
            ->select(
                'c.id as call_id',
                'c.appointment_made',
                'c.converted_appointment_id',
                'a.id as appointment_id',
                'a.starts_at',
                'a.status'
            )
            ->where(function ($query) {
                $query->where('c.appointment_made', '=', 0)
                      ->orWhereNull('c.converted_appointment_id');
            })
            ->orderBy('c.id')
            ->get();

        $this->info('📊 Found ' . $inconsistentCalls->count() . ' calls with inconsistent flags');
        $this->newLine();

        if ($inconsistentCalls->isEmpty()) {
            $this->info('✅ All calls are already consistent!');
            return 0;
        }

        // Display table of inconsistent calls
        $this->table(
            ['Call ID', 'Current appointment_made', 'Current converted_appointment_id', 'Actual Appointment ID', 'Status'],
            $inconsistentCalls->map(function ($call) {
                return [
                    $call->call_id,
                    $call->appointment_made ? 'true' : 'false',
                    $call->converted_appointment_id ?? 'NULL',
                    $call->appointment_id,
                    $call->status
                ];
            })->toArray()
        );

        $this->newLine();

        if (!$dryRun) {
            if (!$this->confirm('Do you want to sync these calls?', true)) {
                $this->warn('❌ Sync cancelled by user');
                return 1;
            }

            $this->info('🚀 Starting sync...');
            $this->newLine();

            $progressBar = $this->output->createProgressBar($inconsistentCalls->count());
            $progressBar->start();

            $synced = 0;
            $failed = 0;

            foreach ($inconsistentCalls as $call) {
                try {
                    DB::table('calls')
                        ->where('id', $call->call_id)
                        ->update([
                            'appointment_made' => true,
                            'converted_appointment_id' => $call->appointment_id,
                            'updated_at' => now()
                        ]);

                    $synced++;
                } catch (\Exception $e) {
                    $failed++;
                    Log::error('Failed to sync call appointment flags', [
                        'call_id' => $call->call_id,
                        'error' => $e->getMessage()
                    ]);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Display results
            $this->info('✅ Sync Complete!');
            $this->newLine();

            $this->table(
                ['Metric', 'Count'],
                [
                    ['Successfully Synced', $synced],
                    ['Failed', $failed],
                    ['Total Processed', $inconsistentCalls->count()]
                ]
            );

            // Verify consistency
            $this->newLine();
            $this->info('📊 Verifying consistency...');

            $remaining = DB::table('calls as c')
                ->join('appointments as a', 'a.call_id', '=', 'c.id')
                ->where(function ($query) {
                    $query->where('c.appointment_made', '=', 0)
                          ->orWhereNull('c.converted_appointment_id');
                })
                ->count();

            if ($remaining === 0) {
                $this->info('✅ All calls are now consistent!');
            } else {
                $this->warn('⚠️  Still ' . $remaining . ' inconsistent calls remaining');
            }
        } else {
            $this->info('✅ Dry run complete - no changes made');
            $this->info('💡 Run without --dry-run to apply changes');
        }

        return 0;
    }
}
