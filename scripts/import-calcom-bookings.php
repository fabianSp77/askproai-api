#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CalcomV2Service;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\CalcomBooking;
use App\Models\CalcomEventType;
use App\Models\Company;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "  Cal.com V2 Bookings Import\n";
echo "════════════════════════════════════════════════════════════════\n\n";

$service = new CalcomV2Service();

// Get default company and branch
$company = Company::first();
$branch = Branch::first();

if (!$company || !$branch) {
    echo "❌ Error: No company or branch found in database\n";
    exit(1);
}

echo "📋 Configuration:\n";
echo "  • Company: {$company->name}\n";
echo "  • Branch: {$branch->name}\n\n";

// Stats
$stats = [
    'processed' => 0,
    'created' => 0,
    'updated' => 0,
    'failed' => 0,
];

try {
    // First, create a mapping of Cal.com event type IDs to local IDs
    $eventTypeMapping = [];
    $calcomEventTypes = CalcomEventType::all();
    foreach ($calcomEventTypes as $et) {
        $eventTypeMapping[$et->calcom_event_type_id] = $et->id;
    }
    
    echo "📍 Event Type Mapping loaded: " . count($eventTypeMapping) . " types\n";
    
    echo "🔍 Fetching bookings from Cal.com...\n";
    $bookings = $service->getAllBookings();
    $total = count($bookings);
    
    echo "  ✅ Found {$total} bookings\n\n";
    
    if ($total === 0) {
        echo "  No bookings to import.\n";
        exit(0);
    }
    
    echo "📥 Importing bookings...\n";
    $progressBar = "  Progress: ";
    
    foreach ($bookings as $index => $booking) {
        $stats['processed']++;
        
        // Show progress
        if ($index % 10 === 0) {
            $percent = round(($index / $total) * 100);
            echo "\r{$progressBar}[" . str_repeat("▓", $percent/2) . str_repeat("░", 50 - $percent/2) . "] {$percent}%";
        }
        
        try {
            // Find or create customer
            $attendee = $booking['attendees'][0] ?? null;
            if (!$attendee) {
                // Fallback to responses
                $attendee = [
                    'name' => $booking['responses']['name'] ?? 'Unknown',
                    'email' => $booking['responses']['email'] ?? 'unknown@example.com',
                ];
            }
            
            $customer = Customer::firstOrCreate(
                ['email' => $attendee['email']],
                [
                    'name' => $attendee['name'] ?? 'Unknown',
                    'phone' => $attendee['phoneNumber'] ?? null,
                    'notes' => 'Imported from Cal.com',
                ]
            );
            
            // Parse dates
            $startsAt = Carbon::parse($booking['startTime'] ?? $booking['start']);
            $endsAt = Carbon::parse($booking['endTime'] ?? $booking['end']);
            
            // Map Cal.com event type ID to local ID
            $calcomEventTypeId = $booking['eventTypeId'] ?? null;
            $localEventTypeId = null;
            if ($calcomEventTypeId && isset($eventTypeMapping[$calcomEventTypeId])) {
                $localEventTypeId = $eventTypeMapping[$calcomEventTypeId];
            }
            
            // Check if appointment exists
            $existingAppointment = Appointment::where('calcom_v2_booking_id', $booking['id'])->first();
            
            if ($existingAppointment) {
                // Update existing
                $existingAppointment->update([
                    'status' => strtolower($booking['status'] ?? 'confirmed'),
                    'meeting_url' => $booking['meetingUrl'] ?? null,
                    'notes' => $booking['description'] ?? null,
                    'calcom_event_type_id' => $localEventTypeId,
                ]);
                $stats['updated']++;
            } else {
                // Create new appointment
                $appointment = Appointment::create([
                    'customer_id' => $customer->id,
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'calcom_v2_booking_id' => $booking['id'],
                    'calcom_booking_uid' => $booking['uid'] ?? null,
                    'calcom_event_type_id' => $localEventTypeId,  // Use mapped local ID
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => strtolower($booking['status'] ?? 'confirmed'),
                    'notes' => $booking['description'] ?? null,
                    'meeting_url' => $booking['meetingUrl'] ?? null,
                    'source' => 'cal.com',
                    'payload' => json_encode($booking),
                ]);
                
                // Also create CalcomBooking record
                CalcomBooking::create([
                    'calcom_uid' => $booking['uid'] ?? $booking['id'],
                    'appointment_id' => $appointment->id,
                    'status' => $booking['status'] ?? 'ACCEPTED',
                    'raw_payload' => $booking,
                ]);
                
                $stats['created']++;
            }
            
        } catch (\Exception $e) {
            $stats['failed']++;
            Log::error('Failed to import booking', [
                'booking_id' => $booking['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
        }
    }
    
    echo "\r{$progressBar}[" . str_repeat("▓", 50) . "] 100%\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "════════════════════════════════════════════════════════════════\n";
echo "  Import Complete\n";
echo "════════════════════════════════════════════════════════════════\n\n";
echo "📊 Statistics:\n";
echo "  • Processed: {$stats['processed']}\n";
echo "  • Created:   {$stats['created']}\n";
echo "  • Updated:   {$stats['updated']}\n";
echo "  • Failed:    {$stats['failed']}\n\n";

if ($stats['failed'] > 0) {
    echo "⚠️  Some records failed. Check logs for details.\n";
} else {
    echo "✅ All bookings imported successfully!\n";
}

echo "\n";