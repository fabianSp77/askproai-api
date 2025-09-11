<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CalcomV2Service;
use App\Models\Appointment;
use App\Models\CalcomEventType;
use App\Models\CalcomBooking;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncCalcomHistoricalData extends Command
{
    protected $signature = 'calcom:sync-historical 
                           {--type= : Specific type to sync (bookings|event-types|schedules|users|teams|all)}
                           {--from= : Start date for bookings (YYYY-MM-DD)}
                           {--to= : End date for bookings (YYYY-MM-DD)}
                           {--company= : Company ID to sync for}
                           {--dry-run : Run without saving to database}';

    protected $description = 'Sync all historical data from Cal.com V2 API and map to local entities';

    protected CalcomV2Service $calcomService;
    protected array $syncLog = [];
    protected bool $dryRun = false;
    protected ?int $syncLogId = null;

    public function __construct(CalcomV2Service $calcomService)
    {
        parent::__construct();
        $this->calcomService = $calcomService;
    }

    public function handle(): int
    {
        $this->dryRun = $this->option('dry-run');
        $type = $this->option('type') ?? 'all';
        
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Cal.com V2 Historical Data Sync');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('');
        $this->info('🔧 Configuration:');
        $this->info('  • API Version: 2024-08-13');
        $this->info('  • Base URL: ' . config('services.calcom.v2_base_url'));
        $this->info('  • Sync Type: ' . ucfirst($type));
        $this->info('  • Mode: ' . ($this->dryRun ? 'DRY RUN (no changes)' : 'LIVE'));
        $this->info('');

        // Create sync log entry
        if (!$this->dryRun) {
            $this->syncLogId = DB::table('calcom_sync_logs')->insertGetId([
                'sync_type' => $type,
                'status' => 'started',
                'started_at' => now(),
                'metadata' => json_encode([
                    'options' => $this->options(),
                    'api_version' => config('services.calcom.v2_api_version')
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        try {
            switch ($type) {
                case 'bookings':
                    $this->syncBookings();
                    break;
                case 'event-types':
                    $this->syncEventTypes();
                    break;
                case 'schedules':
                    $this->syncSchedules();
                    break;
                case 'users':
                    $this->syncUsers();
                    break;
                case 'teams':
                    $this->syncTeams();
                    break;
                case 'all':
                    $this->syncAll();
                    break;
                default:
                    $this->error("Unknown sync type: {$type}");
                    return self::FAILURE;
            }

            // Update sync log as completed
            if (!$this->dryRun && $this->syncLogId) {
                DB::table('calcom_sync_logs')
                    ->where('id', $this->syncLogId)
                    ->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'records_processed' => $this->syncLog['processed'] ?? 0,
                        'records_created' => $this->syncLog['created'] ?? 0,
                        'records_updated' => $this->syncLog['updated'] ?? 0,
                        'records_failed' => $this->syncLog['failed'] ?? 0,
                        'updated_at' => now()
                    ]);
            }

            $this->displaySummary();
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Sync failed: ' . $e->getMessage());
            Log::error('[SyncCalcomHistoricalData] Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update sync log as failed
            if (!$this->dryRun && $this->syncLogId) {
                DB::table('calcom_sync_logs')
                    ->where('id', $this->syncLogId)
                    ->update([
                        'status' => 'failed',
                        'completed_at' => now(),
                        'errors' => json_encode([
                            'message' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]),
                        'updated_at' => now()
                    ]);
            }

            return self::FAILURE;
        }
    }

    protected function syncAll(): void
    {
        $this->info('📋 Starting complete synchronization...');
        $this->newLine();

        // Order matters: Teams & Users first, then dependent data
        $this->syncTeams();
        $this->syncUsers();
        $this->syncEventTypes();
        $this->syncSchedules();
        $this->syncBookings();
    }

    protected function syncTeams(): void
    {
        $this->info('👥 Syncing Teams...');
        
        try {
            $teams = $this->calcomService->getTeams();
            $this->info("  Found {count($teams)} teams");

            $bar = $this->output->createProgressBar(count($teams));
            $bar->start();

            foreach ($teams as $team) {
                $this->processTeam($team);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

        } catch (\Exception $e) {
            $this->error("  Failed to sync teams: " . $e->getMessage());
            $this->syncLog['failed'] = ($this->syncLog['failed'] ?? 0) + 1;
        }
    }

    protected function syncUsers(): void
    {
        $this->info('👤 Syncing Users...');
        
        try {
            $users = $this->calcomService->getUsers();
            $this->info("  Found " . count($users) . " users");

            $bar = $this->output->createProgressBar(count($users));
            $bar->start();

            foreach ($users as $user) {
                $this->processUser($user);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

        } catch (\Exception $e) {
            $this->error("  Failed to sync users: " . $e->getMessage());
            $this->syncLog['failed'] = ($this->syncLog['failed'] ?? 0) + 1;
        }
    }

    protected function syncEventTypes(): void
    {
        $this->info('📅 Syncing Event Types...');
        
        try {
            $eventTypes = $this->calcomService->getEventTypes();
            $this->info("  Found " . count($eventTypes) . " event types");

            $bar = $this->output->createProgressBar(count($eventTypes));
            $bar->start();

            foreach ($eventTypes as $eventType) {
                $this->processEventType($eventType);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

        } catch (\Exception $e) {
            $this->error("  Failed to sync event types: " . $e->getMessage());
            $this->syncLog['failed'] = ($this->syncLog['failed'] ?? 0) + 1;
        }
    }

    protected function syncSchedules(): void
    {
        $this->info('🕐 Syncing Schedules...');
        
        try {
            $schedules = $this->calcomService->getSchedules();
            $this->info("  Found " . count($schedules) . " schedules");

            $bar = $this->output->createProgressBar(count($schedules));
            $bar->start();

            foreach ($schedules as $schedule) {
                $this->processSchedule($schedule);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

        } catch (\Exception $e) {
            $this->error("  Failed to sync schedules: " . $e->getMessage());
            $this->syncLog['failed'] = ($this->syncLog['failed'] ?? 0) + 1;
        }
    }

    protected function syncBookings(): void
    {
        $this->info('📚 Syncing Bookings...');
        
        $filters = [];
        
        // Add date filters if provided
        if ($from = $this->option('from')) {
            $filters['from'] = $from;
        }
        if ($to = $this->option('to')) {
            $filters['to'] = $to;
        }

        try {
            // Use pagination-aware method
            $allBookings = $this->calcomService->getAllBookings($filters);
            $this->info("  Found " . count($allBookings) . " bookings");

            if (count($allBookings) > 0) {
                $bar = $this->output->createProgressBar(count($allBookings));
                $bar->start();

                $chunks = array_chunk($allBookings, 50); // Process in chunks
                
                foreach ($chunks as $chunk) {
                    foreach ($chunk as $booking) {
                        $this->processBooking($booking);
                        $bar->advance();
                    }
                    
                    // Small delay between chunks to avoid rate limiting
                    usleep(100000); // 100ms
                }

                $bar->finish();
            }
            
            $this->newLine(2);

        } catch (\Exception $e) {
            $this->error("  Failed to sync bookings: " . $e->getMessage());
            $this->syncLog['failed'] = ($this->syncLog['failed'] ?? 0) + 1;
        }
    }

    protected function processTeam(array $team): void
    {
        $this->syncLog['processed'] = ($this->syncLog['processed'] ?? 0) + 1;

        if ($this->dryRun) {
            $this->syncLog['created'] = ($this->syncLog['created'] ?? 0) + 1;
            return;
        }

        try {
            // Find or create company mapping
            $company = $this->findOrCreateCompanyForTeam($team);
            
            DB::table('calcom_teams')->updateOrInsert(
                ['calcom_team_id' => $team['id']],
                [
                    'company_id' => $company->id ?? null,
                    'name' => $team['name'] ?? 'Unknown Team',
                    'slug' => $team['slug'] ?? null,
                    'bio' => $team['bio'] ?? null,
                    'logo_url' => $team['logoUrl'] ?? null,
                    'hide_branding' => $team['hideBranding'] ?? false,
                    'parent_team_id' => $team['parentId'] ?? null,
                    'metadata' => json_encode($team['metadata'] ?? []),
                    'theme' => json_encode($team['theme'] ?? []),
                    'last_synced_at' => now(),
                    'updated_at' => now()
                ]
            );

            $this->syncLog['created'] = ($this->syncLog['created'] ?? 0) + 1;

        } catch (\Exception $e) {
            Log::error('[SyncCalcomHistoricalData] Failed to process team', [
                'team_id' => $team['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            $this->syncLog['failed'] = ($this->syncLog['failed'] ?? 0) + 1;
        }
    }

    protected function processUser(array $user): void
    {
        $this->syncLog['processed'] = ($this->syncLog['processed'] ?? 0) + 1;

        if ($this->dryRun) {
            $this->syncLog['created'] = ($this->syncLog['created'] ?? 0) + 1;
            return;
        }

        try {
            // Try to find matching staff member by email
            $staff = Staff::where('email', $user['email'] ?? '')
                ->orWhere('name', $user['name'] ?? '')
                ->first();

            // Get default company
            $company = Company::first();
            
            DB::table('calcom_users')->updateOrInsert(
                ['calcom_user_id' => $user['id']],
                [
                    'staff_id' => $staff->id ?? null,
                    'company_id' => $company->id ?? null,
                    'email' => $user['email'] ?? '',
                    'username' => $user['username'] ?? null,
                    'name' => $user['name'] ?? 'Unknown User',
                    'bio' => $user['bio'] ?? null,
                    'avatar_url' => $user['avatarUrl'] ?? null,
                    'timezone' => $user['timeZone'] ?? 'Europe/Berlin',
                    'locale' => $user['locale'] ?? 'de',
                    'default_schedule_id' => $user['defaultScheduleId'] ?? null,
                    'is_away' => $user['away'] ?? false,
                    'metadata' => json_encode($user['metadata'] ?? []),
                    'last_synced_at' => now(),
                    'updated_at' => now()
                ]
            );

            $this->syncLog['created'] = ($this->syncLog['created'] ?? 0) + 1;

        } catch (\Exception $e) {
            Log::error('[SyncCalcomHistoricalData] Failed to process user', [
                'user_id' => $user['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            $this->syncLog['failed'] = ($this->syncLog['failed'] ?? 0) + 1;
        }
    }

    protected function processEventType(array $eventType): void
    {
        $this->syncLog['processed'] = ($this->syncLog['processed'] ?? 0) + 1;

        if ($this->dryRun) {
            $this->syncLog['updated'] = ($this->syncLog['updated'] ?? 0) + 1;
            return;
        }

        try {
            $existing = CalcomEventType::where('calcom_event_type_id', $eventType['id'])->first();
            
            // Get default company and branch if not existing
            if (!$existing) {
                $company = Company::first();
                $branch = Branch::first();
            }

            $data = [
                'calcom_numeric_event_type_id' => $eventType['id'],
                'name' => $eventType['title'] ?? $eventType['slug'] ?? 'Unknown',
                'slug' => $eventType['slug'] ?? null,
                'description' => $eventType['description'] ?? null,
                'duration_minutes' => $eventType['length'] ?? 30,
                'price' => $eventType['price'] ?? 0,
                'is_active' => !($eventType['hidden'] ?? false),
                'requires_confirmation' => $eventType['requiresConfirmation'] ?? false,
                'minimum_booking_notice' => $eventType['minimumBookingNotice'] ?? 0,
                'booking_future_limit' => $eventType['periodDays'] ?? null,
                'time_slot_interval' => $eventType['slotInterval'] ?? null,
                'buffer_before' => $eventType['beforeEventBuffer'] ?? 0,
                'buffer_after' => $eventType['afterEventBuffer'] ?? 0,
                'locations' => json_encode($eventType['locations'] ?? []),
                'custom_fields' => json_encode($eventType['customInputs'] ?? []),
                'max_bookings_per_day' => $eventType['periodCountCalendarDays'] ?? null,
                'seats_per_time_slot' => $eventType['seatsPerTimeSlot'] ?? null,
                'schedule_id' => $eventType['scheduleId'] ?? null,
                'metadata' => json_encode($eventType['metadata'] ?? []),
                'last_synced_at' => now(),
            ];

            // Add company/branch only for new records
            if (!$existing) {
                $data['company_id'] = $company->id ?? 1;
                $data['branch_id'] = $branch->id ?? null;
            }

            // Add V2 specific fields
            if (isset($eventType['userId'])) {
                $data['calcom_user_id'] = $eventType['userId'];
            }
            if (isset($eventType['teamId'])) {
                $data['calcom_team_id'] = $eventType['teamId'];
                $data['team_id'] = $eventType['teamId'];
                $data['is_team_event'] = true;
            }
            if (isset($eventType['hosts'])) {
                $data['hosts'] = json_encode($eventType['hosts']);
            }

            CalcomEventType::updateOrCreate(
                ['calcom_event_type_id' => (string)$eventType['id']],
                $data
            );

            $this->syncLog['updated'] = ($this->syncLog['updated'] ?? 0) + 1;

        } catch (\Exception $e) {
            Log::error('[SyncCalcomHistoricalData] Failed to process event type', [
                'event_type_id' => $eventType['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            $this->syncLog['failed'] = ($this->syncLog['failed'] ?? 0) + 1;
        }
    }

    protected function processSchedule(array $schedule): void
    {
        $this->syncLog['processed'] = ($this->syncLog['processed'] ?? 0) + 1;

        if ($this->dryRun) {
            $this->syncLog['created'] = ($this->syncLog['created'] ?? 0) + 1;
            return;
        }

        try {
            // Find staff member if user ID is provided
            $staff = null;
            if (isset($schedule['userId'])) {
                $calcomUser = DB::table('calcom_users')
                    ->where('calcom_user_id', $schedule['userId'])
                    ->first();
                    
                if ($calcomUser && $calcomUser->staff_id) {
                    $staff = Staff::find($calcomUser->staff_id);
                }
            }

            DB::table('calcom_schedules')->updateOrInsert(
                ['calcom_schedule_id' => $schedule['id']],
                [
                    'calcom_user_id' => $schedule['userId'] ?? null,
                    'staff_id' => $staff->id ?? null,
                    'name' => $schedule['name'] ?? 'Default Schedule',
                    'timezone' => $schedule['timeZone'] ?? 'Europe/Berlin',
                    'is_default' => $schedule['isDefault'] ?? false,
                    'availability' => json_encode($schedule['availability'] ?? []),
                    'working_hours' => json_encode($schedule['workingHours'] ?? []),
                    'date_overrides' => json_encode($schedule['dateOverrides'] ?? []),
                    'last_synced_at' => now(),
                    'updated_at' => now()
                ]
            );

            $this->syncLog['created'] = ($this->syncLog['created'] ?? 0) + 1;

        } catch (\Exception $e) {
            Log::error('[SyncCalcomHistoricalData] Failed to process schedule', [
                'schedule_id' => $schedule['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            $this->syncLog['failed'] = ($this->syncLog['failed'] ?? 0) + 1;
        }
    }

    protected function processBooking(array $booking): void
    {
        $this->syncLog['processed'] = ($this->syncLog['processed'] ?? 0) + 1;

        if ($this->dryRun) {
            $this->syncLog['created'] = ($this->syncLog['created'] ?? 0) + 1;
            return;
        }

        try {
            // Find or create customer based on attendee info
            $customer = $this->findOrCreateCustomer($booking);
            
            // Find staff member if user is mapped
            $staff = null;
            if (isset($booking['userId'])) {
                $calcomUser = DB::table('calcom_users')
                    ->where('calcom_user_id', $booking['userId'])
                    ->first();
                    
                if ($calcomUser && $calcomUser->staff_id) {
                    $staff = Staff::find($calcomUser->staff_id);
                }
            }

            // Find service based on event type
            $service = null;
            $eventType = null;
            if (isset($booking['eventTypeId'])) {
                $eventType = CalcomEventType::where('calcom_numeric_event_type_id', $booking['eventTypeId'])
                    ->orWhere('calcom_event_type_id', $booking['eventTypeId'])
                    ->first();
                    
                if ($eventType && $eventType->service_id) {
                    $service = Service::find($eventType->service_id);
                }
            }

            // Determine branch
            $branch = $staff?->home_branch_id ? Branch::find($staff->home_branch_id) : Branch::first();

            // Parse dates
            $startsAt = Carbon::parse($booking['startTime'] ?? $booking['start']);
            $endsAt = Carbon::parse($booking['endTime'] ?? $booking['end']);

            // Create or update appointment
            $appointment = Appointment::updateOrCreate(
                [
                    'calcom_v2_booking_id' => $booking['id'],
                ],
                [
                    'customer_id' => $customer->id,
                    'staff_id' => $staff->id ?? null,
                    'service_id' => $service->id ?? null,
                    'branch_id' => $branch->id ?? null,
                    'company_id' => $branch->company_id ?? Company::first()->id,
                    'calcom_booking_uid' => $booking['uid'] ?? null,
                    'calcom_user_id' => $booking['userId'] ?? null,
                    'calcom_team_id' => $booking['teamId'] ?? null,
                    'calcom_event_type_id' => $booking['eventTypeId'] ?? null,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => $this->mapBookingStatus($booking['status'] ?? 'ACCEPTED'),
                    'notes' => $booking['description'] ?? null,
                    'meeting_url' => $booking['meetingUrl'] ?? null,
                    'location_type' => $booking['location']['type'] ?? null,
                    'location_value' => $booking['location']['value'] ?? $booking['location'] ?? null,
                    'attendees' => json_encode($booking['attendees'] ?? []),
                    'responses' => json_encode($booking['responses'] ?? []),
                    'rescheduled_from_uid' => $booking['rescheduledFromUid'] ?? null,
                    'cancellation_reason' => $booking['cancellationReason'] ?? null,
                    'rejected_reason' => $booking['rejectionReason'] ?? null,
                    'is_recurring' => $booking['recurringEventId'] ? true : false,
                    'recurring_event_id' => $booking['recurringEventId'] ?? null,
                    'price' => $booking['payment']['amount'] ?? null,
                    'source' => 'cal.com',
                    'booking_metadata' => json_encode($booking['metadata'] ?? []),
                    'payload' => json_encode($booking),
                ]
            );

            // Also create CalcomBooking record for compatibility
            CalcomBooking::updateOrCreate(
                ['calcom_uid' => $booking['uid'] ?? $booking['id']],
                [
                    'appointment_id' => $appointment->id,
                    'status' => $booking['status'] ?? 'ACCEPTED',
                    'raw_payload' => $booking,
                ]
            );

            $this->syncLog['created'] = ($this->syncLog['created'] ?? 0) + 1;

        } catch (\Exception $e) {
            Log::error('[SyncCalcomHistoricalData] Failed to process booking', [
                'booking_id' => $booking['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            $this->syncLog['failed'] = ($this->syncLog['failed'] ?? 0) + 1;
        }
    }

    protected function findOrCreateCustomer(array $booking): Customer
    {
        // Get first attendee (main customer)
        $attendee = $booking['attendees'][0] ?? $booking['user'] ?? [];
        
        if (empty($attendee['email'])) {
            // Fallback to responses
            $attendee = [
                'email' => $booking['responses']['email'] ?? 'unknown@example.com',
                'name' => $booking['responses']['name'] ?? 'Unknown Customer',
            ];
        }

        return Customer::firstOrCreate(
            ['email' => $attendee['email']],
            [
                'name' => $attendee['name'] ?? 'Unknown',
                'phone' => $attendee['phoneNumber'] ?? $booking['responses']['phone'] ?? null,
                'locale' => $attendee['locale'] ?? 'de',
                'timezone' => $attendee['timeZone'] ?? 'Europe/Berlin',
                'notes' => 'Imported from Cal.com',
            ]
        );
    }

    protected function findOrCreateCompanyForTeam(array $team): ?Company
    {
        // Try to find existing company or use first one
        $company = Company::where('name', 'LIKE', '%' . $team['name'] . '%')->first();
        
        if (!$company) {
            $company = Company::first();
        }

        return $company;
    }

    protected function mapBookingStatus(string $calcomStatus): string
    {
        return match($calcomStatus) {
            'ACCEPTED' => 'confirmed',
            'PENDING' => 'pending',
            'CANCELLED' => 'cancelled',
            'REJECTED' => 'cancelled',
            default => 'pending'
        };
    }

    protected function displaySummary(): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Synchronization Summary');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('');
        
        if ($this->dryRun) {
            $this->warn('  🔸 DRY RUN MODE - No changes were made');
        }
        
        $this->info('  📊 Statistics:');
        $this->info('     • Processed: ' . ($this->syncLog['processed'] ?? 0));
        $this->info('     • Created:   ' . ($this->syncLog['created'] ?? 0));
        $this->info('     • Updated:   ' . ($this->syncLog['updated'] ?? 0));
        $this->info('     • Failed:    ' . ($this->syncLog['failed'] ?? 0));
        $this->info('');
        
        if (($this->syncLog['failed'] ?? 0) > 0) {
            $this->warn('  ⚠️  Some records failed to sync. Check logs for details.');
        } else {
            $this->info('  ✅ All records synced successfully!');
        }
        
        $this->info('═══════════════════════════════════════════════════════════════');
    }
}