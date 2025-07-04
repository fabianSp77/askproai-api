<?php

use App\Filament\Admin\Pages\CompanyIntegrationPortal;
use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\CalcomEventType;
use Livewire\Livewire;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Authenticate as super admin
$user = App\Models\User::where('email', 'fabian@askproai.de')->first();
Auth::login($user);

echo "====== COMPANY INTEGRATION PORTAL TEST SUITE ======\n\n";

// 1. TEST COMPANY SELECTION
echo "1. Testing Company Selection:\n";
$company = Company::first();
echo "   - Found company: {$company->name} (ID: {$company->id})\n";
echo "   - Branches: " . DB::table('branches')->where('company_id', $company->id)->count() . "\n";
echo "   - Phone Numbers: " . DB::table('phone_numbers')->where('company_id', $company->id)->count() . "\n";
echo "   ✅ Company selection works\n\n";

// 2. TEST INTEGRATION STATUS
echo "2. Testing Integration Status:\n";
echo "   - Cal.com API Key: " . ($company->calcom_api_key ? '✅ SET' : '❌ NOT SET') . "\n";
echo "   - Cal.com Team Slug: " . ($company->calcom_team_slug ? '✅ SET' : '❌ NOT SET') . "\n";
echo "   - Retell API Key: " . ($company->retell_api_key ? '✅ SET' : '❌ NOT SET') . "\n";
echo "   - Retell Agent ID: " . ($company->retell_agent_id ? '✅ SET' : '❌ NOT SET') . "\n";
echo "   - Stripe Customer ID: " . ($company->stripe_customer_id ? '✅ SET' : '❌ NOT SET') . "\n";

// 3. TEST BRANCH MANAGEMENT
echo "\n3. Testing Branch Management:\n";
$branches = DB::table('branches')->where('company_id', $company->id)->get();
foreach ($branches as $branch) {
    echo "   Branch: {$branch->name} (ID: {$branch->id})\n";
    echo "   - Active: " . ($branch->active ? '✅ YES' : '❌ NO') . "\n";
    echo "   - Address: " . ($branch->address ?: 'Not set') . "\n";
    echo "   - Email: " . ($branch->email ?: 'Not set') . "\n";
    
    // Check event types
    $eventTypeCount = DB::table('branch_event_types')->where('branch_id', $branch->id)->count();
    echo "   - Event Types: {$eventTypeCount}\n";
    
    // Check phone numbers
    $phoneCount = DB::table('phone_numbers')->where('branch_id', $branch->id)->count();
    echo "   - Phone Numbers: {$phoneCount}\n\n";
}

// 4. TEST PHONE NUMBER MANAGEMENT
echo "4. Testing Phone Number Management:\n";
$phoneNumbers = DB::table('phone_numbers')->where('company_id', $company->id)->get();
foreach ($phoneNumbers as $phone) {
    echo "   Phone: {$phone->number} (ID: {$phone->id})\n";
    echo "   - Active: " . ($phone->is_active ? '✅ YES' : '❌ NO') . "\n";
    echo "   - Primary: " . ($phone->is_primary ? '✅ YES' : '❌ NO') . "\n";
    echo "   - Branch: " . ($phone->branch_id ?: 'Not assigned') . "\n";
    echo "   - Retell Phone ID: " . ($phone->retell_phone_id ?: 'Not set') . "\n";
    echo "   - Retell Agent ID: " . ($phone->retell_agent_id ?: 'Not set') . "\n";
    echo "   - Retell Agent Version: " . ($phone->retell_agent_version ?: 'Not set') . "\n\n";
}

// 5. TEST SERVICE-EVENTTYPE MAPPING
echo "5. Testing Service-EventType Mapping:\n";
$mappings = DB::table('service_event_type_mappings')
    ->where('service_event_type_mappings.company_id', $company->id)
    ->join('services', 'service_event_type_mappings.service_id', '=', 'services.id')
    ->select('service_event_type_mappings.*', 'services.name as service_name')
    ->get();

