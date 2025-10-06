<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Company;
use App\Services\PhoneNumberNormalizer;
use Carbon\Carbon;

class ImportCalcomBookingsDirectly extends Command
{
    protected $signature = 'calcom:import-directly
                            {--days=180 : Days to look back}
                            {--future=90 : Days to look forward}
                            {--force : Skip existing appointments}';

    protected $description = 'Import Cal.com bookings directly without webhook handler';

    public function handle()
    {
        $this->info('🔄 Direct Cal.com Booking Import');
        $this->info(str_repeat('=', 60));

        $apiKey = config('services.calcom.api_key');
        $from = now()->subDays((int)$this->option('days'))->toIso8601String();
        $to = now()->addDays((int)$this->option('future'))->toIso8601String();

        $response = Http::get('https://api.cal.com/v1/bookings', [
            'apiKey' => $apiKey,
            'from' => $from,
            'to' => $to,
        ]);

        if (!$response->successful()) {
            $this->error('Failed to fetch bookings');
            return 1;
        }

        $bookings = $response->json()['bookings'] ?? [];
        $this->info('Found ' . count($bookings) . ' bookings');

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $defaultCompany = Company::first();

        foreach ($bookings as $booking) {
            try {
                $calcomId = $booking['id'] ?? $booking['uid'] ?? null;

                // Skip if exists and not forced
                if (!$this->option('force')) {
                    $exists = Appointment::where('calcom_v2_booking_id', $calcomId)->exists();
                    if ($exists) {
                        $skipped++;
                        continue;
                    }
                }

                // Extract customer info
                $attendee = $booking['attendees'][0] ?? [];
                $customerName = $attendee['name'] ?? $booking['name'] ?? 'Cal.com Customer';
                $customerEmail = $attendee['email'] ?? $booking['email'] ?? 'calcom_' . uniqid() . '@noemail.com';
                $customerPhone = $this->extractPhone($attendee, $booking);

                // Find or create customer
                $customer = $this->findOrCreateCustomer($customerName, $customerEmail, $customerPhone);

                // Create appointment (without event type ID to avoid FK constraint)
                $appointment = Appointment::updateOrCreate(
                    ['calcom_v2_booking_id' => $calcomId],
                    [
                        'company_id' => $customer->company_id ?? $defaultCompany->id ?? 1,
                        'customer_id' => $customer->id,
                        'starts_at' => Carbon::parse($booking['startTime']),
                        'ends_at' => Carbon::parse($booking['endTime']),
                        'status' => strtolower($booking['status'] ?? 'confirmed'),
                        'source' => 'cal.com',
                        'notes' => $booking['description'] ?? $booking['responses']['notes'] ?? null,
                        'metadata' => json_encode([
                            'cal_com_data' => $booking,
                            'imported_at' => now()->toIso8601String(),
                            'event_type_id' => $booking['eventTypeId'] ?? null, // Store in metadata instead
                        ]),
                    ]
                );

                if ($appointment->wasRecentlyCreated) {
                    $created++;
                    $this->info("✓ Created: {$customerName} on " . Carbon::parse($booking['startTime'])->format('Y-m-d H:i'));
                } else {
                    $updated++;
                    $this->info("↻ Updated: {$customerName}");
                }

            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Error: " . $e->getMessage());
                $this->error("  Booking: " . json_encode($booking));
            }
        }

        $this->info('');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', count($bookings)],
                ['Created', $created],
                ['Updated', $updated],
                ['Skipped', $skipped],
                ['Errors', $errors],
            ]
        );

        return 0;
    }

    private function findOrCreateCustomer(string $name, string $email, ?string $phone): Customer
    {
        // Try email first
        if ($email && !str_contains($email, '@noemail.com')) {
            $customer = Customer::where('email', $email)->first();
            if ($customer) {
                if ($phone && !$customer->phone) {
                    $customer->update(['phone' => $phone]);
                }
                return $customer;
            }
        }

        // Try phone
        if ($phone) {
            $normalizedPhone = PhoneNumberNormalizer::normalize($phone);
            $phoneVariants = PhoneNumberNormalizer::generateVariants($normalizedPhone ?? $phone);

            $customer = Customer::where(function ($query) use ($phoneVariants) {
                foreach ($phoneVariants as $variant) {
                    $query->orWhere('phone', $variant);
                }
            })->first();

            if ($customer) {
                if ($email && !$customer->email) {
                    $customer->update(['email' => $email]);
                }
                return $customer;
            }
        }

        // Create new
        return Customer::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone ?? '',
            'source' => 'cal.com',
        ]);
    }

    private function extractPhone($attendee, $booking): ?string
    {
        return $attendee['phone'] ??
               $attendee['phoneNumber'] ??
               $booking['phone'] ??
               $booking['phoneNumber'] ??
               $booking['responses']['phone'] ??
               $booking['responses']['attendeePhoneNumber'] ??
               null;
    }
}