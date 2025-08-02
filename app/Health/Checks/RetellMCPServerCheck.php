<?php

namespace App\Health\Checks;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Illuminate\Support\Facades\Http;
use App\Services\MCP\RetellAIBridgeMCPServer;

class RetellMCPServerCheck extends Check
{
    protected ?string $mcpServerUrl = null;
    protected int $timeout = 5;

    public function __construct()
    {
        $this->mcpServerUrl = config('services.retell_mcp.url');
    }

    public function run(): Result
    {
        $result = Result::make();

        // Check if MCP is enabled
        if (!config('retell-mcp.features.enabled', true)) {
            return $result
                ->shortSummary('Disabled')
                ->notificationMessage('Retell AI MCP is disabled')
                ->ok();
        }

        // Check external MCP server
        $mcpServerHealthy = $this->checkExternalMCPServer();
        
        if (!$mcpServerHealthy) {
            return $result
                ->failed()
                ->shortSummary('MCP server unreachable')
                ->notificationMessage('Retell AI MCP server is not responding at ' . $this->mcpServerUrl)
                ->meta([
                    'server_url' => $this->mcpServerUrl,
                    'error' => 'Connection failed',
                ]);
        }

        // Check circuit breaker status
        try {
            $bridgeServer = app(RetellAIBridgeMCPServer::class);
            $health = $bridgeServer->healthCheck();
            
            if (isset($health['circuit_breaker']['status'])) {
                $cbStatus = $health['circuit_breaker']['status'];
                
                if ($cbStatus === 'open') {
                    return $result
                        ->failed()
                        ->shortSummary('Circuit breaker open')
                        ->notificationMessage('Retell AI MCP circuit breaker is open due to failures')
                        ->meta([
                            'circuit_breaker' => $health['circuit_breaker'],
                        ]);
                }
                
                if ($cbStatus === 'half-open') {
                    return $result
                        ->warning()
                        ->shortSummary('Circuit breaker testing')
                        ->notificationMessage('Retell AI MCP circuit breaker is in half-open state')
                        ->meta([
                            'circuit_breaker' => $health['circuit_breaker'],
                        ]);
                }
            }
        } catch (\Exception $e) {
            // Don't fail health check if we can't get circuit breaker status
        }

        // Check recent call statistics
        $stats = $this->getRecentCallStats();
        
        return $result
            ->ok()
            ->shortSummary('Operational')
            ->notificationMessage('Retell AI MCP integration is healthy')
            ->meta([
                'server_url' => $this->mcpServerUrl,
                'recent_calls' => $stats['recent_calls'],
                'success_rate' => $stats['success_rate'] . '%',
                'active_campaigns' => $stats['active_campaigns'],
            ]);
    }

    /**
     * Check if external MCP server is reachable
     */
    protected function checkExternalMCPServer(): bool
    {
        if (!$this->mcpServerUrl) {
            return false;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->get($this->mcpServerUrl . '/health');
            
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get recent call statistics
     */
    protected function getRecentCallStats(): array
    {
        try {
            $recentCalls = \App\Models\Call::where('direction', 'outbound')
                ->where('created_at', '>=', now()->subHour())
                ->count();
            
            $successfulCalls = \App\Models\Call::where('direction', 'outbound')
                ->where('created_at', '>=', now()->subHour())
                ->where('status', 'completed')
                ->count();
            
            $successRate = $recentCalls > 0 
                ? round(($successfulCalls / $recentCalls) * 100) 
                : 100;
            
            $activeCampaigns = \App\Models\RetellAICallCampaign::whereIn('status', ['running', 'scheduled'])
                ->count();
            
            return [
                'recent_calls' => $recentCalls,
                'success_rate' => $successRate,
                'active_campaigns' => $activeCampaigns,
            ];
        } catch (\Exception $e) {
            return [
                'recent_calls' => 0,
                'success_rate' => 0,
                'active_campaigns' => 0,
            ];
        }
    }
}