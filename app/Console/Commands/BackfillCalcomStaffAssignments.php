<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\CalcomHostMappingService;
use App\Services\Strategies\HostMatchContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillCalcomStaffAssignments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:backfill-calcom-staff
                            {--dry-run : Preview changes without updating}
                            {--limit=100 : Number of appointments to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill staff_id for Cal.com appointments with NULL staff using Cal.com host mapping';

    /**
     * Execute the console command.
     */
    public function handle(CalcomHostMappingService $hostMapping): int
    {
        $this->info('🔍 Starting Cal.com staff assignment backfill...');
        $this->newLine();

        // Query appointments that need staff assignment
        $query = Appointment::whereNotNull('calcom_v2_booking_id')
            ->whereNull('staff_id')
            ->whereNotNull('metadata')
            ->orderBy('created_at', 'desc');

        if ($this->option('limit')) {
            $query->limit((int) $this->option('limit'));
        }

        $appointments = $query->get();

        if ($appointments->isEmpty()) {
            $this->info('✅ No appointments need staff assignment backfill');
            return self::SUCCESS;
        }

        $this->info("Found {$appointments->count()} appointments with NULL staff_id");
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('🔶 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $updated = 0;
        $failed = 0;
        $noHostData = 0;
        $noMatch = 0;

        $bar = $this->output->createProgressBar($appointments->count());
        $bar->start();

        foreach ($appointments as $appointment) {
            try {
                // Extract Cal.com data from metadata
                $metadata = $appointment->metadata;

                if (!is_array($metadata)) {
                    $metadata = json_decode($metadata, true);
                }

                // Try both possible keys for Cal.com data
                $calcomData = $metadata['cal_com_data'] ?? $metadata['calcom_booking'] ?? null;

                if (!$calcomData) {
                    $noHostData++;
                    $bar->advance();
                    continue;
                }

                // Extract host data
                $hostData = $hostMapping->extractHostFromBooking($calcomData);

                if (!$hostData) {
                    $noHostData++;
                    $bar->advance();
                    continue;
                }

                // Build context
                $context = new HostMatchContext(
                    companyId: $appointment->company_id,
                    branchId: $appointment->branch_id,
                    serviceId: $appointment->service_id,
                    calcomBooking: $calcomData
                );

                // Resolve staff
                $staffId = $hostMapping->resolveStaffForHost($hostData, $context);

                if (!$staffId) {
                    $noMatch++;
                    $bar->advance();

                    Log::channel('calcom')->debug('[Backfill] No staff match', [
                        'appointment_id' => $appointment->id,
                        'host_email' => $hostData['email'] ?? null,
                        'host_name' => $hostData['name'] ?? null,
                    ]);
                    continue;
                }

                // Update appointment (if not dry-run)
                if (!$this->option('dry-run')) {
                    $appointment->update([
                        'staff_id' => $staffId,
                        'calcom_host_id' => $hostData['id'] ?? null,
                    ]);

                    Log::channel('calcom')->info('[Backfill] Staff assigned', [
                        'appointment_id' => $appointment->id,
                        'staff_id' => $staffId,
                        'calcom_host_id' => $hostData['id'] ?? null,
                        'host_email' => $hostData['email'] ?? null,
                    ]);
                }

                $updated++;

            } catch (\Exception $e) {
                $failed++;

                Log::channel('calcom')->error('[Backfill] Error', [
                    'appointment_id' => $appointment->id ?? null,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $this->error("\nAppointment {$appointment->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary table
        $this->table(
            ['Status', 'Count', 'Description'],
            [
                ['✅ Updated', $updated, 'Staff successfully assigned'],
                ['⚠️ No Match', $noMatch, 'Host found but no matching staff'],
                ['📭 No Host Data', $noHostData, 'No Cal.com host data in metadata'],
                ['❌ Failed', $failed, 'Errors during processing'],
                ['📊 Total', $appointments->count(), 'Total appointments processed'],
            ]
        );

        $this->newLine();

        if ($this->option('dry-run')) {
            $this->info('🔶 DRY RUN COMPLETE - No changes were made');
            $this->info('💡 Run without --dry-run to apply changes');
        } else {
            $this->info('✅ Backfill complete!');
            if ($updated > 0) {
                $this->info("📝 Updated {$updated} appointments with staff assignments");
            }
        }

        return self::SUCCESS;
    }
}
