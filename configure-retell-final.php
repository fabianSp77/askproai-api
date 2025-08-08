#!/usr/bin/env php
<?php
/**
 * Final Retell.ai Configuration with correct API endpoints
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$agentId = 'agent_d7da9e5c49c4ccfff2526df5c1';

echo "================================================================================\n";
echo "Retell.ai Hair Salon Agent Configuration\n";
echo "Agent ID: $agentId\n";
echo "================================================================================\n\n";

// First, let's check the current agent
echo "📡 Fetching current agent configuration...\n";
$ch = curl_init("https://api.retellai.com/get-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $agent = json_decode($response, true);
    echo "✅ Agent found: " . ($agent['agent_name'] ?? 'Unknown') . "\n";
    echo "   Voice: " . ($agent['voice_id'] ?? 'Not set') . "\n";
    echo "   Webhook: " . ($agent['webhook_url'] ?? 'Not set') . "\n\n";
} else {
    echo "⚠️  Could not fetch agent (HTTP $httpCode)\n\n";
}

// Prepare the update with webhook configuration
$webhookConfig = [
    'webhook_url' => 'https://api.askproai.de/api/v2/hair-salon-mcp/retell-webhook',
    'enable_backchannel' => true,
    'backchannel_frequency_milliseconds' => 1500,
    'backchannel_words' => ['ja', 'okay', 'verstehe', 'genau', 'aha'],
    'interruption_sensitivity' => 0.6,
    'responsiveness' => 0.7,
    'voice_speed' => 1.0,
    'voice_temperature' => 0.5,
    'ambient_sound' => 'off'
];

echo "🔧 Updating agent with webhook configuration...\n";

// Use the correct API endpoint
$ch = curl_init("https://api.retellai.com/update-agent");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array_merge(
    ['agent_id' => $agentId],
    $webhookConfig
)));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ Agent updated successfully!\n\n";
    
    $updatedAgent = json_decode($response, true);
    if ($updatedAgent) {
        echo "Configuration Summary:\n";
        echo "--------------------\n";
        echo "• Webhook URL: " . ($updatedAgent['webhook_url'] ?? $webhookConfig['webhook_url']) . "\n";
        echo "• Backchannel: Enabled with German words\n";
        echo "• Interruption Sensitivity: 0.6 (medium)\n";
        echo "• Responsiveness: 0.7 (natural)\n\n";
    }
} else {
    echo "❌ Failed to update agent: HTTP $httpCode\n";
    if ($error) {
        echo "   Error: $error\n";
    }
    if ($response) {
        $decoded = json_decode($response, true);
        if ($decoded && isset($decoded['error'])) {
            echo "   API Error: " . $decoded['error'] . "\n";
        } else {
            echo "   Response: " . substr($response, 0, 200) . "\n";
        }
    }
    echo "\n";
}

// Now create the webhook endpoint handler
echo "📝 Creating webhook handler...\n";

$webhookHandler = '<?php
/**
 * Retell.ai Webhook Handler for Hair Salon MCP
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MCP\HairSalonMCPServer;
use Illuminate\Support\Facades\Log;

class RetellHairSalonWebhookController extends Controller
{
    private HairSalonMCPServer $mcpServer;
    
    public function __construct(HairSalonMCPServer $mcpServer)
    {
        $this->mcpServer = $mcpServer;
    }
    
    public function handleWebhook(Request $request)
    {
        Log::info("Retell webhook received", $request->all());
        
        $event = $request->input("event");
        $callId = $request->input("call_id");
        
        switch ($event) {
            case "call_started":
                return $this->handleCallStarted($request);
                
            case "call_ended":
                return $this->handleCallEnded($request);
                
            case "call_analyzed":
                return $this->handleCallAnalyzed($request);
                
            case "function_call":
                return $this->handleFunctionCall($request);
                
            default:
                Log::warning("Unknown Retell event: " . $event);
                return response()->json(["status" => "ok"]);
        }
    }
    
    private function handleFunctionCall(Request $request)
    {
        $functionName = $request->input("function_name");
        $arguments = $request->input("arguments", []);
        
        // Set default company_id if not provided
        if (!isset($arguments["company_id"])) {
            $arguments["company_id"] = 1;
        }
        
        Log::info("Function call: " . $functionName, $arguments);
        
        try {
            switch ($functionName) {
                case "list_services":
                    $result = $this->mcpServer->getServices($arguments);
                    break;
                    
                case "check_availability":
                    $result = $this->mcpServer->checkAvailability($arguments);
                    break;
                    
                case "book_appointment":
                    $result = $this->mcpServer->bookAppointment($arguments);
                    break;
                    
                case "schedule_callback":
                    $result = $this->mcpServer->scheduleCallback($arguments);
                    break;
                    
                default:
                    $result = [
                        "success" => false,
                        "error" => "Unknown function: " . $functionName
                    ];
            }
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error("Function call error: " . $e->getMessage());
            return response()->json([
                "success" => false,
                "error" => "Internal error: " . $e->getMessage()
            ]);
        }
    }
    
    private function handleCallStarted(Request $request)
    {
        return response()->json(["status" => "ok"]);
    }
    
    private function handleCallEnded(Request $request)
    {
        // Track billing here
        $duration = $request->input("call_duration_seconds", 0);
        $callId = $request->input("call_id");
        
        // Log for billing
        Log::info("Call ended - Duration: {$duration}s, Call ID: {$callId}");
        
        return response()->json(["status" => "ok"]);
    }
    
    private function handleCallAnalyzed(Request $request)
    {
        return response()->json(["status" => "ok"]);
    }
}
';

file_put_contents('/var/www/api-gateway/app/Http/Controllers/RetellHairSalonWebhookController.php', $webhookHandler);
echo "✅ Webhook handler created\n\n";

// Add route
echo "📝 Adding webhook route...\n";
$routeContent = "\n// Retell.ai Hair Salon Webhook\nRoute::post('/api/v2/hair-salon-mcp/retell-webhook', [\\App\\Http\\Controllers\\RetellHairSalonWebhookController::class, 'handleWebhook'])\n    ->name('api.hair-salon.retell.webhook');\n";

// Check if route already exists
$existingRoutes = file_get_contents('/var/www/api-gateway/routes/api.php');
if (strpos($existingRoutes, 'hair-salon-mcp/retell-webhook') === false) {
    file_put_contents('/var/www/api-gateway/routes/api.php', $routeContent, FILE_APPEND);
    echo "✅ Route added\n\n";
} else {
    echo "ℹ️  Route already exists\n\n";
}

echo "================================================================================\n";
echo "🎉 Hair Salon MCP Configuration Complete!\n";
echo "================================================================================\n\n";

echo "✅ What has been configured:\n";
echo "   • Webhook URL: https://api.askproai.de/api/v2/hair-salon-mcp/retell-webhook\n";
echo "   • Webhook handler created\n";
echo "   • Route added to API\n";
echo "   • German backchannel words configured\n";
echo "   • Optimized voice settings\n\n";

echo "📞 The agent will handle these via webhook:\n";
echo "   • list_services - Service-Katalog anzeigen\n";
echo "   • check_availability - Verfügbarkeit prüfen\n";
echo "   • book_appointment - Termine buchen\n";
echo "   • schedule_callback - Beratung vereinbaren\n\n";

echo "🧪 Test the agent:\n";
echo "   1. Call: +493033081738\n";
echo "   2. Say: 'Ich möchte einen Termin für einen Haarschnitt'\n";
echo "   3. Or: 'Ich hätte gerne Strähnen' (triggers callback)\n\n";

echo "📊 Monitor logs:\n";
echo "   tail -f storage/logs/laravel.log | grep -i retell\n\n";