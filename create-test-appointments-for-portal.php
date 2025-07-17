<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Set company context
$companyId = 1;
app()->instance('current_company_id', $companyId);

// Get company details
$company = Company::find($companyId);
if (!$company) {
    die("Company with ID $companyId not found!\n");
}

echo "Creating test appointments for Company: {$company->name}\n";

// Get first branch or create one
$branch = $company->branches()->first();
if (!$branch) {
    $branch = Branch::create([
        'company_id' => $companyId,
        'name' => 'Hauptfiliale',
        'phone' => '+4930123456',
        'email' => 'info@example.com',
        'address' => 'Musterstraße 1',
        'city' => 'Berlin',
        'postal_code' => '10115',
        'country' => 'DE',
        'timezone' => 'Europe/Berlin',
        'is_active' => true
    ]);
    echo "Created branch: {$branch->name}\n";
}

// Get first staff or create one
$staff = $company->staff()->first();
if (!$staff) {
    $staff = Staff::create([
        'company_id' => $companyId,
        'branch_id' => $branch->id,
        'name' => 'Dr. Max Mustermann',
        'email' => 'dr.mustermann@example.com',
        'is_active' => true
    ]);
    echo "Created staff: {$staff->name}\n";
}

// Get first service or create one
$service = $company->services()->first();
if (!$service) {
    $service = Service::create([
        'company_id' => $companyId,
        'name' => 'Beratungsgespräch',
        'duration' => 30,
        'price' => 50.00,
        'description' => 'Allgemeines Beratungsgespräch',
        'is_active' => true
    ]);
    echo "Created service: {$service->name}\n";
}

// Get or create customers
$customers = [];

// Use specific customer IDs from database
$customerIds = [19, 20, 21, 7]; // Anna Schmidt, Peter Müller, Julia Weber, Hans Schuster
$customers = [];
foreach ($customerIds as $id) {
    $customer = Customer::withoutGlobalScopes()->find($id);
    if ($customer) {
        $customers[] = $customer;
    }
}

if (count($customers) < 4) {
    die("Not enough customers found. Need at least 4 customers.\n");
}

echo "Using " . count($customers) . " customers\n";

// Create appointments without scopes to avoid issues
DB::statement('SET @disable_tenant_scope = 1');

$appointments = [
    // Today appointments
    [
        'company_id' => $companyId,
        'branch_id' => $branch->id,
        'customer_id' => $customers[0]->id,
        'staff_id' => $staff->id,
        'service_id' => $service->id,
        'starts_at' => Carbon::today()->setHour(9)->setMinute(0),
        'ends_at' => Carbon::today()->setHour(9)->setMinute(30),
        'status' => 'confirmed',
        'source' => 'phone',
        'notes' => 'Erstberatung - Rückenschmerzen',
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ],
    [
        'company_id' => $companyId,
        'branch_id' => $branch->id,
        'customer_id' => $customers[1]->id,
        'staff_id' => $staff->id,
        'service_id' => $service->id,
        'starts_at' => Carbon::today()->setHour(10)->setMinute(30),
        'ends_at' => Carbon::today()->setHour(11)->setMinute(0),
        'status' => 'completed',
        'source' => 'phone',
        'notes' => 'Nachuntersuchung',
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ],
    [
        'company_id' => $companyId,
        'branch_id' => $branch->id,
        'customer_id' => $customers[2]->id,
        'staff_id' => $staff->id,
        'service_id' => $service->id,
        'starts_at' => Carbon::today()->setHour(14)->setMinute(0),
        'ends_at' => Carbon::today()->setHour(14)->setMinute(30),
        'status' => 'scheduled',
        'source' => 'phone',
        'notes' => 'Beratung wegen Allergie',
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ],
    // Tomorrow appointments
    [
        'company_id' => $companyId,
        'branch_id' => $branch->id,
        'customer_id' => $customers[3]->id,
        'staff_id' => $staff->id,
        'service_id' => $service->id,
        'starts_at' => Carbon::tomorrow()->setHour(9)->setMinute(0),
        'ends_at' => Carbon::tomorrow()->setHour(9)->setMinute(30),
        'status' => 'pending',
        'source' => 'phone',
        'notes' => 'Ersttermin - Kopfschmerzen',
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ],
    [
        'company_id' => $companyId,
        'branch_id' => $branch->id,
        'customer_id' => $customers[0]->id,
        'staff_id' => $staff->id,
        'service_id' => $service->id,
        'starts_at' => Carbon::tomorrow()->setHour(15)->setMinute(0),
        'ends_at' => Carbon::tomorrow()->setHour(15)->setMinute(30),
        'status' => 'scheduled',
        'source' => 'phone',
        'notes' => 'Folgetermin',
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ],
    // Past appointments
    [
        'company_id' => $companyId,
        'branch_id' => $branch->id,
        'customer_id' => $customers[1]->id,
        'staff_id' => $staff->id,
        'service_id' => $service->id,
        'starts_at' => Carbon::yesterday()->setHour(10)->setMinute(0),
        'ends_at' => Carbon::yesterday()->setHour(10)->setMinute(30),
        'status' => 'completed',
        'source' => 'phone',
        'notes' => 'Routineuntersuchung',
        'created_at' => Carbon::yesterday(),
        'updated_at' => Carbon::yesterday()
    ],
    // Cancelled appointment
    [
        'company_id' => $companyId,
        'branch_id' => $branch->id,
        'customer_id' => $customers[2]->id,
        'staff_id' => $staff->id,
        'service_id' => $service->id,
        'starts_at' => Carbon::today()->setHour(16)->setMinute(0),
        'ends_at' => Carbon::today()->setHour(16)->setMinute(30),
        'status' => 'cancelled',
        'source' => 'phone',
        'notes' => 'Kunde hat abgesagt',
        'created_at' => Carbon::now()->subHours(2),
        'updated_at' => Carbon::now()->subHours(2)
    ]
];

foreach ($appointments as $data) {
    try {
        $appointment = DB::table('appointments')->insertGetId($data);
        echo "Created appointment ID: {$appointment} - {$data['notes']} - Status: {$data['status']}\n";
    } catch (\Exception $e) {
        echo "Error creating appointment: " . $e->getMessage() . "\n";
    }
}

DB::statement('SET @disable_tenant_scope = 0');

// Show summary
$count = Appointment::where('company_id', $companyId)->count();
echo "\nTotal appointments for company: $count\n";

// Show appointments by status
$statuses = Appointment::where('company_id', $companyId)
    ->select('status', DB::raw('count(*) as count'))
    ->groupBy('status')
    ->get();

echo "\nAppointments by status:\n";
foreach ($statuses as $status) {
    echo "  {$status->status}: {$status->count}\n";
}

echo "\nTest appointments created successfully!\n";