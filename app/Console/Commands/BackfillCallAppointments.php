<?php

namespace App\Console\Commands;

use App\Models\{Call, Appointment, Customer, Service, Company};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Log};
use Carbon\Carbon;

class BackfillCallAppointments extends Command
{
    protected $signature = 'appointments:backfill
                            {call_id? : Specific call ID to backfill}
                            {--all : Backfill all calls with booking_confirmed=1 but no appointment}
                            {--dry-run : Show what would be created without actually creating}';

    protected $description = 'Create missing appointments from calls with booking_details (nachträglicher Import)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        if ($callId = $this->argument('call_id')) {
            return $this->backfillSingleCall($callId, $dryRun);
        }

        if ($this->option('all')) {
            return $this->backfillAllCalls($dryRun);
        }

        $this->error('Please specify a call_id or use --all flag');
        return 1;
    }

    private function backfillSingleCall(int $callId, bool $dryRun): int
    {
        $call = Call::with(['appointments', 'customer'])->find($callId);

        if (!$call) {
            $this->error("❌ Call {$callId} not found");
            return 1;
        }

        $this->info("📞 Processing Call ID: {$call->id}");
        $this->line("   Retell ID: {$call->retell_call_id}");
        $this->line("   Created: {$call->created_at}");
        $this->newLine();

        if ($call->appointments->isNotEmpty()) {
            $this->warn("⚠️  Call already has {$call->appointments->count()} appointment(s)");
            $this->line("   Appointment IDs: " . $call->appointments->pluck('id')->join(', '));
            return 1;
        }

        if (!$call->booking_details) {
            $this->error("❌ Call has no booking_details");
            return 1;
        }

        $bookingData = json_decode($call->booking_details, true);
        $calcomBooking = $bookingData['calcom_booking'] ?? null;

        if (!$calcomBooking) {
            $this->error("❌ Invalid booking_details structure - no calcom_booking found");
            return 1;
        }

        $this->info("✓ Found Cal.com booking data:");
        $this->line("   Cal.com ID: {$calcomBooking['id']}");
        $this->line("   UID: {$calcomBooking['uid']}");
        $this->line("   Start: {$calcomBooking['start']}");
        $this->line("   Attendee: {$calcomBooking['attendees'][0]['name']} ({$calcomBooking['attendees'][0]['email']})");
        $this->newLine();

        // Ensure customer exists
        $customer = $this->ensureCustomer($call, $calcomBooking, $dryRun);

        if (!$customer && !$dryRun) {
            $this->error("❌ Failed to create/find customer");
            return 1;
        }

        // Ensure company_id is set
        if (!$call->company_id && !$dryRun) {
            $companyId = $this->detectCompanyId($call, $calcomBooking);
            $call->update(['company_id' => $companyId]);
            $this->info("✓ Updated call company_id to {$companyId}");
        }

        // Create appointment
        $appointmentData = $this->buildAppointmentData($call, $calcomBooking, $customer);

        $this->info("📋 Appointment data to create:");
        foreach ($appointmentData as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $this->line("   {$key}: {$value}");
            }
        }
        $this->newLine();

        if ($dryRun) {
            $this->warn("🔍 DRY RUN - Would create appointment with above data");
            return 0;
        }

        try {
            $appointment = Appointment::create($appointmentData);

            $this->info("✅ Appointment created successfully!");
            $this->line("   Appointment ID: {$appointment->id}");
            $this->line("   Customer: {$appointment->customer->name}");
            $this->line("   Time: {$appointment->starts_at}");

            // Update call booking_confirmed flag
            if (!$call->booking_confirmed) {
                $call->update(['booking_confirmed' => 1]);
                $this->info("✓ Updated call booking_confirmed flag");
            }

            Log::info('Backfilled appointment from call', [
                'call_id' => $call->id,
                'appointment_id' => $appointment->id,
                'calcom_booking_id' => $calcomBooking['id']
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Failed to create appointment: {$e->getMessage()}");
            Log::error('Backfill failed', [
                'call_id' => $call->id,
                'error' => $e->getMessage()
            ]);
            return 1;
        }
    }

    private function backfillAllCalls(bool $dryRun): int
    {
        $this->info('🔍 Finding calls with bookings but no appointments...');

        $calls = Call::whereNotNull('booking_details')
            ->where('booking_confirmed', 1)
            ->whereDoesntHave('appointments')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($calls->isEmpty()) {
            $this->info('✅ No calls need backfilling - all have appointments');
            return 0;
        }

        $this->warn("Found {$calls->count()} call(s) needing backfill");
        $this->newLine();

        if (!$dryRun && !$this->confirm("Create appointments for {$calls->count()} calls?")) {
            $this->info('Cancelled');
            return 0;
        }

        $successful = 0;
        $failed = 0;

        foreach ($calls as $call) {
            $this->line("Processing Call {$call->id}...");

            $result = $this->backfillSingleCall($call->id, $dryRun);

            if ($result === 0) {
                $successful++;
            } else {
                $failed++;
            }

            $this->newLine();
        }

        $this->info("Summary:");
        $this->line("  Successful: {$successful}");
        $this->line("  Failed: {$failed}");

        return $failed > 0 ? 1 : 0;
    }

    private function ensureCustomer(Call $call, array $calcomBooking, bool $dryRun): ?Customer
    {
        if ($call->customer_id && $call->customer) {
            $this->info("✓ Using existing customer: {$call->customer->name} (ID: {$call->customer_id})");
            return $call->customer;
        }

        $attendee = $calcomBooking['attendees'][0] ?? null;
        if (!$attendee) {
            $this->error("❌ No attendee data in Cal.com booking");
            return null;
        }

        $name = $attendee['name'] ?? 'Unknown';
        $email = $attendee['email'] ?? null;
        $phone = $calcomBooking['bookingFieldsResponses']['phone'] ?? $call->from_number ?? 'unknown';

        // Try to find existing customer
        $customer = Customer::where(function($q) use ($email, $phone) {
            if ($email) $q->orWhere('email', $email);
            if ($phone !== 'unknown') $q->orWhere('phone', $phone);
        })->where('company_id', $call->company_id ?? 1)
          ->first();

        if ($customer) {
            $this->info("✓ Found existing customer: {$customer->name} (ID: {$customer->id})");
            if (!$dryRun && !$call->customer_id) {
                $call->update(['customer_id' => $customer->id]);
                $this->info("✓ Linked customer to call");
            }
            return $customer;
        }

        if ($dryRun) {
            $this->warn("🔍 DRY RUN - Would create customer: {$name} ({$email})");
            return new Customer(['id' => 0, 'name' => $name]); // Dummy for dry run
        }

        // Create new customer
        $customer = Customer::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'company_id' => $call->company_id ?? 1,
            'source' => 'retell_phone_backfill',
            'status' => 'active',
            'customer_type' => 'private'
        ]);

        $this->info("✓ Created new customer: {$customer->name} (ID: {$customer->id})");

        // Link to call
        $call->update(['customer_id' => $customer->id]);

        return $customer;
    }

    private function detectCompanyId(Call $call, array $calcomBooking): int
    {
        // Try to detect from event type
        $eventTypeId = $calcomBooking['eventTypeId'] ?? null;

        if ($eventTypeId) {
            $service = Service::where('calcom_event_type_id', $eventTypeId)->first();
            if ($service) {
                return $service->company_id;
            }
        }

        // Try to detect from phone number
        if ($call->to_number && $call->to_number !== 'unknown') {
            $phoneCompany = Company::where('phone', $call->to_number)->first();
            if ($phoneCompany) {
                return $phoneCompany->id;
            }
        }

        // Default to Company ID 1
        return 1;
    }

    private function buildAppointmentData(Call $call, array $calcomBooking, ?Customer $customer): array
    {
        $attendee = $calcomBooking['attendees'][0] ?? [];

        return [
            'calcom_v2_booking_id' => (string) $calcomBooking['id'],
            'external_id' => $calcomBooking['uid'],
            'customer_id' => $customer->id ?? $call->customer_id,
            'company_id' => $call->company_id ?? 1,
            'branch_id' => $call->branch_id,
            'service_id' => $this->findServiceId($calcomBooking),
            'staff_id' => $this->findStaffId($calcomBooking),
            'call_id' => $call->id,
            'starts_at' => Carbon::parse($calcomBooking['start']),
            'ends_at' => Carbon::parse($calcomBooking['end']),
            'status' => $this->mapStatus($calcomBooking['status'] ?? 'accepted'),
            'source' => 'retell_phone',
            'booking_type' => 'single',
            'notes' => $calcomBooking['description'] ?? $calcomBooking['bookingFieldsResponses']['notes'] ?? null,
            'metadata' => json_encode([
                'calcom_booking_id' => $calcomBooking['id'],
                'customer_name' => $attendee['name'] ?? 'Unknown',
                'customer_email' => $attendee['email'] ?? null,
                'customer_phone' => $calcomBooking['bookingFieldsResponses']['phone'] ?? null,
                'sync_method' => 'backfill',
                'backfilled_at' => now()->toIso8601String(),
                'original_call_id' => $call->retell_call_id
            ]),
            'created_at' => $calcomBooking['createdAt'] ? Carbon::parse($calcomBooking['createdAt']) : now(),
            'updated_at' => now()
        ];
    }

    private function findServiceId(array $calcomBooking): ?int
    {
        $eventTypeId = $calcomBooking['eventTypeId'] ?? null;
        if (!$eventTypeId) {
            return null;
        }

        return Service::where('calcom_event_type_id', $eventTypeId)->value('id');
    }

    private function findStaffId(array $calcomBooking): ?string
    {
        $hosts = $calcomBooking['hosts'] ?? [];
        if (empty($hosts)) {
            return null;
        }

        $hostEmail = $hosts[0]['email'] ?? null;
        if (!$hostEmail) {
            return null;
        }

        return DB::table('staff')->where('email', $hostEmail)->value('id');
    }

    private function mapStatus(string $calcomStatus): string
    {
        return match(strtolower($calcomStatus)) {
            'accepted' => 'confirmed',
            'pending' => 'pending',
            'cancelled', 'rejected' => 'cancelled',
            default => 'scheduled'
        };
    }
}
