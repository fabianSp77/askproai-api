#!/usr/bin/env php
<?php

/**
 * ASK-010: Manual Appointment Metadata Validation Script
 *
 * PURPOSE: Quick manual validation of appointment metadata fields
 *
 * USAGE:
 *   php tests/manual_metadata_validation.php
 *
 * VALIDATES:
 * - Metadata field existence in database
 * - Sample data creation with metadata
 * - Metadata retrieval and verification
 * - Complete lifecycle: Book → Reschedule → Cancel
 *
 * OUTPUT: Clear PASS/FAIL status with detailed information
 */

// Bootstrap Laravel application
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Call;
use App\Services\Retell\AppointmentCreationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Color output helpers
function colorize($text, $color) {
    $colors = [
        'green'  => "\033[0;32m",
        'red'    => "\033[0;31m",
        'yellow' => "\033[0;33m",
        'blue'   => "\033[0;34m",
        'reset'  => "\033[0m",
    ];
    return ($colors[$color] ?? '') . $text . $colors['reset'];
}

function pass($message) {
    echo colorize("[PASS] ", 'green') . $message . "\n";
}

function fail($message) {
    echo colorize("[FAIL] ", 'red') . $message . "\n";
}

function info($message) {
    echo colorize("[INFO] ", 'blue') . $message . "\n";
}

function section($title) {
    echo "\n" . colorize(str_repeat('=', 70), 'yellow') . "\n";
    echo colorize("  " . $title, 'yellow') . "\n";
    echo colorize(str_repeat('=', 70), 'yellow') . "\n\n";
}

// Start validation
echo "\n";
section("APPOINTMENT METADATA VALIDATION SCRIPT");
echo "Started at: " . Carbon::now()->format('Y-m-d H:i:s') . "\n\n";

$errors = 0;
$passes = 0;

