<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

echo "\n" . str_repeat('=', 60) . "\n";
echo "SIMPLE END-TO-END SETUP\n";
echo str_repeat('=', 60) . "\n\n";

// 1. Update branch with Retell agent
echo "1. Updating branch with Retell agent...\n";
DB::table('branches')
    ->where('id', '14b9996c-4ebe-11f0-b9c1-0ad77e7a9793')
    ->update([
        'retell_agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'updated_at' => now()
    ]);
echo "   ✅ Branch updated with Retell agent\n";

// 2. Update phone number
echo "\n2. Updating phone number mapping...\n";
DB::table('phone_numbers')
    ->where('number', '+493083793369')
    ->update([
        'retell_agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'branch_id' => '14b9996c-4ebe-11f0-b9c1-0ad77e7a9793',
        'is_primary' => true,
        'updated_at' => now()
    ]);
echo "   ✅ Phone number updated\n";

// 3. Check for services
echo "\n3. Checking services...\n";
$services = DB::table('services')
    ->where('company_id', 1)
    ->get();

if ($services->count() > 0) {
    echo "   ✅ Found " . $services->count() . " services:\n";
    foreach ($services as $service) {
        echo "   - {$service->name} ({$service->duration} min)\n";
    }
} else {
    echo "   ⚠️  No services found - creating default service...\n";
    DB::table('services')->insert([
        'company_id' => 1,
        'name' => 'Beratungsgespräch',
        'description' => 'Persönliches Beratungsgespräch',
        'duration' => 30,
        'price' => 0.00,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "   ✅ Default service created\n";
}

// 4. Check for staff
echo "\n4. Checking staff members...\n";
$staff = DB::table('staff')
    ->where('company_id', 1)
    ->get();

if ($staff->count() > 0) {
    echo "   ✅ Found " . $staff->count() . " staff members:\n";
    foreach ($staff as $member) {
        echo "   - {$member->first_name} {$member->last_name}\n";
    }
} else {
    echo "   ⚠️  No staff found - creating default staff member...\n";
    DB::table('staff')->insert([
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'company_id' => 1,
        'branch_id' => '14b9996c-4ebe-11f0-b9c1-0ad77e7a9793',
        'name' => 'Fabian Spitzer',
        'first_name' => 'Fabian',
        'last_name' => 'Spitzer',
        'email' => 'fabian@askproai.de',
        'phone' => '+493083793369',
        'role' => 'Rechtsberater',
        'is_active' => true,
        'is_bookable' => true,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "   ✅ Default staff member created\n";
}

// 5. Check Cal.com configuration
echo "\n5. Checking Cal.com configuration...\n";
$company = DB::table('companies')->find(1);
if ($company->calcom_api_key) {
    echo "   ✅ Cal.com API key is configured\n";
    
    // Check if branch has Cal.com event type
    $branch = DB::table('branches')->find('14b9996c-4ebe-11f0-b9c1-0ad77e7a9793');
    if ($branch->calcom_event_type_id) {
        echo "   ✅ Branch has Cal.com event type: {$branch->calcom_event_type_id}\n";
    } else {
        echo "   ⚠️  Branch missing Cal.com event type\n";
        echo "   Run the Event Type Import Wizard in admin panel\n";
    }
} else {
    echo "   ⚠️  No Cal.com API key configured\n";
}

// 6. Show current configuration
echo "\n" . str_repeat('=', 60) . "\n";
echo "CURRENT CONFIGURATION\n";
echo str_repeat('=', 60) . "\n";

$phoneNumber = DB::table('phone_numbers')->where('number', '+493083793369')->first();
$branch = DB::table('branches')->find($phoneNumber->branch_id);

echo "\n📞 Phone Number: +49 30 837 93 369\n";
echo "🏢 Branch: " . ($branch->name ?? 'Not assigned') . "\n";
echo "🤖 Retell Agent: agent_9a8202a740cd3120d96fcfda1e\n";
echo "🔗 Webhook: https://api.askproai.de/api/mcp/retell/webhook\n";

if ($branch && $branch->calcom_event_type_id) {
    echo "📅 Cal.com Event Type: {$branch->calcom_event_type_id}\n";
} else {
    echo "📅 Cal.com: ⚠️ Not configured (appointments will be saved locally)\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "TEST INSTRUCTIONS\n";
echo str_repeat('=', 60) . "\n";

echo "\n1. Call +49 30 837 93 369\n";
echo "2. The AI assistant will answer\n";
echo "3. Say: 'Ich möchte gerne einen Termin vereinbaren'\n";
echo "4. The AI will ask for:\n";
echo "   - Your name\n";
echo "   - Preferred date and time\n";
echo "   - Contact information\n";
echo "\n5. The system will:\n";
echo "   - Process the call through MCP\n";
echo "   - Create/update customer record\n";
if ($branch && $branch->calcom_event_type_id) {
    echo "   - Check availability in Cal.com\n";
    echo "   - Book appointment in Cal.com\n";
} else {
    echo "   - Book appointment in local database\n";
}
echo "   - Log the call in the database\n";

echo "\n✅ SYSTEM READY FOR END-TO-END TESTING!\n";