<?php

/**
 * Backfill Missing Appointments from booking_details
 *
 * ISSUE: 9 calls have booking_confirmed=true but no local Appointment record
 * CAUSE: createLocalRecord() failed silently, exception was swallowed
 * SOLUTION: Create Appointment records from booking_details JSON data
 *
 * Run: php database/scripts/backfill_missing_appointments.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   BACKFILL MISSING APPOINTMENTS - PHASE 5.3\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

DB::beginTransaction();

try {
    // Find calls with booking_confirmed=true but no appointment
    $callsWithoutAppointments = Call::where('booking_confirmed', true)
        ->whereDoesntHave('appointments')
        ->whereNotNull('booking_details')
        ->whereNotNull('booking_id')
        ->orderBy('id')
        ->get();

    echo "🔍 Found {$callsWithoutAppointments->count()} calls with booking but no appointment\n\n";

    $createdCount = 0;
    $skippedCount = 0;
    $errorCount = 0;

    foreach ($callsWithoutAppointments as $call) {
        echo "Processing Call ID: {$call->id}\n";
        echo "  Retell ID: {$call->retell_call_id}\n";
        echo "  From: {$call->from_number}\n";
        echo "  Company: {$call->company_id}\n";

        // Parse booking_details
        $bookingDetails = json_decode($call->booking_details, true);
        if (!$bookingDetails || !isset($bookingDetails['calcom_booking'])) {
            echo "  ⚠️  SKIP: No Cal.com booking data in booking_details\n\n";
            $skippedCount++;
            continue;
        }

        $calcomBooking = $bookingDetails['calcom_booking'];
        $bookingUid = $calcomBooking['uid'] ?? $call->booking_id;

        // Check if appointment already exists with this booking ID
        $existingAppointment = Appointment::where('calcom_v2_booking_id', $bookingUid)
            ->orWhere('external_id', $bookingUid)
            ->first();

        if ($existingAppointment) {
            echo "  ℹ️  SKIP: Appointment already exists (ID: {$existingAppointment->id})\n";
            echo "     Linking call to existing appointment...\n";

            $call->update([
                'appointment_id' => $existingAppointment->id,
                'appointment_made' => true
            ]);

            echo "  ✅ Call linked to existing appointment\n\n";
            $skippedCount++;
            continue;
        }

        // Extract appointment data from Cal.com booking
        $startTime = $calcomBooking['start'] ?? null;
        $endTime = $calcomBooking['end'] ?? null;
        $duration = $calcomBooking['duration'] ?? 30;

        if (!$startTime) {
            echo "  ❌ ERROR: No start time in Cal.com booking\n\n";
            $errorCount++;
            continue;
        }

        // Parse times
        $startsAt = Carbon::parse($startTime);
        $endsAt = $endTime ? Carbon::parse($endTime) : $startsAt->copy()->addMinutes($duration);

        // Extract attendee info
        $attendees = $calcomBooking['attendees'] ?? [];
        $attendee = $attendees[0] ?? null;
        $customerName = $attendee['name'] ?? 'Unknown';
        $customerEmail = $attendee['email'] ?? null;
        $customerPhone = $call->from_number ?? ($bookingDetails['bookingFieldsResponses']['phone'] ?? null);

        // Find or create customer
        $customer = null;

        // First try by phone
        if ($customerPhone && $customerPhone !== 'anonymous') {
            $customer = Customer::where('phone', $customerPhone)
                ->where('company_id', $call->company_id)
                ->first();
        }

        // If not found by phone, try by email
        if (!$customer && $customerEmail) {
            $customer = Customer::where('email', $customerEmail)
                ->where('company_id', $call->company_id)
                ->first();
        }

        // Create customer if not found
        if (!$customer) {
            // Determine if anonymous
            $isAnonymous = $customerPhone === 'anonymous' || empty($customerPhone);

            try {
                $customer = new Customer();
                $customer->company_id = $call->company_id;
                $customer->forceFill([
                    'name' => $customerName,
                    'email' => $isAnonymous ? ('anonymous_backfill_' . time() . '_' . $call->id . '@anonymous.local') : $customerEmail,
                    'phone' => $isAnonymous ? ('anonymous_backfill_' . time() . '_' . $call->id) : $customerPhone,
                    'source' => 'backfill_from_calcom',
                    'status' => 'active',
                    'notes' => 'Backfilled from Cal.com booking (Call ID: ' . $call->id . ')' . ($isAnonymous ? ' [Anonymous caller with email: ' . $customerEmail . ']' : '')
                ]);
                $customer->save();

                echo "  ✅ Created customer: {$customerName} (ID: {$customer->id})\n";
            } catch (\Illuminate\Database\QueryException $e) {
                // If duplicate email, try to find customer by email without company restriction
                if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    echo "  ⚠️  Email already exists, searching without company restriction...\n";
                    $customer = Customer::where('email', $customerEmail)->first();

                    if ($customer) {
                        echo "  ✅ Found existing customer by email: {$customer->name} (ID: {$customer->id}, Company: {$customer->company_id})\n";
                    } else {
                        // This shouldn't happen, but re-throw if we can't find the customer
                        throw $e;
                    }
                } else {
                    throw $e;
                }
            }
        } else {
            echo "  ✅ Found existing customer: {$customer->name} (ID: {$customer->id})\n";
        }

        // Link customer to call
        if (!$call->customer_id) {
            $call->customer_id = $customer->id;
            $call->save();
        }

        // Find service
        $eventTypeId = $calcomBooking['eventTypeId'] ?? ($calcomBooking['eventType']['id'] ?? null);
        $service = null;

        if ($eventTypeId) {
            $service = Service::where('calcom_event_type_id', $eventTypeId)
                ->where('company_id', $call->company_id)
                ->first();
        }

        // Fallback to default service
        if (!$service) {
            $service = Service::where('company_id', $call->company_id)
                ->where('is_active', true)
                ->first();
        }

        if (!$service) {
            echo "  ❌ ERROR: No service found for company {$call->company_id}\n\n";
            $errorCount++;
            continue;
        }

        echo "  ✅ Using service: {$service->name} (ID: {$service->id})\n";

        // Get default branch
        $branchId = $call->branch_id ?? $customer->branch_id;
        if (!$branchId) {
            $defaultBranch = Branch::where('company_id', $call->company_id)->first();
            $branchId = $defaultBranch ? $defaultBranch->id : null;
        }

        // Create appointment
        $appointment = new Appointment();
        $appointment->forceFill([
            'company_id' => $call->company_id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'branch_id' => $branchId,
            'call_id' => $call->id,
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'ends_at' => $endsAt->format('Y-m-d H:i:s'),
            'status' => $calcomBooking['status'] ?? 'scheduled',
            'notes' => 'Backfilled from Cal.com booking',
            'source' => 'backfill_script',
            'calcom_v2_booking_id' => $bookingUid,
            'external_id' => $bookingUid,
            'metadata' => json_encode([
                'backfilled' => true,
                'backfilled_at' => now()->toIso8601String(),
                'original_call_id' => $call->id,
                'calcom_booking_id' => $calcomBooking['id'] ?? null,
                'customer_name' => $customerName,
                'appointment_date' => $startsAt->format('Y-m-d'),
                'appointment_time' => $startsAt->format('H:i:s')
            ]),
            'created_by' => 'system',
            'booking_source' => 'backfill_from_calcom'
        ]);
        $appointment->save();

        // Update call record
        $call->update([
            'appointment_id' => $appointment->id,
            'appointment_made' => true
        ]);

        echo "  ✅ CREATED Appointment ID: {$appointment->id}\n";
        echo "     Start: {$startsAt->format('Y-m-d H:i')}\n";
        echo "     Customer: {$customer->name}\n";
        echo "     Service: {$service->name}\n\n";

        $createdCount++;
    }

    DB::commit();

    echo "═══════════════════════════════════════════════════════════════\n";
    echo "   ✅ BACKFILL COMPLETE\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    echo "📊 Summary:\n";
    echo "   Created: {$createdCount} appointments\n";
    echo "   Skipped: {$skippedCount} (already existed or linked)\n";
    echo "   Errors: {$errorCount}\n";
    echo "   Total Processed: {$callsWithoutAppointments->count()}\n\n";

    if ($createdCount > 0) {
        echo "✅ Successfully backfilled {$createdCount} missing appointments!\n\n";
    }

    if ($errorCount > 0) {
        echo "⚠️  {$errorCount} appointments could not be created. Check logs above.\n\n";
    }

    Log::info('Backfill missing appointments completed', [
        'created' => $createdCount,
        'skipped' => $skippedCount,
        'errors' => $errorCount,
        'total' => $callsWithoutAppointments->count()
    ]);

} catch (\Exception $e) {
    DB::rollBack();

    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "   ❌ ERROR\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n";
    echo "⚠️  Transaction rolled back. No changes were made.\n";
    echo "\n";

    Log::error('Backfill missing appointments failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    exit(1);
}
