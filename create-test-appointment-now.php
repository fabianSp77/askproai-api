<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Branch;
use Carbon\Carbon;

// Disable tenant scope temporarily
\App\Scopes\TenantScope::$disabled = true;

try {
    // Get first company, branch, customer, staff, service
    $company = \App\Models\Company::first();
    $branch = Branch::where('company_id', $company->id)->first();
    $customer = Customer::where('company_id', $company->id)->first();
    $staff = Staff::where('company_id', $company->id)->first();
    $service = Service::where('company_id', $company->id)->first();
    
    if (!$branch || !$customer || !$staff || !$service) {
        echo "Missing required data. Creating test data...\n";
        
        // Create branch if missing
        if (!$branch) {
            $branch = Branch::create([
                'company_id' => $company->id,
                'name' => 'Test Branch',
                'address' => 'Test Address',
                'city' => 'Test City',
                'postal_code' => '12345',
                'country' => 'DE',
                'phone' => '+491234567890',
                'email' => 'branch@test.com',
                'is_active' => true
            ]);
        }
        
        // Create customer if missing
        if (!$customer) {
            $customer = Customer::create([
                'company_id' => $company->id,
                'first_name' => 'Test',
                'last_name' => 'Customer',
                'email' => 'test@customer.com',
                'phone' => '+491234567890'
            ]);
        }
        
        // Create staff if missing
        if (!$staff) {
            $staff = Staff::create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'first_name' => 'Test',
                'last_name' => 'Staff',
                'email' => 'staff@test.com',
                'is_active' => true
            ]);
        }
        
        // Create service if missing
        if (!$service) {
            $service = Service::create([
                'company_id' => $company->id,
                'name' => 'Test Service',
                'duration' => 60,
                'price' => 50.00,
                'is_active' => true
            ]);
        }
    }
    
    // Create appointment for today
    $appointment = Appointment::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'customer_id' => $customer->id,
        'staff_id' => $staff->id,
        'service_id' => $service->id,
        'starts_at' => Carbon::now()->addHours(2),
        'ends_at' => Carbon::now()->addHours(3),
        'status' => 'confirmed',
        'source' => 'manual',
        'booking_type' => 'single',
        'payload' => json_encode([
            'notes' => 'Test appointment created for debugging',
            'created_by' => 'Debug Script'
        ])
    ]);
    
    echo "✅ Test appointment created successfully!\n";
    echo "ID: {$appointment->id}\n";
    echo "Company: {$company->name} (ID: {$company->id})\n";
    echo "Branch: {$branch->name}\n";
    echo "Customer: {$customer->first_name} {$customer->last_name}\n";
    echo "Staff: {$staff->first_name} {$staff->last_name}\n";
    echo "Service: {$service->name}\n";
    echo "Time: {$appointment->starts_at->format('d.m.Y H:i')}\n";
    
    // Check if visible with scope
    \App\Scopes\TenantScope::$disabled = false;
    $count = Appointment::count();
    echo "\nAppointments visible with TenantScope: $count\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}