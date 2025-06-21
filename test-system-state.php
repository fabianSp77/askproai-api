<?php
require __DIR__ . '/vendor/autoload.php';

use App\Models\Company;
use App\Models\Branch;
use App\Models\Call;
use App\Models\WebhookEvent;
use App\Services\RetellV2Service;
use App\Scopes\TenantScope;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Disable tenant scope for this analysis
Branch::withoutGlobalScope(TenantScope::class);
Call::withoutGlobalScope(TenantScope::class);

echo "🔍 ASKPROAI SYSTEM STATE ANALYSIS\n";
echo "=====================================\n\n";

// 1. Company & Branch Analysis
echo "📊 COMPANY & BRANCH STATE\n";
echo "-------------------------\n";

$companies = Company::all();
foreach ($companies as $company) {
    echo "\nCompany: {$company->name} (ID: {$company->id})\n";
    echo "  - Active: " . ($company->is_active ? 'Yes' : 'No') . "\n";
    echo "  - Phone: {$company->phone}\n";
    echo "  - Retell Agent ID: " . ($company->retell_agent_id ?: 'NOT SET') . "\n";
    $retellKey = 'NOT SET';
    if ($company->retell_api_key) {
        try {
            $retellKey = substr(decrypt($company->retell_api_key), 0, 10) . '...';
        } catch (\Exception $e) {
            $retellKey = substr($company->retell_api_key, 0, 10) . '...';
        }
    }
    echo "  - Retell API Key: $retellKey\n";
    
    $calcomKey = 'NOT SET';
    if ($company->calcom_api_key) {
        try {
            $calcomKey = substr(decrypt($company->calcom_api_key), 0, 10) . '...';
        } catch (\Exception $e) {
            $calcomKey = substr($company->calcom_api_key, 0, 10) . '...';
        }
    }
    echo "  - Cal.com API Key: $calcomKey\n";
    echo "  - Cal.com Team Slug: " . ($company->calcom_team_slug ?: 'NOT SET') . "\n";
    
    echo "\n  Branches:\n";
    $branches = Branch::withoutGlobalScope(TenantScope::class)->where('company_id', $company->id)->get();
    foreach ($branches as $branch) {
        echo "    - {$branch->name} (ID: {$branch->id})\n";
        echo "      Phone: " . ($branch->phone_number ?: 'NOT SET') . "\n";
        echo "      Active: " . ($branch->active ? 'Yes' : 'No') . "\n";
        echo "      Retell Agent ID: " . ($branch->retell_agent_id ?: 'NOT SET') . "\n";
        echo "      Cal.com Event Type ID: " . ($branch->calcom_event_type_id ?: 'NOT SET') . "\n";
        echo "      Cal.com API Key: " . ($branch->calcom_api_key ? 'SET (Branch Override)' : 'Using Company Key') . "\n";
    }
}

// 2. Call Analysis
echo "\n\n📞 CALL ANALYSIS\n";
echo "-----------------\n";
$callCount = Call::withoutGlobalScope(TenantScope::class)->count();
$recentCalls = Call::withoutGlobalScope(TenantScope::class)->orderBy('created_at', 'desc')->limit(5)->get();

echo "Total calls in database: $callCount\n\n";

if ($recentCalls->count() > 0) {
    echo "Recent calls:\n";
    foreach ($recentCalls as $call) {
        echo "  - Call ID: {$call->id}\n";
        echo "    Retell ID: " . ($call->retell_call_id ?: 'N/A') . "\n";
        echo "    From: {$call->from_number} → To: {$call->to_number}\n";
        echo "    Status: {$call->status}\n";
        echo "    Created: " . $call->created_at->format('Y-m-d H:i:s') . "\n\n";
    }
} else {
    echo "❌ No calls found in database!\n";
}

// 3. Webhook Analysis
echo "\n\n🌐 WEBHOOK ANALYSIS\n";
echo "--------------------\n";
$webhookCount = WebhookEvent::count();
$retellWebhooks = WebhookEvent::where('provider', 'retell')->count();
$recentWebhooks = WebhookEvent::orderBy('created_at', 'desc')->limit(5)->get();

echo "Total webhooks: $webhookCount\n";
echo "Retell webhooks: $retellWebhooks\n\n";

if ($recentWebhooks->count() > 0) {
    echo "Recent webhooks:\n";
    foreach ($recentWebhooks as $webhook) {
        echo "  - Provider: {$webhook->provider}\n";
        echo "    Event Type: {$webhook->event_type}\n";
        echo "    Status: {$webhook->status}\n";
        echo "    Created: " . $webhook->created_at->format('Y-m-d H:i:s') . "\n\n";
    }
} else {
    echo "❌ No webhooks found in database!\n";
}

