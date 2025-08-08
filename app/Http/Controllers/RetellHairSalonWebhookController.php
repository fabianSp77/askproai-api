<?php
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
