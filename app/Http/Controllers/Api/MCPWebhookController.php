<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MCP\WebhookMCPServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MCPWebhookController extends Controller
{
    protected WebhookMCPServer $webhookServer;
    
    public function __construct(WebhookMCPServer $webhookServer)
    {
        $this->webhookServer = $webhookServer;
    }
    
    /**
     * Handle Retell webhook through MCP
     * This bypasses signature verification for easier integration
     */
    public function handleRetell(Request $request)
    {
        $correlationId = $request->header('x-correlation-id', uniqid('mcp_'));
        
        Log::info('[MCP Webhook Controller] Retell webhook received', [
            'correlation_id' => $correlationId,
            'event' => $request->input('event'),
            'call_id' => $request->input('call.call_id'),
            'headers' => $request->headers->all()
        ]);
        
        try {
            // Process through MCP webhook server
            $result = $this->webhookServer->processRetellWebhook([
                'event' => $request->input('event'),
                'payload' => $request->all(),
                'correlation_id' => $correlationId
            ]);
            
            if (!$result['success']) {
                Log::error('[MCP Webhook Controller] Processing failed', [
                    'correlation_id' => $correlationId,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                
                // Still return 200 to prevent retries
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Processing failed',
                    'correlation_id' => $correlationId
                ], 200);
            }
            
            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'event_id' => $result['event_id'] ?? null,
                'message' => 'Webhook processed successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('[MCP Webhook Controller] Exception occurred', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return 200 to prevent retries
            return response()->json([
                'success' => false,
                'error' => 'Internal error',
                'correlation_id' => $correlationId
            ], 200);
        }
    }
    
    /**
     * Test endpoint for MCP webhook
     */
    public function test(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'MCP Webhook endpoint is working',
            'timestamp' => now()->toIso8601String(),
            'headers' => $request->headers->all(),
            'body' => $request->all()
        ]);
    }
}