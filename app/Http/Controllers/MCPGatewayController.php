<?php

namespace App\Http\Controllers;

use App\Services\MCP\MCPGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * MCP Gateway Controller - HTTP endpoint for MCP communication
 */
class MCPGatewayController extends Controller
{
    protected MCPGateway $gateway;
    
    public function __construct(MCPGateway $gateway)
    {
        $this->gateway = $gateway;
    }
    
    /**
     * Handle MCP request
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        // Log incoming request
        Log::debug('MCP Gateway request', [
            'method' => $request->input('method'),
            'params' => $request->input('params'),
            'id' => $request->input('id')
        ]);
        
        // Process request through gateway
        $response = $this->gateway->process($request->all());
        
        // Log response
        Log::debug('MCP Gateway response', [
            'response' => $response,
            'has_error' => isset($response['error'])
        ]);
        
        // Return JSON-RPC response
        return response()->json($response);
    }
    
    /**
     * Get gateway health status
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function health()
    {
        $health = $this->gateway->health();
        
        $isHealthy = $health['gateway'] === 'healthy' && 
                     collect($health['servers'])->every(fn($s) => ($s['status'] ?? 'unknown') === 'healthy');
        
        return response()->json($health, $isHealthy ? 200 : 503);
    }
    
    /**
     * List available MCP methods
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function methods()
    {
        return response()->json([
            'methods' => $this->gateway->listMethods(),
            'version' => '2.0',
            'gateway' => 'AskProAI MCP Gateway'
        ]);
    }
}