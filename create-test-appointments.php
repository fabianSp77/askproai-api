<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Branch;
use Carbon\Carbon;
use App\Scopes\TenantScope;

// Company ID 1
$companyId = 1;

// Set company context
app()->instance('current_company_id', $companyId);

// Get first branch for company 1
$branch = Branch::withoutGlobalScope(TenantScope::class)->where('company_id', $companyId)->first();
if (!$branch) {
    $branch = Branch::create([
        'company_id' => $companyId,
        'name' => 'Hauptfiliale',
        'address' => 'Musterstraße 1, 10115 Berlin',
        'phone' => '+49 30 12345678',
        'email' => 'hauptfiliale@askproai.de'
    ]);
}

// Get or create a service
$service = Service::where('company_id', $companyId)->first();
if (!$service) {
    $service = Service::create([
        'company_id' => $companyId,
        'name' => 'Beratungsgespräch',
        'duration' => 60,
        'price' => 120.00,
        'description' => 'Persönliches Beratungsgespräch'
    ]);
}

// Get or create staff
$staff = Staff::where('company_id', $companyId)->first();
if (!$staff) {
    $staff = Staff::create([
        'company_id' => $companyId,
        'branch_id' => $branch->id,
        'name' => 'Dr. Max Mustermann',
        'email' => 'max.mustermann@askproai.de',
        'phone' => '+49 30 12345679'
    ]);
}

// Get or create customers
$customers = [];
$customerData = [
    ['name' => 'Anna Schmidt', 'phone' => '+491701234567', 'email' => 'anna.schmidt@example.com'],
    ['name' => 'Peter Müller', 'phone' => '+491701234568', 'email' => 'peter.mueller@example.com'],
    ['name' => 'Julia Weber', 'phone' => '+491701234569', 'email' => 'julia.weber@example.com'],
    ['name' => 'Thomas Fischer', 'phone' => '+491701234570', 'email' => 'thomas.fischer@example.com'],
    ['name' => 'Sarah Wagner', 'phone' => '+491701234571', 'email' => 'sarah.wagner@example.com']
];

foreach ($customerData as $data) {
    $customer = Customer::where('company_id', $companyId)
        ->where('phone', $data['phone'])
        ->first();
    
    if (!$customer) {
        $customer = Customer::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email']
        ]);
    }
    
    $customers[] = $customer;
}

// Create appointments
$appointmentTimes = [
    ['start' => Carbon::today()->setHour(9), 'status' => 'confirmed'],
    ['start' => Carbon::today()->setHour(11), 'status' => 'confirmed'],
    ['start' => Carbon::today()->setHour(14), 'status' => 'pending'],
    ['start' => Carbon::tomorrow()->setHour(10), 'status' => 'confirmed'],
    ['start' => Carbon::tomorrow()->setHour(15), 'status' => 'pending'],
    ['start' => Carbon::today()->subDays(2)->setHour(13), 'status' => 'completed'],
    ['start' => Carbon::today()->addDays(3)->setHour(16), 'status' => 'scheduled'],
    ['start' => Carbon::today()->addDays(5)->setHour(9), 'status' => 'scheduled'],
];

foreach ($appointmentTimes as $index => $time) {
    $customer = $customers[$index % count($customers)];
    $start = $time['start'];
    $end = $start->copy()->addMinutes($service->duration);
    
    $appointment = Appointment::create([
        'company_id' => $companyId,
        'branch_id' => $branch->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'staff_id' => $staff->id,
        'starts_at' => $start,
        'ends_at' => $end,
        'status' => $time['status'],
        'notes' => 'Testtermin erstellt für Demo'
    ]);
    
    echo "Created appointment: {$appointment->id} - {$customer->name} - {$start->format('d.m.Y H:i')} - Status: {$time['status']}\n";
}

echo "\nTest appointments created successfully!\n";