<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;
use App\Models\CalcomEventType;
use App\Models\Staff;
use App\Services\EventTypeNameParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

echo "=== Testing Complete Import Flow ===\n\n";

// Setup
$company = Company::find(85);
$branch = \App\Models\Branch::withoutGlobalScopes()
    ->where('company_id', $company->id)
    ->where('is_active', 1)
    ->first();

echo "Company: {$company->name}\n";
echo "Branch: {$branch->name}\n\n";

// Simulate import of one event type with staff mapping
$apiKey = decrypt($company->calcom_api_key);
$response = \Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
    'Content-Type' => 'application/json',
])->get("https://api.cal.com/v2/event-types/2026301"); // 15 Minuten Termin

if (!$response->successful()) {
    echo "Failed to load event type\n";
    exit(1);
}

$data = $response->json();
$eventType = $data['data']['eventType'];

echo "Event Type: {$eventType['title']}\n";
echo "Cal.com ID: {$eventType['id']}\n";
echo "Duration: {$eventType['length']} minutes\n\n";

// Check users
echo "Cal.com Users:\n";
$calcomUsers = [];
if (isset($eventType['users']) && is_array($eventType['users'])) {
    foreach ($eventType['users'] as $user) {
        $calcomUsers[] = $user;
        echo "  - {$user['name']} (ID: {$user['id']}, Email: {$user['email']})\n";
    }
}

// Create Event Type in database
echo "\nCreating Event Type in database...\n";

$nameParser = new EventTypeNameParser();
$eventTypeName = $nameParser->generateEventTypeName($branch, $eventType['title']);

// Check if event type already exists
$existing = DB::table('calcom_event_types')
    ->where('branch_id', $branch->id)
    ->where('calcom_event_type_id', $eventType['id'])
    ->first();

if ($existing) {
    $dbEventTypeId = $existing->id;
    DB::table('calcom_event_types')
        ->where('id', $dbEventTypeId)
        ->update([
            'name' => $eventTypeName,
            'slug' => Str::slug($eventType['title']),
            'description' => $eventType['description'] ?? null,
            'duration_minutes' => $eventType['length'],
            'is_active' => true,
            'last_synced_at' => now()
        ]);
} else {
    $dbEventTypeId = DB::table('calcom_event_types')->insertGetId([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'calcom_event_type_id' => $eventType['id'],
        'calcom_numeric_event_type_id' => $eventType['id'],
        'name' => $eventTypeName,
        'slug' => Str::slug($eventType['title']),
        'description' => $eventType['description'] ?? null,
        'duration_minutes' => $eventType['length'],
        'is_team_event' => $eventType['schedulingType'] === 'COLLECTIVE' ? 1 : 0,
        'requires_confirmation' => $eventType['requiresConfirmation'] ?? false ? 1 : 0,
        'booking_limits' => json_encode($eventType['bookingLimits'] ?? []),
        'metadata' => json_encode([
            'imported_at' => now(),
            'imported_by' => 'test-script',
            'original_name' => $eventType['title']
        ]),
        'is_active' => 1,
        'last_synced_at' => now(),
        'created_at' => now(),
        'updated_at' => now()
    ]);
}

$dbEventType = DB::table('calcom_event_types')->where('id', $dbEventTypeId)->first();

echo "Created Event Type: {$dbEventType->name} (ID: {$dbEventType->id})\n\n";

// Staff Mapping
echo "Processing Staff Mappings...\n";

// Clear existing assignments
DB::table('staff_event_types')
    ->where('event_type_id', $dbEventType->id)
    ->delete();

foreach ($calcomUsers as $calcomUser) {
    echo "\nProcessing Cal.com User: {$calcomUser['name']}\n";
    
    // Try to find existing staff
    $staff = null;
    
    // First try by email
    if ($calcomUser['email']) {
        $staff = DB::table('staff')
            ->where('company_id', $company->id)
            ->where('email', $calcomUser['email'])
            ->first();
    }
    
    // Then try by name
    if (!$staff) {
        $staff = DB::table('staff')
            ->where('company_id', $company->id)
            ->where('name', 'LIKE', '%' . $calcomUser['name'] . '%')
            ->first();
    }
    
    if ($staff) {
        echo "  Found existing staff: {$staff->name}\n";
        
        // Create assignment
        DB::table('staff_event_types')->insert([
            'staff_id' => $staff->id,
            'event_type_id' => $dbEventType->id,
            'calcom_user_id' => $calcomUser['id'],
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "  ✓ Created assignment\n";
    } else {
        echo "  No matching staff found - would create new staff member\n";
        
        // In real import, we would create new staff here if requested
        // For testing, just show what would happen
        echo "  Would create: {$calcomUser['name']} with email {$calcomUser['email']}\n";
    }
}

// Show final results
echo "\n\n=== Import Results ===\n";

$assignments = DB::table('staff_event_types')
    ->join('staff', 'staff.id', '=', 'staff_event_types.staff_id')
    ->where('event_type_id', $dbEventType->id)
    ->select('staff.name', 'staff_event_types.calcom_user_id')
    ->get();

echo "Event Type: {$dbEventType->name}\n";
echo "Staff Assignments:\n";
foreach ($assignments as $assignment) {
    echo "  - {$assignment->name} (Cal.com User: {$assignment->calcom_user_id})\n";
}

echo "\n=== Test Complete ===\n";