<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\CalcomEventType;
use App\Models\Staff;
use App\Models\Service;
use App\Services\CalcomV2Service;

echo "\n" . str_repeat('=', 60) . "\n";
echo "SETTING UP END-TO-END CONFIGURATION\n";
echo str_repeat('=', 60) . "\n\n";

$company = Company::find(1);

// Set company context for tenant scoping
app()->instance('company', $company);
auth()->loginUsingId(1); // Login as admin user

// 1. Get or create branch
echo "1. Setting up Branch...\n";
$branch = Branch::withoutGlobalScopes()
    ->where('company_id', $company->id)
    ->where('name', 'Hauptfiliale')
    ->first();

if (!$branch) {
    echo "   Creating Hauptfiliale branch...\n";
    $branch = Branch::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Hauptfiliale',
        'address' => 'Hauptstraße 1',
        'city' => 'Berlin',
        'zip_code' => '10115',
        'phone' => '+493083793369',
        'email' => 'info@askproai.de',
        'is_active' => true,
        'business_hours' => [
            'monday' => ['open' => '09:00', 'close' => '18:00'],
            'tuesday' => ['open' => '09:00', 'close' => '18:00'],
            'wednesday' => ['open' => '09:00', 'close' => '18:00'],
            'thursday' => ['open' => '09:00', 'close' => '18:00'],
            'friday' => ['open' => '09:00', 'close' => '17:00'],
            'saturday' => ['closed' => true],
            'sunday' => ['closed' => true]
        ]
    ]);
    echo "   ✅ Branch created\n";
} else {
    echo "   ✅ Branch exists: {$branch->name}\n";
}

// 2. Get Cal.com event types
echo "\n2. Getting Cal.com event types...\n";
if ($company->calcom_api_key) {
    try {
        $calcomService = new CalcomV2Service(decrypt($company->calcom_api_key));
        $eventTypes = $calcomService->getEventTypes();
        
        echo "   Found " . count($eventTypes) . " event types\n";
        
        // Find a suitable event type
        $defaultEventType = null;
        foreach ($eventTypes as $eventType) {
            echo "   - {$eventType['title']} (ID: {$eventType['id']}, Slug: {$eventType['slug']})\n";
            
            // Use the first active event type
            if (!$defaultEventType && !$eventType['hidden']) {
                $defaultEventType = $eventType;
            }
        }
        
        if ($defaultEventType) {
            echo "\n   Using event type: {$defaultEventType['title']}\n";
            
            // Update branch with Cal.com event type
            $branch->update([
                'calcom_event_type_id' => $defaultEventType['id'],
                'retell_agent_id' => 'agent_9a8202a740cd3120d96fcfda1e'
            ]);
            echo "   ✅ Branch updated with Cal.com event type\n";
            
            // Store in our database
            $calcomEventType = CalcomEventType::withoutGlobalScopes()->updateOrCreate(
                ['calcom_id' => $defaultEventType['id']],
                [
                    'company_id' => $company->id,
                    'title' => $defaultEventType['title'],
                    'slug' => $defaultEventType['slug'],
                    'length' => $defaultEventType['length'],
                    'description' => $defaultEventType['description'] ?? '',
                    'is_active' => !$defaultEventType['hidden'],
                    'metadata' => $defaultEventType
                ]
            );
            echo "   ✅ Cal.com event type stored in database\n";
        } else {
            echo "   ⚠️  No suitable Cal.com event type found\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ Cal.com error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ⚠️  No Cal.com API key configured\n";
}

// 3. Create a default service
echo "\n3. Setting up default service...\n";
$service = Service::withoutGlobalScopes()
    ->where('company_id', $company->id)
    ->where('name', 'Beratungsgespräch')
    ->first();

if (!$service) {
    $service = Service::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Beratungsgespräch',
        'description' => 'Persönliches Beratungsgespräch',
        'duration' => 30,
        'price' => 0,
        'is_active' => true,
        'buffer_time' => 0,
        'max_advance_booking' => 365,
        'min_advance_booking' => 1
    ]);
    echo "   ✅ Service created: {$service->name}\n";
} else {
    echo "   ✅ Service exists: {$service->name}\n";
}

// Assign service to branch
$branch->services()->syncWithoutDetaching([$service->id]);
echo "   ✅ Service assigned to branch\n";

// 4. Create a default staff member
echo "\n4. Setting up staff member...\n";
$staff = Staff::withoutGlobalScopes()
    ->where('company_id', $company->id)
    ->where('email', 'fabian@askproai.de')
    ->first();

if (!$staff) {
    $staff = Staff::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'first_name' => 'Fabian',
        'last_name' => 'Spitzer',
        'email' => 'fabian@askproai.de',
        'phone' => '+493083793369',
        'position' => 'Rechtsberater',
        'is_active' => true,
        'can_book_appointments' => true
    ]);
    echo "   ✅ Staff member created: {$staff->first_name} {$staff->last_name}\n";
} else {
    echo "   ✅ Staff member exists: {$staff->first_name} {$staff->last_name}\n";
}

// Assign service to staff
$staff->services()->syncWithoutDetaching([$service->id]);
echo "   ✅ Service assigned to staff\n";

// 5. Update phone number record
echo "\n5. Updating phone number record...\n";
$phoneRecord = PhoneNumber::withoutGlobalScopes()
    ->where('number', '+493083793369')
    ->first();

if ($phoneRecord) {
    $phoneRecord->update([
        'branch_id' => $branch->id,
        'retell_agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'is_primary' => true,
        'metadata' => array_merge($phoneRecord->metadata ?? [], [
            'agent_name' => 'Online: Assistent für Fabian Spitzer Rechtliches',
            'branch_name' => $branch->name,
            'last_updated' => now()->toIso8601String()
        ])
    ]);
    echo "   ✅ Phone number updated\n";
} else {
    echo "   ⚠️  Phone number not found\n";
}

// 6. Test the complete flow
echo "\n6. Testing complete flow...\n";
echo "   Phone: +493083793369\n";
echo "   → Branch: {$branch->name}\n";
echo "   → Service: {$service->name}\n";
echo "   → Staff: {$staff->first_name} {$staff->last_name}\n";
if ($branch->calcom_event_type_id) {
    echo "   → Cal.com Event Type ID: {$branch->calcom_event_type_id}\n";
} else {
    echo "   → Cal.com Event Type: ⚠️ Not configured\n";
}
echo "   → Retell Agent: agent_9a8202a740cd3120d96fcfda1e\n";

echo "\n" . str_repeat('=', 60) . "\n";
echo "END-TO-END SETUP COMPLETE\n";
echo str_repeat('=', 60) . "\n";

echo "\n✅ System is ready for end-to-end testing!\n\n";
echo "Test flow:\n";
echo "1. Call +49 30 837 93 369\n";
echo "2. Retell AI agent will answer\n";
echo "3. Ask for an appointment\n";
echo "4. The system will:\n";
echo "   - Process through MCP webhook\n";
echo "   - Create customer record\n";
if ($branch->calcom_event_type_id) {
    echo "   - Check availability in Cal.com\n";
    echo "   - Book appointment in Cal.com\n";
} else {
    echo "   - Book appointment in local database\n";
}
echo "   - Send confirmation\n";

echo "\n📞 Ready to receive calls!\n";