<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Models\Company;
use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateCalcomToV2 extends Command
{
    protected $signature = 'calcom:migrate-to-v2 
                            {--company= : Specific company ID to migrate}
                            {--dry-run : Run without making changes}
                            {--limit=100 : Number of records to process}';

    protected $description = 'Migrate Cal.com booking IDs from V1 to V2 format';

    private CalcomV2Service $calcomV2Service;

    public function handle()
    {
        $this->calcomV2Service = app(CalcomV2Service::class);
        
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $companyId = $this->option('company');

        $this->info('Cal.com V1 to V2 Migration');
        $this->info('==========================');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Get appointments that need migration
        $query = Appointment::query()
            ->whereNotNull('calcom_booking_id')
            ->whereNull('calcom_v2_booking_id');

        if ($companyId) {
            $query->whereHas('branch', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        $appointments = $query->limit($limit)->get();
        
        $this->info("Found {$appointments->count()} appointments to migrate");

        $migrated = 0;
        $failed = 0;

        $this->withProgressBar($appointments, function ($appointment) use ($dryRun, &$migrated, &$failed) {
            try {
                // Try to fetch the booking from Cal.com V2 API
                $v1BookingId = $appointment->calcom_booking_id;
                
                if ($dryRun) {
                    $this->line("\nWould migrate appointment {$appointment->id} with Cal.com booking {$v1BookingId}");
                    $migrated++;
                    return;
                }

                // For V2, we need to find the booking by UID or other identifier
                // This is a simplified version - actual implementation may need different logic
                $v2BookingUid = $this->findV2BookingUid($appointment);
                
                if ($v2BookingUid) {
                    $appointment->update([
                        'calcom_v2_booking_id' => $v2BookingUid,
                        'calcom_v2_metadata' => [
                            'migrated_from_v1' => true,
                            'v1_booking_id' => $v1BookingId,
                            'migrated_at' => now()->toIso8601String(),
                        ]
                    ]);
                    
                    $migrated++;
                    
                    Log::info('Migrated Cal.com booking', [
                        'appointment_id' => $appointment->id,
                        'v1_booking_id' => $v1BookingId,
                        'v2_booking_uid' => $v2BookingUid,
                    ]);
                } else {
                    $failed++;
                    Log::warning('Could not find V2 booking for appointment', [
                        'appointment_id' => $appointment->id,
                        'v1_booking_id' => $v1BookingId,
                    ]);
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error('Failed to migrate appointment', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        $this->newLine(2);
        $this->info('Migration Summary');
        $this->info('=================');
        $this->info("Migrated: {$migrated}");
        $this->error("Failed: {$failed}");

        // Update company settings if all appointments migrated
        if (!$dryRun && $companyId && $failed === 0) {
            $this->updateCompanySettings($companyId);
        }

        return $failed === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    private function findV2BookingUid(Appointment $appointment): ?string
    {
        // Try different strategies to find the V2 booking
        
        // Strategy 1: Check if V1 ID is actually a UID that works in V2
        try {
            $booking = $this->calcomV2Service->getBooking($appointment->calcom_booking_id);
            if ($booking && isset($booking['uid'])) {
                return $booking['uid'];
            }
        } catch (\Exception $e) {
            // Continue to next strategy
        }

        // Strategy 2: Search by appointment time and attendee
        if ($appointment->customer && $appointment->customer->email) {
            try {
                $bookings = $this->calcomV2Service->searchBookings([
                    'email' => $appointment->customer->email,
                    'startTime' => $appointment->starts_at->toIso8601String(),
                ]);
                
                if (!empty($bookings)) {
                    return $bookings[0]['uid'] ?? null;
                }
            } catch (\Exception $e) {
                // Continue
            }
        }

        // Strategy 3: If we have external metadata, try to use that
        if ($appointment->metadata && isset($appointment->metadata['calcom_uid'])) {
            return $appointment->metadata['calcom_uid'];
        }

        return null;
    }

    private function updateCompanySettings(int $companyId): void
    {
        $company = Company::find($companyId);
        if ($company) {
            $settings = $company->settings ?? [];
            $settings['calcom_v2_migration_completed'] = true;
            $settings['calcom_v2_migration_date'] = now()->toIso8601String();
            $company->settings = $settings;
            $company->save();
            
            $this->info("Updated company {$company->name} settings to mark V2 migration as complete");
        }
    }
}