// 4. Configuration Check
echo "\n\n⚙️  CONFIGURATION CHECK\n";
echo "------------------------\n";

// Check environment variables
$envChecks = [
    'RETELL_TOKEN' => env('RETELL_TOKEN'),
    'RETELL_WEBHOOK_SECRET' => env('RETELL_WEBHOOK_SECRET'),
    'RETELL_BASE' => env('RETELL_BASE'),
    'DEFAULT_RETELL_API_KEY' => env('DEFAULT_RETELL_API_KEY'),
    'DEFAULT_RETELL_AGENT_ID' => env('DEFAULT_RETELL_AGENT_ID'),
];

foreach ($envChecks as $key => $value) {
    if ($value) {
        echo "✅ $key: " . substr($value, 0, 20) . "...\n";
    } else {
        echo "❌ $key: NOT SET\n";
    }
}

// Check config
echo "\nConfig values:\n";
echo "  - services.retell.api_key: " . (config('services.retell.api_key') ? 'SET' : 'NOT SET') . "\n";
echo "  - services.retell.secret: " . (config('services.retell.secret') ? 'SET' : 'NOT SET') . "\n";
echo "  - services.retell.base: " . config('services.retell.base', 'NOT SET') . "\n";

// 5. Retell API Test
echo "\n\n🔌 RETELL API CONNECTION TEST\n";
echo "-------------------------------\n";

$company = Company::first();
if ($company && $company->retell_api_key) {
    try {
        try {
            $apiKey = decrypt($company->retell_api_key);
        } catch (\Exception $e) {
            $apiKey = $company->retell_api_key;
        }
        $retell = new RetellV2Service($apiKey);
        
        // Test listing agents
        $agents = $retell->listAgents();
        if (isset($agents['agents'])) {
            echo "✅ Successfully connected to Retell API\n";
            echo "   Found " . count($agents['agents']) . " agents\n";
        } else {
            echo "⚠️  Connected but no agents found\n";
        }
    } catch (\Exception $e) {
        echo "❌ Failed to connect to Retell API: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ No company with Retell API key found\n";
}

// 6. Phone Number Mapping
echo "\n\n📱 PHONE NUMBER MAPPING\n";
echo "------------------------\n";

$branches = Branch::withoutGlobalScope(TenantScope::class)->where('active', true)->whereNotNull('phone_number')->get();
if ($branches->count() > 0) {
    echo "Active branches with phone numbers:\n";
    foreach ($branches as $branch) {
        echo "  - {$branch->name}: {$branch->phone_number}\n";
        echo "    → Retell Agent: " . ($branch->retell_agent_id ?: 'NOT SET') . "\n";
        echo "    → Cal.com Event: " . ($branch->calcom_event_type_id ?: 'NOT SET') . "\n";
    }
} else {
    echo "❌ No active branches with phone numbers found!\n";
}

// 7. Recommendations
echo "\n\n💡 RECOMMENDATIONS\n";
echo "-------------------\n";

$issues = [];

// Check for missing configurations
if (!env('RETELL_TOKEN') && !env('DEFAULT_RETELL_API_KEY')) {
    $issues[] = "Set RETELL_TOKEN or DEFAULT_RETELL_API_KEY in .env";
}

if (!env('RETELL_WEBHOOK_SECRET')) {
    $issues[] = "Set RETELL_WEBHOOK_SECRET in .env (use same as API key if not provided by Retell)";
}

$activeBranches = Branch::withoutGlobalScope(TenantScope::class)->where('active', true)->count();
$branchesWithPhone = Branch::withoutGlobalScope(TenantScope::class)->where('active', true)->whereNotNull('phone_number')->count();
$branchesWithAgent = Branch::withoutGlobalScope(TenantScope::class)->where('active', true)->whereNotNull('retell_agent_id')->count();
$branchesWithCalcom = Branch::withoutGlobalScope(TenantScope::class)->where('active', true)->whereNotNull('calcom_event_type_id')->count();

if ($branchesWithPhone < $activeBranches) {
    $issues[] = "Configure phone numbers for all active branches";
}

if ($branchesWithAgent < $activeBranches) {
    $issues[] = "Configure Retell agents for all active branches";
}

if ($branchesWithCalcom < $activeBranches) {
    $issues[] = "Configure Cal.com event types for all active branches";
}

if ($callCount == 0 && $webhookCount == 0) {
    $issues[] = "No calls or webhooks received - check Retell webhook configuration";
    $issues[] = "Ensure webhook URL is configured in Retell: https://yourdomain.com/api/retell/webhook";
}

if (count($issues) > 0) {
    echo "Issues found:\n";
    foreach ($issues as $index => $issue) {
        echo ($index + 1) . ". $issue\n";
    }
} else {
    echo "✅ System appears to be properly configured!\n";
}

echo "\n✅ Analysis complete\n";