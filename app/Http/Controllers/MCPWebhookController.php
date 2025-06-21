<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MCP\WebhookMCPServer;
use Illuminate\Support\Facades\Log;

class MCPWebhookController extends Controller
{
    protected WebhookMCPServer $webhookMCP;
    
    public function __construct(WebhookMCPServer $webhookMCP)
    {
        $this->webhookMCP = $webhookMCP;
    }
    
    /**
     * Handle Retell webhook using MCP services
     */
    public function handleRetellWebhook(Request $request)
    {
        // Log incoming webhook
        Log::info('MCP Retell webhook received', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'ip' => $request->ip()
        ]);
        
        // Process webhook through MCP
        $result = $this->webhookMCP->processRetellWebhook($request->all());
        
        // Log result
        Log::info('MCP Retell webhook processed', [
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? null,
            'appointment_created' => $result['appointment_created'] ?? false
        ]);
        
        // Always return 200 to acknowledge receipt
        return response()->json($result, 200);
    }
    
    /**
     * Get webhook processing statistics
     */
    public function getWebhookStats(Request $request)
    {
        $params = $request->validate([
            'days' => 'integer|min:1|max:90'
        ]);
        
        $stats = $this->webhookMCP->getWebhookStats($params);
        
        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }
    
    /**
     * Health check for MCP webhook processor
     */
    public function health()
    {
        return response()->json([
            'status' => 'healthy',
            'service' => 'mcp_webhook_processor',
            'timestamp' => now()->toIso8601String()
        ]);
    }
}