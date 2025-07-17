<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Set company context
$companyId = 1;
app()->instance('current_company_id', $companyId);

echo "Creating simple test appointments for Company ID: $companyId\n";

// Use specific customer IDs from database that we know exist
$customerIds = [19, 20, 21, 7]; // Anna Schmidt, Peter Müller, Julia Weber, Hans Schuster

// Create appointments without branch/staff relationships
DB::statement('SET @disable_tenant_scope = 1');

$appointments = [
    // Today appointments
    [
        'company_id' => $companyId,
        'customer_id' => $customerIds[0],
        'starts_at' => Carbon::today()->setHour(9)->setMinute(0),
        'ends_at' => Carbon::today()->setHour(9)->setMinute(30),
        'status' => 'confirmed',
        'source' => 'phone',
        'notes' => 'Erstberatung - Rückenschmerzen',
        'price' => 50,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ],
    [
        'company_id' => $companyId,
        'customer_id' => $customerIds[1],
        'starts_at' => Carbon::today()->setHour(10)->setMinute(30),
        'ends_at' => Carbon::today()->setHour(11)->setMinute(0),
        'status' => 'completed',
        'source' => 'phone',
        'notes' => 'Nachuntersuchung',
        'price' => 50,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ],
    [
        'company_id' => $companyId,
        'customer_id' => $customerIds[2],
        'starts_at' => Carbon::today()->setHour(14)->setMinute(0),
        'ends_at' => Carbon::today()->setHour(14)->setMinute(30),
        'status' => 'scheduled',
        'source' => 'phone',
        'notes' => 'Beratung wegen Allergie',
        'price' => 50,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ],
    // Tomorrow appointments
    [
        'company_id' => $companyId,
        'customer_id' => $customerIds[3],
        'starts_at' => Carbon::tomorrow()->setHour(9)->setMinute(0),
        'ends_at' => Carbon::tomorrow()->setHour(9)->setMinute(30),
        'status' => 'pending',
        'source' => 'phone',
        'notes' => 'Ersttermin - Kopfschmerzen',
        'price' => 50,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ],
    [
        'company_id' => $companyId,
        'customer_id' => $customerIds[0],
        'starts_at' => Carbon::tomorrow()->setHour(15)->setMinute(0),
        'ends_at' => Carbon::tomorrow()->setHour(15)->setMinute(30),
        'status' => 'scheduled',
        'source' => 'phone',
        'notes' => 'Folgetermin',
        'price' => 50,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ],
    // Past appointments
    [
        'company_id' => $companyId,
        'customer_id' => $customerIds[1],
        'starts_at' => Carbon::yesterday()->setHour(10)->setMinute(0),
        'ends_at' => Carbon::yesterday()->setHour(10)->setMinute(30),
        'status' => 'completed',
        'source' => 'phone',
        'notes' => 'Routineuntersuchung',
        'price' => 50,
        'created_at' => Carbon::yesterday(),
        'updated_at' => Carbon::yesterday()
    ],
    // Cancelled appointment
    [
        'company_id' => $companyId,
        'customer_id' => $customerIds[2],
        'starts_at' => Carbon::today()->setHour(16)->setMinute(0),
        'ends_at' => Carbon::today()->setHour(16)->setMinute(30),
        'status' => 'cancelled',
        'source' => 'phone',
        'notes' => 'Kunde hat abgesagt',
        'price' => 50,
        'created_at' => Carbon::now()->subHours(2),
        'updated_at' => Carbon::now()->subHours(2)
    ],
    // Next week appointments
    [
        'company_id' => $companyId,
        'customer_id' => $customerIds[0],
        'starts_at' => Carbon::now()->addDays(3)->setHour(11)->setMinute(0),
        'ends_at' => Carbon::now()->addDays(3)->setHour(11)->setMinute(30),
        'status' => 'scheduled',
        'source' => 'phone',
        'notes' => 'Kontrolle',
        'price' => 50,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ],
    [
        'company_id' => $companyId,
        'customer_id' => $customerIds[1],
        'starts_at' => Carbon::now()->addDays(5)->setHour(9)->setMinute(30),
        'ends_at' => Carbon::now()->addDays(5)->setHour(10)->setMinute(0),
        'status' => 'scheduled',
        'source' => 'phone',
        'notes' => 'Beratungstermin',
        'price' => 75,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ],
];

$created = 0;
foreach ($appointments as $data) {
    try {
        $appointment = DB::table('appointments')->insertGetId($data);
        $created++;
        echo "Created appointment ID: {$appointment} - {$data['notes']} - Status: {$data['status']}\n";
    } catch (\Exception $e) {
        echo "Error creating appointment: " . $e->getMessage() . "\n";
    }
}

DB::statement('SET @disable_tenant_scope = 0');

// Show summary
echo "\nCreated $created appointments\n";

// Show appointments by status using raw query
$statuses = DB::select("
    SELECT status, COUNT(*) as count 
    FROM appointments 
    WHERE company_id = ? 
    GROUP BY status
", [$companyId]);

echo "\nAppointments by status:\n";
foreach ($statuses as $status) {
    echo "  {$status->status}: {$status->count}\n";
}

echo "\nTest appointments created successfully!\n";