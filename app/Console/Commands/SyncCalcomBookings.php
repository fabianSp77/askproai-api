<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\CalcomWebhookController;
use Carbon\Carbon;

class SyncCalcomBookings extends Command
{
    protected $signature = 'calcom:sync-bookings
                            {--days=30 : Number of days to sync backwards}
                            {--future=30 : Number of days to sync forward}
                            {--dry-run : Show what would be synced without making changes}';

    protected $description = 'Sync bookings from Cal.com API to local database';

    private CalcomWebhookController $webhookController;
    private int $totalBookings = 0;
    private int $newBookings = 0;
    private int $updatedBookings = 0;
    private int $errors = 0;

    public function __construct()
    {
        parent::__construct();
        $this->webhookController = app(CalcomWebhookController::class);
    }

    public function handle()
    {
        $this->info('🔄 Cal.com Booking Synchronization');
        $this->info(str_repeat('=', 60));
        $this->info('Timestamp: ' . now()->format('Y-m-d H:i:s'));
        $this->info(str_repeat('-', 60));

        $days = $this->option('days');
        $futureDays = $this->option('future');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Fetch bookings from Cal.com API (v1)
        $bookings = $this->fetchBookings($days, $futureDays);

        if (empty($bookings)) {
            $this->warn('No bookings found or API error occurred');
            return 1;
        }

        $this->info("Found {$this->totalBookings} bookings to process");
        $this->info('');

        // Process each booking
        $this->processBookings($bookings, $dryRun);

        // Show summary
        $this->showSummary();

        return 0;
    }

    private function fetchBookings(int $pastDays, int $futureDays): array
    {
        $this->info('Fetching bookings from Cal.com...');

        $apiKey = config('services.calcom.api_key');
        $baseUrl = 'https://api.cal.com/v1'; // Use v1 API which works with API key

        if (!$apiKey) {
            $this->error('Cal.com API key not configured');
            return [];
        }

        $from = now()->subDays($pastDays)->startOfDay()->toIso8601String();
        $to = now()->addDays($futureDays)->endOfDay()->toIso8601String();

        $this->info("Date range: $from to $to");

        try {
            $response = Http::get($baseUrl . '/bookings', [
                'apiKey' => $apiKey,
                'from' => $from,
                'to' => $to,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $bookings = $data['bookings'] ?? [];
                $this->totalBookings = count($bookings);
                return $bookings;
            } else {
                $this->error('API request failed: ' . $response->status());
                Log::error('Cal.com API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

        } catch (\Exception $e) {
            $this->error('Error fetching bookings: ' . $e->getMessage());
            Log::error('Cal.com fetch error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function processBookings(array $bookings, bool $dryRun): void
    {
        $progressBar = $this->output->createProgressBar($this->totalBookings);
        $progressBar->start();

        foreach ($bookings as $booking) {
            try {
                if ($dryRun) {
                    $this->analyzeBooking($booking);
                } else {
                    $this->syncBooking($booking);
                }
            } catch (\Exception $e) {
                $this->errors++;
                Log::error('Error processing Cal.com booking', [
                    'booking_id' => $booking['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->info(''); // New line after progress bar
    }

    private function analyzeBooking(array $booking): void
    {
        // In dry-run mode, just analyze what would happen
        $calcomId = $booking['id'] ?? $booking['uid'] ?? null;
        $startTime = Carbon::parse($booking['startTime']);
        $attendee = $booking['attendees'][0] ?? [];
        $customerName = $attendee['name'] ?? $booking['name'] ?? 'Unknown';

        $exists = \App\Models\Appointment::where('calcom_v2_booking_id', $calcomId)
            ->exists();

        if ($exists) {
            $this->updatedBookings++;
        } else {
            $this->newBookings++;
        }

        $this->info("  Would process: $customerName at " . $startTime->format('Y-m-d H:i'));
    }

    private function syncBooking(array $booking): void
    {
        // Check if appointment already exists
        $calcomId = $booking['id'] ?? $booking['uid'] ?? null;

        $exists = \App\Models\Appointment::where('calcom_v2_booking_id', $calcomId)
            ->exists();

        // Transform booking data to match webhook format
        $webhookPayload = $this->transformToWebhookFormat($booking);

        try {
            // Use the webhook controller's handler to process the booking
            // This ensures consistent processing logic
            $controller = new CalcomWebhookController();

            // Use reflection to call the protected method
            $method = new \ReflectionMethod($controller, 'handleBookingCreated');
            $method->setAccessible(true);
            $method->invoke($controller, $webhookPayload);

            // Check if it was actually created
            $wasCreated = \App\Models\Appointment::where('calcom_v2_booking_id', $calcomId)->exists();

            if (!$exists && $wasCreated) {
                $this->newBookings++;
            } elseif ($exists) {
                $this->updatedBookings++;
            } else {
                // Failed to create
                Log::warning('Failed to create Cal.com appointment', [
                    'calcom_id' => $calcomId,
                    'booking' => $booking
                ]);
            }
        } catch (\Exception $e) {
            $this->errors++;
            Log::error('Error syncing Cal.com booking', [
                'booking_id' => $calcomId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function transformToWebhookFormat(array $booking): array
    {
        // Transform v1 API response to webhook format
        return [
            'id' => $booking['id'],
            'uid' => $booking['uid'] ?? $booking['id'],
            'startTime' => $booking['startTime'],
            'endTime' => $booking['endTime'],
            'eventTypeId' => $booking['eventTypeId'] ?? null,
            'eventType' => $booking['eventType'] ?? null,
            'title' => $booking['title'] ?? null,
            'description' => $booking['description'] ?? null,
            'additionalNotes' => $booking['additionalNotes'] ?? null,
            'location' => $booking['location'] ?? null,
            'meetingUrl' => $booking['meetingUrl'] ?? null,
            'status' => $booking['status'] ?? 'confirmed',
            'attendees' => $booking['attendees'] ?? [],
            'user' => $booking['user'] ?? [],
            'responses' => $booking['responses'] ?? $booking['customInputs'] ?? [],
            'customInputs' => $booking['customInputs'] ?? [],
            'metadata' => $booking['metadata'] ?? [],
            'name' => $booking['attendees'][0]['name'] ?? null,
            'email' => $booking['attendees'][0]['email'] ?? null,
        ];
    }

    private function showSummary(): void
    {
        $this->info('');
        $this->info('📊 Synchronization Summary:');
        $this->info(str_repeat('=', 60));

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Bookings Processed', $this->totalBookings],
                ['New Appointments Created', $this->newBookings],
                ['Existing Appointments Updated', $this->updatedBookings],
                ['Errors', $this->errors],
            ]
        );

        if ($this->errors > 0) {
            $this->warn("⚠️  {$this->errors} errors occurred during sync. Check logs for details.");
        } else {
            $this->info('✅ Synchronization completed successfully!');
        }

        // Show sample appointments
        $this->info('');
        $this->info('📅 Recent Cal.com Appointments:');
        $recentAppointments = \App\Models\Appointment::where('source', 'cal.com')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if ($recentAppointments->count() > 0) {
            $this->table(
                ['ID', 'Customer', 'Start Time', 'Status'],
                $recentAppointments->map(function ($a) {
                    return [
                        $a->id,
                        $a->customer->name ?? 'Unknown',
                        Carbon::parse($a->starts_at)->format('Y-m-d H:i'),
                        $a->status,
                    ];
                })
            );
        } else {
            $this->info('No Cal.com appointments found.');
        }
    }
}