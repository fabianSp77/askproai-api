<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Appointment;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Company;
use Carbon\Carbon;

// Get companies with different types
$companies = [
    ['id' => 1, 'name' => 'Krückeberg Servicegruppe', 'type' => 'mixed'],
    ['id' => 15, 'name' => 'AskProAI', 'type' => 'tech'],
    ['id' => 16, 'name' => 'Demo GmbH', 'type' => 'demo'],
    ['id' => 18, 'name' => 'Friseur Schmidt', 'type' => 'inbound'],
    ['id' => 21, 'name' => 'Salon Schönheit', 'type' => 'inbound'],
];

$statuses = ['completed', 'no_show', 'cancelled', 'scheduled'];
$callStatuses = ['ended', 'no-answer', 'busy', 'failed'];

echo "Creating test data for August 2025...\n\n";

foreach ($companies as $company) {
    $companyModel = Company::find($company['id']);
    if (!$companyModel) continue;
    
    echo "Processing {$company['name']}...\n";
    
    // Get or create a service for this company
    $service = Service::firstOrCreate(
        ['company_id' => $company['id'], 'name' => 'Standard Service'],
        [
            'price' => rand(30, 150),
            'default_duration_minutes' => rand(30, 90),
            'active' => true,
        ]
    );
    
    // Get existing staff or skip if none exists
    $staff = Staff::where('company_id', $company['id'])->first();
    if (!$staff) {
        // Try to get branch for this company
        $branch = \App\Models\Branch::where('company_id', $company['id'])->first();
        if (!$branch) {
            // Create a default branch
            $branch = \App\Models\Branch::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'company_id' => $company['id'],
                'name' => 'Hauptfiliale',
                'address' => 'Test Street 1',
                'phone' => '+49123456789',
                'is_active' => true,
            ]);
        }
        
        $staff = Staff::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'company_id' => $company['id'],
            'branch_id' => $branch->id,
            'name' => 'Test Staff ' . $company['id'],
            'email' => 'staff' . $company['id'] . '@test.com',
        ]);
    }
    
    // Create appointments for August 2025
    $appointmentsToCreate = rand(10, 30);
    for ($i = 0; $i < $appointmentsToCreate; $i++) {
        $day = rand(1, 31);
        $hour = rand(9, 18);
        $startsAt = Carbon::create(2025, 8, $day, $hour, 0, 0);
        
        // Create or get customer
        $customer = Customer::firstOrCreate(
            [
                'company_id' => $company['id'],
                'email' => 'customer' . $i . '_company' . $company['id'] . '@test.com'
            ],
            [
                'name' => 'Test Customer ' . $i,
                'phone' => '+49 30 ' . rand(10000000, 99999999), // Valid German format
            ]
        );
        
        Appointment::create([
            'company_id' => $company['id'],
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes($service->default_duration_minutes),
            'status' => $statuses[array_rand($statuses)],
            'notes' => 'Test appointment for August 2025',
        ]);
    }
    echo "  Created $appointmentsToCreate appointments\n";
    
    // Create calls based on company type
    if (in_array($company['type'], ['inbound', 'mixed'])) {
        // Inbound calls (Friseure, Restaurants)
        $inboundCallsToCreate = rand(20, 50);
        for ($i = 0; $i < $inboundCallsToCreate; $i++) {
            $day = rand(1, 31);
            $hour = rand(8, 20);
            $createdAt = Carbon::create(2025, 8, $day, $hour, rand(0, 59), rand(0, 59));
            
            Call::create([
                'company_id' => $company['id'],
                'direction' => 'inbound',
                'call_status' => $callStatuses[array_rand($callStatuses)],
                'duration_minutes' => rand(1, 15),
                'from_number' => '+49 30 ' . rand(10000000, 99999999),
                'to_number' => '+49 30 ' . rand(10000000, 99999999),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
        echo "  Created $inboundCallsToCreate inbound calls\n";
    }
    
    if (in_array($company['type'], ['outbound', 'mixed', 'tech'])) {
        // Outbound calls (Versicherungen, Vertrieb)
        $outboundCallsToCreate = rand(30, 60);
        for ($i = 0; $i < $outboundCallsToCreate; $i++) {
            $day = rand(1, 31);
            $hour = rand(9, 18);
            $createdAt = Carbon::create(2025, 8, $day, $hour, rand(0, 59), rand(0, 59));
            
            $callStatus = $callStatuses[array_rand($callStatuses)];
            $leadStatus = null;
            if ($callStatus === 'ended') {
                $leadStatuses = ['qualified', 'not_qualified', 'appointment_set', 'callback_scheduled'];
                $leadStatus = $leadStatuses[array_rand($leadStatuses)];
            }
            
            Call::create([
                'company_id' => $company['id'],
                'direction' => 'outbound',
                'call_status' => $callStatus,
                'lead_status' => $leadStatus,
                'duration_minutes' => rand(2, 20),
                'from_number' => '+49 30 ' . rand(10000000, 99999999),
                'to_number' => '+49 30 ' . rand(10000000, 99999999),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
        echo "  Created $outboundCallsToCreate outbound calls\n";
    }
}

echo "\n✅ Test data creation completed!\n";
echo "Please refresh the Analytics Dashboard to see the new data.\n";