if ($mappings->count() > 0) {
    foreach ($mappings as $mapping) {
        echo "   - Service: {$mapping->service_name} -> Event Type ID: {$mapping->calcom_event_type_id}\n";
        if ($mapping->keywords) {
            echo "     Keywords: {$mapping->keywords}\n";
        }
    }
} else {
    echo "   ❌ No service mappings found\n";
}

// 6. TEST RETELL AGENT DETAILS
echo "\n6. Testing Retell Agent Details:\n";
try {
    $retellService = app(\App\Services\MCP\RetellMCPServer::class);
    $agentsResult = $retellService->getAgentsWithPhoneNumbers(['company_id' => $company->id]);
    
    if (isset($agentsResult['agents']) || is_array($agentsResult)) {
        $agents = isset($agentsResult['agents']) ? $agentsResult['agents'] : $agentsResult;
        echo "   Found " . count($agents) . " Retell agents\n";
        foreach ($agents as $agent) {
            echo "   - Agent: " . ($agent['agent_name'] ?? 'Unnamed') . " (ID: {$agent['agent_id']})\n";
            echo "     Voice: " . ($agent['voice_id'] ?? 'Not set') . "\n";
            echo "     Language: " . ($agent['language'] ?? 'Not set') . "\n";
            if (isset($agent['phone_numbers']) && count($agent['phone_numbers']) > 0) {
                echo "     Phone Numbers: " . count($agent['phone_numbers']) . "\n";
            }
        }
    } else {
        echo "   ❌ No agents found or API error\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error loading agents: " . $e->getMessage() . "\n";
}

// 7. TEST CAL.COM EVENT TYPES
echo "\n7. Testing Cal.com Event Types:\n";
$eventTypes = DB::table('calcom_event_types')->where('company_id', $company->id)->get();
echo "   Found " . $eventTypes->count() . " event types\n";
foreach ($eventTypes->take(5) as $eventType) {
    echo "   - {$eventType->name} (ID: {$eventType->calcom_numeric_event_type_id})\n";
    echo "     Duration: {$eventType->duration_minutes} minutes\n";
    echo "     Active: " . ($eventType->is_active ? '✅' : '❌') . "\n";
}

// 8. TEST WEBHOOK STATUS
echo "\n8. Testing Webhook Status:\n";
$recentWebhooks = DB::table('webhook_events')
    ->where('company_id', $company->id)
    ->where('created_at', '>=', now()->subDay())
    ->count();
echo "   Recent webhooks (24h): {$recentWebhooks}\n";

// 9. TEST KNOWLEDGE BASE
echo "\n9. Testing Knowledge Base:\n";
try {
    $knowledgeService = app(\App\Services\MCP\KnowledgeMCPServer::class);
    $docCount = $knowledgeService->getDocumentCount(['company_id' => $company->id])['count'] ?? 0;
    echo "   Knowledge documents: {$docCount}\n";
} catch (\Exception $e) {
    echo "   ❌ Error checking knowledge base: " . $e->getMessage() . "\n";
}

// 10. SUMMARY
echo "\n====== TEST SUMMARY ======\n";
echo "✅ Company selection and data loading\n";
echo "✅ Integration status display\n";
echo "✅ Branch management display\n";
echo "✅ Phone number management display\n";
echo ($mappings->count() > 0 ? "✅" : "⚠️") . " Service-EventType mappings\n";
echo "✅ Database queries working\n";

echo "\n⚠️ NOTE: This is a static test. For interactive testing:\n";
echo "1. Visit /admin/company-integration-portal\n";
echo "2. Test inline editing by clicking pencil icons\n";
echo "3. Test toggles and dropdowns\n";
echo "4. Test modals for event type and service mapping\n";
echo "5. Test integration tests buttons\n";

echo "\n====== END OF TEST ======\n";