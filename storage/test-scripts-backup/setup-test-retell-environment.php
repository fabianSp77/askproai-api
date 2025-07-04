#!/usr/bin/env php
<?php
/**
 * Setup Test Environment for Retell Integration
 * 
 * Creates necessary test data for testing the complete flow
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\Service;
use App\Models\CalcomEventType;
use Illuminate\Support\Facades\DB;

echo "\n========================================\n";
echo "RETELL TEST ENVIRONMENT SETUP\n";
echo "========================================\n";

// Start transaction
DB::beginTransaction();

try {
    // Disable tenant scope for setup
    app()->bind('tenant.company_id', function() {
        return null;
    });
    // 1. Create or find test company
    $company = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('slug', 'test-retell')
        ->first();
        
    if (!$company) {
        $company = Company::create([
            'name' => 'Test Retell Company',
            'slug' => 'test-retell',
            'email' => 'test@retell.local',
            'phone_number' => '+49 30 837 93 369',
            'is_active' => true,
            'settings' => [
                'timezone' => 'Europe/Berlin',
                'locale' => 'de'
            ]
        ]);
        echo "✅ Created test company: {$company->name}\n";
    } else {
        echo "✅ Using existing test company: {$company->name}\n";
    }
    
    // 2. Create or find test branch
    $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->where('slug', 'test-berlin')
        ->first();
        
    if (!$branch) {
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Test Berlin Branch',
            'slug' => 'test-berlin',
            'phone_number' => '+49 30 837 93 369',
            'email' => 'berlin@test.local',
            'is_active' => true,
            'address' => [
                'street' => 'Teststraße 123',
                'city' => 'Berlin',
                'zip' => '10115',
                'country' => 'DE'
            ]
        ]);
        echo "✅ Created test branch: {$branch->name}\n";
    } else {
        echo "✅ Using existing test branch: {$branch->name}\n";
    }
    
    // 3. Create or find phone number
    $phoneNumber = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('number', '+49 30 837 93 369')
        ->first();
        
    if (!$phoneNumber) {
        $phoneNumber = PhoneNumber::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'number' => '+49 30 837 93 369',
            'type' => 'main',
            'is_active' => true,
            'retell_agent_id' => 'agent_test123',
            'description' => 'Test Retell Phone Number'
        ]);
        echo "✅ Created phone number: {$phoneNumber->number}\n";
    } else {
        echo "✅ Using existing phone number: {$phoneNumber->number}\n";
    }
    
    // 4. Create test service
    $service = Service::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->where('slug', 'beratungsgesprach')
        ->first();
        
    if (!$service) {
        $service = Service::create([
            'company_id' => $company->id,
            'name' => 'Beratungsgespräch',
            'slug' => 'beratungsgesprach',
            'duration' => 30,
            'price' => 50.00,
            'is_active' => true,
            'description' => 'Test consultation service'
        ]);
        echo "✅ Created test service: {$service->name}\n";
    } else {
        echo "✅ Using existing test service: {$service->name}\n";
    }
    
    // 5. Create Cal.com event type
    $eventType = CalcomEventType::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->where('slug', 'beratung')
        ->first();
        
    if (!$eventType) {
        $eventType = CalcomEventType::create([
            'company_id' => $company->id,
            'calcom_id' => 999999, // Test ID
            'title' => 'Beratungsgespräch',
            'slug' => 'beratung',
            'length' => 30,
            'is_active' => true
        ]);
        echo "✅ Created test event type: {$eventType->title}\n";
    } else {
        echo "✅ Using existing event type: {$eventType->title}\n";
    }
    
    // Update branch with event type
    if (!$branch->calcom_event_type_id) {
        $branch->update(['calcom_event_type_id' => $eventType->calcom_id]);
        echo "✅ Linked event type to branch\n";
    }
    
    DB::commit();
    
    echo "\n========================================\n";
    echo "TEST ENVIRONMENT READY\n";
    echo "========================================\n";
    echo "\nTest Data Summary:\n";
    echo "- Company ID: {$company->id}\n";
    echo "- Branch ID: {$branch->id}\n";
    echo "- Phone Number: {$phoneNumber->number}\n";
    echo "- Service: {$service->name}\n";
    echo "- Event Type ID: {$eventType->calcom_id}\n";
    echo "\n✅ You can now run: php test-retell-booking-flow.php\n\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Setup failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}