try {
    // ========================================================================
    // STEP 1: DATABASE SCHEMA VALIDATION
    // ========================================================================
    section("STEP 1: Database Schema Validation");

    info("Checking appointments table for metadata columns...");

    $columns = DB::select("SHOW COLUMNS FROM appointments");
    $columnNames = array_column($columns, 'Field');

    $requiredColumns = [
        'created_by',
        'booking_source',
        'booked_by_user_id',
        'rescheduled_at',
        'rescheduled_by',
        'reschedule_source',
        'previous_starts_at',
        'cancelled_at',
        'cancelled_by',
        'cancellation_source',
    ];

    $missingColumns = [];
    foreach ($requiredColumns as $column) {
        if (in_array($column, $columnNames)) {
            pass("Column exists: {$column}");
            $passes++;
        } else {
            fail("Column missing: {$column}");
            $missingColumns[] = $column;
            $errors++;
        }
    }

    if (!empty($missingColumns)) {
        fail("Missing columns: " . implode(', ', $missingColumns));
        echo "\n";
        info("You may need to run migrations to add these columns.");
        info("Check database/migrations for metadata-related migrations.");
        echo "\n";
    }

    // ========================================================================
    // STEP 2: CREATE TEST DATA
    // ========================================================================
    section("STEP 2: Create Test Data");

    info("Creating test company, branch, service, customer...");

    DB::beginTransaction();

    $company = Company::firstOrCreate(
        ['name' => 'Metadata Test Company'],
        ['name' => 'Metadata Test Company', 'status' => 'active']
    );
    pass("Company created: ID {$company->id}");
    $passes++;

    $branch = Branch::firstOrCreate(
        ['company_id' => $company->id, 'name' => 'Test Branch'],
        ['company_id' => $company->id, 'name' => 'Test Branch']
    );
    pass("Branch created: ID {$branch->id}");
    $passes++;

    $service = Service::firstOrCreate(
        ['company_id' => $company->id, 'name' => 'Metadata Test Service'],
        [
            'company_id' => $company->id,
            'name' => 'Metadata Test Service',
            'duration_minutes' => 60,
            'price' => 50.00,
            'calcom_event_type_id' => 999999,
        ]
    );
    pass("Service created: ID {$service->id}");
    $passes++;

    $customer = Customer::firstOrCreate(
        ['email' => 'metadata-test@example.com'],
        [
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Metadata Test Customer',
            'email' => 'metadata-test@example.com',
            'phone' => '+49 999 999999',
            'status' => 'active',
        ]
    );
    pass("Customer created: ID {$customer->id}");
    $passes++;

    $call = Call::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'from_number' => $customer->phone,
        'retell_call_id' => 'test_call_' . uniqid(),
        'status' => 'completed',
    ]);
    pass("Call created: ID {$call->id}");
    $passes++;

    // ========================================================================
    // STEP 3: TEST BOOKING METADATA
    // ========================================================================
    section("STEP 3: Test Booking Metadata");

    info("Creating appointment via AppointmentCreationService...");

    $appointmentService = app(AppointmentCreationService::class);

    $bookingDetails = [
        'starts_at' => Carbon::now()->addDays(7)->setTime(10, 0, 0)->format('Y-m-d H:i:s'),
        'ends_at' => Carbon::now()->addDays(7)->setTime(11, 0, 0)->format('Y-m-d H:i:s'),
        'service' => $service->name,
        'duration_minutes' => 60,
    ];

    $appointment = $appointmentService->createLocalRecord(
        $customer,
        $service,
        $bookingDetails,
        'calcom_test_' . uniqid(),
        $call
    );

    if ($appointment && $appointment->id) {
        pass("Appointment created: ID {$appointment->id}");
        $passes++;
    } else {
        fail("Failed to create appointment");
        $errors++;
    }

    // Validate booking metadata
    if (in_array('created_by', $columnNames)) {
        if ($appointment->created_by === 'customer') {
            pass("created_by = 'customer'");
            $passes++;
        } else {
            fail("created_by = '{$appointment->created_by}' (expected 'customer')");
            $errors++;
        }
    }

    if (in_array('booking_source', $columnNames)) {
        if ($appointment->booking_source === 'retell_webhook') {
            pass("booking_source = 'retell_webhook'");
            $passes++;
        } else {
            fail("booking_source = '{$appointment->booking_source}' (expected 'retell_webhook')");
            $errors++;
        }
    }

    if (in_array('booked_by_user_id', $columnNames)) {
        if ($appointment->booked_by_user_id === null) {
            pass("booked_by_user_id = null (customer booking)");
            $passes++;
        } else {
            fail("booked_by_user_id = {$appointment->booked_by_user_id} (expected null)");
            $errors++;
        }
    }

    // ========================================================================
    // STEP 4: TEST RESCHEDULE METADATA
    // ========================================================================
    section("STEP 4: Test Reschedule Metadata");

    info("Rescheduling appointment...");

    $originalTime = Carbon::parse($appointment->starts_at);
    $newTime = Carbon::now()->addDays(10)->setTime(14, 0, 0);

    $appointment->update([
        'starts_at' => $newTime,
        'ends_at' => $newTime->copy()->addHour(),
        'rescheduled_at' => now(),
        'rescheduled_by' => 'customer',
        'reschedule_source' => 'customer_portal',
        'previous_starts_at' => $originalTime,
    ]);

    $appointment->refresh();

    if (in_array('rescheduled_at', $columnNames)) {
        if ($appointment->rescheduled_at !== null) {
            pass("rescheduled_at = {$appointment->rescheduled_at}");
            $passes++;
        } else {
            fail("rescheduled_at is null (expected timestamp)");
            $errors++;
        }
    }

    if (in_array('rescheduled_by', $columnNames)) {
        if ($appointment->rescheduled_by === 'customer') {
            pass("rescheduled_by = 'customer'");
            $passes++;
        } else {
            fail("rescheduled_by = '{$appointment->rescheduled_by}' (expected 'customer')");
            $errors++;
        }
    }

    if (in_array('reschedule_source', $columnNames)) {
        if ($appointment->reschedule_source === 'customer_portal') {
            pass("reschedule_source = 'customer_portal'");
            $passes++;
        } else {
            fail("reschedule_source = '{$appointment->reschedule_source}' (expected 'customer_portal')");
            $errors++;
        }
    }

    if (in_array('previous_starts_at', $columnNames)) {
        if ($appointment->previous_starts_at &&
            $appointment->previous_starts_at->format('Y-m-d H:i:s') === $originalTime->format('Y-m-d H:i:s')) {
            pass("previous_starts_at = {$appointment->previous_starts_at} (original time preserved)");
            $passes++;
        } else {
            fail("previous_starts_at not correct");
            $errors++;
        }
    }

    // Verify booking metadata still preserved
    if (in_array('created_by', $columnNames)) {
        if ($appointment->created_by === 'customer') {
            pass("created_by still 'customer' after reschedule");
            $passes++;
        } else {
            fail("created_by changed after reschedule");
            $errors++;
        }
    }

    // ========================================================================
    // STEP 5: TEST CANCELLATION METADATA
    // ========================================================================
    section("STEP 5: Test Cancellation Metadata");

    info("Cancelling appointment...");

    $appointment->update([
        'status' => 'cancelled',
        'cancelled_at' => now(),
        'cancelled_by' => 'customer',
        'cancellation_source' => 'retell_api',
    ]);

    $appointment->refresh();

    if ($appointment->status === 'cancelled') {
        pass("status = 'cancelled'");
        $passes++;
    } else {
        fail("status = '{$appointment->status}' (expected 'cancelled')");
        $errors++;
    }

    if (in_array('cancelled_at', $columnNames)) {
        if ($appointment->cancelled_at !== null) {
            pass("cancelled_at = {$appointment->cancelled_at}");
            $passes++;
        } else {
            fail("cancelled_at is null (expected timestamp)");
            $errors++;
        }
    }

    if (in_array('cancelled_by', $columnNames)) {
        if ($appointment->cancelled_by === 'customer') {
            pass("cancelled_by = 'customer'");
            $passes++;
        } else {
            fail("cancelled_by = '{$appointment->cancelled_by}' (expected 'customer')");
            $errors++;
        }
    }

    if (in_array('cancellation_source', $columnNames)) {
        if ($appointment->cancellation_source === 'retell_api') {
            pass("cancellation_source = 'retell_api'");
            $passes++;
        } else {
            fail("cancellation_source = '{$appointment->cancellation_source}' (expected 'retell_api')");
            $errors++;
        }
    }

    // Verify all previous metadata still preserved
    if (in_array('created_by', $columnNames) && $appointment->created_by === 'customer') {
        pass("created_by still 'customer' after cancellation");
        $passes++;
    }

    if (in_array('rescheduled_at', $columnNames) && $appointment->rescheduled_at !== null) {
        pass("rescheduled_at still preserved after cancellation");
        $passes++;
    }

    // ========================================================================
    // STEP 6: CLEANUP
    // ========================================================================
    section("STEP 6: Cleanup");

    info("Rolling back test transaction...");
    DB::rollBack();
    pass("Test data rolled back (no changes to database)");
    $passes++;

} catch (\Exception $e) {
    DB::rollBack();
    fail("Exception: " . $e->getMessage());
    echo "\n";
    info("Stack trace:");
    echo $e->getTraceAsString() . "\n";
    $errors++;
}

// ========================================================================
// FINAL SUMMARY
// ========================================================================
section("VALIDATION SUMMARY");

echo "Total Tests: " . ($passes + $errors) . "\n";
echo colorize("Passed: {$passes}", 'green') . "\n";
echo colorize("Failed: {$errors}", 'red') . "\n";
echo "\n";

if ($errors === 0) {
    echo colorize("✓ ALL TESTS PASSED", 'green') . "\n";
    echo "\nAppointment metadata validation successful!\n";
    echo "All metadata fields are working correctly.\n\n";
    exit(0);
} else {
    echo colorize("✗ SOME TESTS FAILED", 'red') . "\n";
    echo "\nPlease review the failed tests above.\n";
    echo "You may need to:\n";
    echo "  1. Run database migrations\n";
    echo "  2. Check column definitions\n";
    echo "  3. Verify service implementations\n\n";
    exit(1);
}
