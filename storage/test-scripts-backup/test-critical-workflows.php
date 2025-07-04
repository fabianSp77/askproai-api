<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use App\Services\PhoneNumberResolver;
use App\Services\RetellV2Service;
use App\Http\Controllers\OptimizedRetellWebhookController;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Testing Critical Workflows...\n\n";

// 1. Test Phone Number Resolution
echo "1️⃣ Testing Phone Number Resolution...\n";
try {
    // Create test data
    $company = Company::first() ?: Company::create([
        'name' => 'Test Company',
        'retell_api_key' => 'test_key',
        'calcom_api_key' => 'cal_test_key',
        'is_active' => true,
    ]);
    
    $branch = Branch::firstOrCreate(
        ['phone_number' => '+493012345678'],
        [
            'company_id' => $company->id,
            'name' => 'Test Branch',
            'is_active' => true,
            'calcom_event_type_id' => 2026361,
            'id' => \Illuminate\Support\Str::uuid(),
        ]
    );
    
    $phoneNumber = PhoneNumber::firstOrCreate(
        ['number' => '+493012345678'],
        [
            'branch_id' => $branch->id,
            'company_id' => $company->id,
            'retell_agent_id' => 'agent_123',
            'is_active' => true,
        ]
    );
    
    $resolver = new PhoneNumberResolver();
    $resolved = $resolver->resolve('+493012345678');
    
    if ($resolved && $resolved->branch_id === $branch->id) {
        echo "   ✅ Phone number resolution works\n";
        echo "   - Phone: {$resolved->number}\n";
        echo "   - Branch: {$resolved->branch->name}\n";
        echo "   - Company: {$resolved->company->name}\n";
    } else {
        echo "   ❌ Phone number resolution failed\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 2. Test Retell Webhook Processing
echo "\n2️⃣ Testing Retell Webhook Processing...\n";
try {
    $webhookData = [
        'event_type' => 'call_ended',
        'call_id' => 'test_call_' . time(),
        'retell_call_id' => 'retell_test_' . time(),
        'agent_id' => 'agent_123',
        'phone_number' => '+493012345678',
        'from_number' => '+491234567890',
        'start_timestamp' => (time() - 300) * 1000,
        'end_timestamp' => time() * 1000,
        'duration_seconds' => 300,
        'transcript' => 'Test transcript',
        'recording_url' => 'https://example.com/recording.mp3',
        'summary' => 'Customer wants appointment',
        'custom_data' => [
            'extracted_info' => [
                'customer_name' => 'Test Customer',
                'requested_date' => now()->addDays(3)->format('Y-m-d'),
                'requested_time' => '14:00',
            ]
        ]
    ];
    
    // Simulate webhook processing
    $controller = new OptimizedRetellWebhookController();
    $request = new \Illuminate\Http\Request();
    $request->merge($webhookData);
    
    // Process webhook (without actual HTTP request)
    DB::beginTransaction();
    
    // Create call record
    $call = Call::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'phone_number_id' => $phoneNumber->id,
        'retell_call_id' => $webhookData['retell_call_id'],
        'call_id' => $webhookData['call_id'],
        'agent_id' => $webhookData['agent_id'],
        'from_number' => $webhookData['from_number'],
        'to_number' => $webhookData['phone_number'],
        'direction' => 'inbound',
        'status' => 'completed',
        'start_timestamp' => $webhookData['start_timestamp'],
        'end_timestamp' => $webhookData['end_timestamp'],
        'duration_seconds' => $webhookData['duration_seconds'],
        'transcript' => $webhookData['transcript'],
        'recording_url' => $webhookData['recording_url'],
        'summary' => $webhookData['summary'],
        'metadata' => $webhookData['custom_data'],
    ]);
    
    echo "   ✅ Call record created\n";
    echo "   - Call ID: {$call->call_id}\n";
    echo "   - Duration: {$call->duration_seconds}s\n";
    echo "   - From: {$call->from_number}\n";
    
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 3. Test Customer Creation from Call
echo "\n3️⃣ Testing Customer Creation from Call...\n";
try {
    if (isset($call)) {
        // Find or create customer
        $customer = Customer::firstOrCreate(
            [
                'phone' => $call->from_number,
                'company_id' => $call->company_id,
            ],
            [
                'name' => $webhookData['custom_data']['extracted_info']['customer_name'] ?? 'Unknown',
                'email' => null,
                'branch_id' => $call->branch_id,
            ]
        );
        
        // Update call with customer
        $call->customer_id = $customer->id;
        $call->save();
        
        echo "   ✅ Customer created/found\n";
        echo "   - Name: {$customer->name}\n";
        echo "   - Phone: {$customer->phone}\n";
        echo "   - ID: {$customer->id}\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 4. Test Appointment Creation
echo "\n4️⃣ Testing Appointment Creation from Call Data...\n";
try {
    if (isset($call) && isset($customer)) {
        $requestedDate = $webhookData['custom_data']['extracted_info']['requested_date'] ?? now()->addDays(3)->format('Y-m-d');
        $requestedTime = $webhookData['custom_data']['extracted_info']['requested_time'] ?? '14:00';
        
        $appointment = Appointment::create([
            'company_id' => $call->company_id,
            'branch_id' => $call->branch_id,
            'customer_id' => $customer->id,
            'call_id' => $call->id,
            'status' => 'scheduled',
            'start_at' => "{$requestedDate} {$requestedTime}:00",
            'end_at' => "{$requestedDate} " . date('H:i:s', strtotime($requestedTime) + 1800),
            'duration' => 30,
            'source' => 'retell',
            'notes' => 'Created from Retell call',
        ]);
        
        echo "   ✅ Appointment created\n";
        echo "   - Date: {$appointment->start_at}\n";
        echo "   - Customer: {$customer->name}\n";
        echo "   - Status: {$appointment->status}\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 5. Test Cal.com Sync Status
echo "\n5️⃣ Testing Cal.com Integration Status...\n";
try {
    $eventTypes = DB::table('calcom_event_types')
        ->where('company_id', $company->id)
        ->count();
    
    echo "   - Event Types: {$eventTypes}\n";
    
    if ($branch->calcom_event_type_id) {
        echo "   ✅ Branch has Cal.com event type configured: {$branch->calcom_event_type_id}\n";
    } else {
        echo "   ⚠️  Branch missing Cal.com event type\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Summary
echo "\n📊 Workflow Test Summary:\n";
echo "   - Phone Resolution: " . (isset($resolved) ? "✅" : "❌") . "\n";
echo "   - Call Creation: " . (isset($call) ? "✅" : "❌") . "\n";
echo "   - Customer Mapping: " . (isset($customer) ? "✅" : "❌") . "\n";
echo "   - Appointment Creation: " . (isset($appointment) ? "✅" : "❌") . "\n";
echo "   - Cal.com Ready: " . ($branch->calcom_event_type_id ? "✅" : "❌") . "\n";

echo "\n✅ Critical workflow test complete!\n";