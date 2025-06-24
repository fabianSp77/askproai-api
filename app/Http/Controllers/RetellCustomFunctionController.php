<?php

namespace App\Http\Controllers;

use App\Services\MCP\MCPGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Controller for handling Retell.ai custom function calls via MCP
 */
class RetellCustomFunctionController extends Controller
{
    protected MCPGateway $gateway;
    
    public function __construct(MCPGateway $gateway)
    {
        $this->gateway = $gateway;
    }
    
    /**
     * Handle custom function call from Retell.ai
     * 
     * @param string $function Function name
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(string $function, Request $request)
    {
        $startTime = microtime(true);
        $requestId = Str::uuid()->toString();
        
        Log::info('Retell custom function call received', [
            'function' => $function,
            'request_id' => $requestId,
            'call_id' => $request->input('call_id'),
            'params' => $request->all(),
        ]);
        
        try {
            // Prepare MCP request
            $mcpRequest = [
                'jsonrpc' => '2.0',
                'method' => "retell.functions.{$function}",
                'params' => array_merge($request->all(), [
                    'caller_number' => $request->input('from_number') ?? $request->input('telefonnummer'),
                    'to_number' => $request->input('to_number'),
                    'call_id' => $request->input('call_id'),
                ]),
                'id' => $requestId,
            ];
            
            // Process through MCP gateway
            $mcpResponse = $this->gateway->process($mcpRequest);
            
            // Check for MCP errors
            if (isset($mcpResponse['error'])) {
                Log::error('MCP error in custom function', [
                    'function' => $function,
                    'error' => $mcpResponse['error'],
                    'request_id' => $requestId,
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => $mcpResponse['error']['message'] ?? 'Unknown error',
                ], 400);
            }
            
            // Extract result
            $result = $mcpResponse['result'] ?? [];
            
            // Log success
            Log::info('Retell custom function completed', [
                'function' => $function,
                'request_id' => $requestId,
                'duration_ms' => (microtime(true) - $startTime) * 1000,
                'success' => $result['success'] ?? false,
            ]);
            
            // Return response in format expected by Retell
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Retell custom function exception', [
                'function' => $function,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $requestId,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => app()->environment('local') ? $e->getMessage() : 'An error occurred processing your request',
            ], 500);
        }
    }
}