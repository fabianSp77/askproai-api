<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Services\RetellV2Service;

echo "🔍 CHECKING RETELL CALLS DIRECTLY\n";
echo str_repeat('=', 50) . "\n\n";

$company = Company::find(1);
$apiKey = decrypt($company->retell_api_key);
$service = new RetellV2Service($apiKey);

// Get recent calls from Retell API
echo "Fetching recent calls from Retell API...\n\n";

try {
    $calls = $service->listCalls(10);
    
    if (empty($calls)) {
        echo "❌ No calls found!\n";
    } else {
        echo "Found " . count($calls) . " recent calls:\n\n";
        
        foreach ($calls as $index => $call) {
            echo ($index + 1) . ". Call ID: " . $call['call_id'] . "\n";
            echo "   Status: " . $call['call_status'] . "\n";
            echo "   Start Time: " . date('Y-m-d H:i:s', $call['start_timestamp'] / 1000) . "\n";
            echo "   Duration: " . ($call['end_timestamp'] - $call['start_timestamp']) / 1000 . " seconds\n";
            echo "   From: " . ($call['from_number'] ?? 'Unknown') . "\n";
            echo "   To: " . ($call['to_number'] ?? 'Unknown') . "\n";
            echo "   Agent: " . ($call['agent_id'] ?? 'Unknown') . "\n";
            
            // Check if webhook was sent
            if (isset($call['webhook_tools'])) {
                echo "   Webhook Tools: " . json_encode($call['webhook_tools']) . "\n";
            }
            
            // Check custom functions
            if (isset($call['transcript_object'])) {
                $hasAppointmentData = false;
                foreach ($call['transcript_object'] as $entry) {
                    if (isset($entry['tool_calls'])) {
                        foreach ($entry['tool_calls'] as $toolCall) {
                            if ($toolCall['function']['name'] === 'collect_appointment_data') {
                                $hasAppointmentData = true;
                                echo "   ✅ Appointment data collected!\n";
                                echo "   Data: " . json_encode($toolCall['function']['arguments']) . "\n";
                            }
                        }
                    }
                }
                if (!$hasAppointmentData) {
                    echo "   ❌ No appointment data collected\n";
                }
            }
            
            echo "\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error fetching calls: " . $e->getMessage() . "\n";
}

// Check webhook events configuration
echo "\n\nCHECKING WEBHOOK CONFIGURATION:\n";
echo str_repeat('-', 30) . "\n";

// Get the Musterfriseur agent details
$agentsResult = $service->listAgents();
$agents = $agentsResult['agents'] ?? [];

foreach ($agents as $agent) {
    if ($agent['agent_name'] === 'Online: Musterfriseur Terminierung') {
        echo "\nAgent: " . $agent['agent_name'] . "\n";
        echo "Webhook URL: " . ($agent['webhook_url'] ?? 'NOT SET') . "\n";
        
        // Check if webhook events are enabled
        if (isset($agent['webhook_events'])) {
            echo "Webhook Events Enabled: " . implode(', ', $agent['webhook_events']) . "\n";
        } else {
            echo "❌ No webhook events configured!\n";
        }
        
        break;
    }
}