<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Services\Retell\AppointmentCreationService;
use App\Services\CalcomV2Client;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncAppointmentsFromCalcom extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calcom:sync-appointments
                            {--dry-run : Show what would be synced without actually syncing}
                            {--limit=50 : Maximum number of calls to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync appointments from Cal.com for calls with booking_id but no appointment record';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Syncing Appointments from Cal.com');
        $this->info(str_repeat('=', 60));

        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
        }

        $this->info("🔢 Processing up to {$limit} calls");
        $this->newLine();

        try {
            // Find calls with booking_id but no appointment
            // Use leftJoin since there's no direct relationship defined
            $orphanedCalls = Call::query()
                ->leftJoin('appointments', 'calls.id', '=', 'appointments.call_id')
                ->whereNotNull('calls.booking_id')
                ->whereNotNull('calls.booking_confirmed')
                ->where('calls.booking_confirmed', true)
                ->whereNull('appointments.id')
                ->select('calls.*')
                ->orderBy('calls.created_at', 'desc')
                ->limit($limit)
                ->get();

            if ($orphanedCalls->isEmpty()) {
                $this->info('✅ No orphaned bookings found - all calls have appointments!');
                return Command::SUCCESS;
            }

            $this->info("📞 Found {$orphanedCalls->count()} calls with bookings but no appointments");
            $this->newLine();

            // Show what will be synced
            $this->table(
                ['Call ID', 'Booking ID', 'Call Date', 'Customer', 'Company'],
                $orphanedCalls->map(function($call) {
                    return [
                        $call->id,
                        substr($call->booking_id, 0, 20) . '...',
                        $call->created_at ? $call->created_at->format('Y-m-d H:i') : 'N/A',
                        $call->customer ? $call->customer->name : 'Unknown',
                        $call->company_id ?? 'N/A'
                    ];
                })->toArray()
            );

            if ($dryRun) {
                $this->newLine();
                $this->info('🏁 DRY RUN COMPLETE - Run without --dry-run to actually sync appointments');
                return Command::SUCCESS;
            }

            // Proceed with actual sync
            $this->newLine();
            $this->info('🚀 Starting appointment sync from Cal.com...');

            $calcomClient = app(CalcomV2Client::class);
            $appointmentService = app(AppointmentCreationService::class);

            $stats = [
                'total' => $orphanedCalls->count(),
                'synced' => 0,
                'failed' => 0,
                'skipped' => 0,
            ];

            $progressBar = $this->output->createProgressBar($stats['total']);
            $progressBar->start();

            foreach ($orphanedCalls as $call) {
                try {
                    // Use booking data from call's booking_details (more reliable than API)
                    $bookingDetails = is_string($call->booking_details)
                        ? json_decode($call->booking_details, true)
                        : $call->booking_details;

                    $bookingData = $bookingDetails['calcom_booking'] ?? null;

                    if (!$bookingData) {
                        // Fallback: Try to fetch from Cal.com API
                        $response = $calcomClient->getBookings(['uid' => $call->booking_id]);

                        if (!$response->successful()) {
                            $stats['skipped']++;
                            Log::warning('⚠️  No booking data available', [
                                'call_id' => $call->id,
                                'booking_id' => $call->booking_id,
                            ]);
                            $progressBar->advance();
                            continue;
                        }

                        $bookingsData = $response->json();
                        $bookingData = $bookingsData['data'][0] ?? null;

                        if (!$bookingData) {
                            $stats['skipped']++;
                            Log::warning('⚠️  Booking not found', [
                                'call_id' => $call->id,
                                'booking_id' => $call->booking_id
                            ]);
                            $progressBar->advance();
                            continue;
                        }
                    }

                    // Extract service from booking
                    $eventTypeId = $bookingData['eventTypeId'] ?? null;
                    $service = null;

                    if ($eventTypeId) {
                        $service = Service::where('calcom_event_type_id', $eventTypeId)->first();
                    }

                    // Try to match service from call metadata if not found
                    $companyId = $call->company_id ?? $call->customer?->company_id;

                    if (!$service && $companyId) {
                        // Fallback to first service of company
                        $service = Service::where('company_id', $companyId)->first();
                    }

                    if (!$service) {
                        $stats['skipped']++;
                        Log::warning('⚠️  Service not found for booking', [
                            'call_id' => $call->id,
                            'booking_id' => $call->booking_id,
                            'event_type_id' => $eventTypeId
                        ]);
                        $progressBar->advance();
                        continue;
                    }

                    // Ensure customer exists
                    $customer = $call->customer;

                    if (!$customer) {
                        // Try to create customer from booking data
                        $attendees = $bookingData['attendees'] ?? [];
                        $attendee = $attendees[0] ?? null;

                        if ($attendee) {
                            $customer = Customer::create([
                                'name' => $attendee['name'] ?? 'Unknown',
                                'email' => $attendee['email'] ?? null,
                                'phone' => $call->from_number,
                                'company_id' => $call->company_id,
                                'branch_id' => $call->branch_id,
                                'source' => 'calcom_sync',
                                'status' => 'active'
                            ]);

                            // Link to call
                            $call->customer_id = $customer->id;
                            $call->save();
                        } else {
                            $stats['skipped']++;
                            Log::warning('⚠️  Cannot create customer - no attendee data', [
                                'call_id' => $call->id,
                                'booking_id' => $call->booking_id
                            ]);
                            $progressBar->advance();
                            continue;
                        }
                    }

                    // Parse booking times (check both field name variations)
                    $startsAt = isset($bookingData['startTime'])
                        ? Carbon::parse($bookingData['startTime'])
                        : (isset($bookingData['start']) ? Carbon::parse($bookingData['start']) : null);
                    $endsAt = isset($bookingData['endTime'])
                        ? Carbon::parse($bookingData['endTime'])
                        : (isset($bookingData['end']) ? Carbon::parse($bookingData['end']) : null);

                    if (!$startsAt) {
                        $stats['skipped']++;
                        Log::warning('⚠️  No start time in booking data', [
                            'call_id' => $call->id,
                            'booking_id' => $call->booking_id
                        ]);
                        $progressBar->advance();
                        continue;
                    }

                    // Calculate end time if not provided
                    if (!$endsAt) {
                        $duration = $service->duration ?? 60;
                        $endsAt = $startsAt->copy()->addMinutes($duration);
                    }

                    // Create appointment record
                    $appointment = $appointmentService->createLocalRecord(
                        customer: $customer,
                        service: $service,
                        bookingDetails: [
                            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                            'ends_at' => $endsAt->format('Y-m-d H:i:s'),
                            'service' => $service->name,
                            'customer_name' => $customer->name,
                            'date' => $startsAt->format('d.m.Y'),
                            'time' => $startsAt->format('H:i'),
                            'duration_minutes' => $startsAt->diffInMinutes($endsAt)
                        ],
                        calcomBookingId: $call->booking_id,
                        call: $call
                    );

                    $stats['synced']++;

                    Log::info('✅ Appointment synced from Cal.com', [
                        'appointment_id' => $appointment->id,
                        'call_id' => $call->id,
                        'booking_id' => $call->booking_id,
                        'customer' => $customer->name,
                        'service' => $service->name,
                        'starts_at' => $startsAt->format('Y-m-d H:i')
                    ]);

                    // Small delay to avoid API rate limiting
                    usleep(200000); // 200ms

                } catch (\Exception $e) {
                    $stats['failed']++;
                    Log::error('❌ Failed to sync appointment', [
                        'call_id' => $call->id,
                        'booking_id' => $call->booking_id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
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
                    ['Total Calls Processed', $stats['total']],
                    ['Successfully Synced', $stats['synced']],
                    ['Skipped (No Data)', $stats['skipped']],
                    ['Failed to Sync', $stats['failed']],
                ]
            );

            if ($stats['failed'] > 0 || $stats['skipped'] > 0) {
                $this->warn('⚠️  Some calls were not synced. Check logs for details.');
            }

            // Verification
            $this->verifyAppointmentCompleteness();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Command failed: ' . $e->getMessage());
            Log::error('SyncAppointmentsFromCalcom command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Verify appointment completeness after sync
     */
    private function verifyAppointmentCompleteness()
    {
        $this->newLine();
        $this->info('📊 Appointment Completeness Verification:');

        $totalConfirmedBookings = Call::whereNotNull('booking_id')
            ->where('booking_confirmed', true)
            ->count();

        $withAppointments = Call::query()
            ->join('appointments', 'calls.id', '=', 'appointments.call_id')
            ->whereNotNull('calls.booking_id')
            ->where('calls.booking_confirmed', true)
            ->count();

        $withoutAppointments = $totalConfirmedBookings - $withAppointments;

        $coverage = $totalConfirmedBookings > 0
            ? round(($withAppointments / $totalConfirmedBookings) * 100, 1)
            : 0;

        $this->table(
            ['Metric', 'Count', 'Coverage'],
            [
                ['Total Confirmed Bookings', $totalConfirmedBookings, '100%'],
                ['With Appointments', $withAppointments, $coverage . '%'],
                ['Missing Appointments', $withoutAppointments, '-'],
            ]
        );

        if ($coverage < 100) {
            $this->warn("⚠️  Appointment coverage is {$coverage}%. Run sync again or check logs.");
        } else {
            $this->info('✅ All confirmed bookings have appointments!');
        }
    }
}
