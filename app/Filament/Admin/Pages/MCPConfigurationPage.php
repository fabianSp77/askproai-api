<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;

class MCPConfigurationPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = "MCP Configuration";
    protected static ?string $navigationGroup = "🔧 System Management";
    protected static ?int $navigationSort = 751;
    protected static ?string $slug = 'mcp-configuration';
    protected static string $view = 'filament.admin.pages.mcp-configuration';
    
    // Page configuration
    protected static ?string $title = 'MCP Configuration';
    protected ?string $subheading = 'Configure and monitor Retell.ai MCP integration';
    
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && (
            $user->hasRole(['Super Admin', 'super_admin', 'developer']) || 
            $user->email === 'dev@askproai.de' ||
            $user->can('manage_mcp_configuration')
        );
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    // Return data for React component
    public function getMountData(): array
    {
        return [
            'user' => [
                'id' => auth()->id(),
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'roles' => auth()->user()->getRoleNames(),
            ],
            'tenant' => [
                'id' => auth()->user()->company_id ?? 1,
                'name' => auth()->user()->company?->name ?? 'Default',
            ],
            'csrf_token' => csrf_token(),
            'api_base_url' => config('app.url') . '/api/mcp',
            'admin_api_url' => config('app.url') . '/admin/api/mcp',
            'websocket_enabled' => config('broadcasting.default') !== 'null',
        ];
    }

    // API Methods that can be called from React
    public function getConfiguration()
    {
        // Get MCP configuration from cache/database
        $config = cache()->remember('mcp:configuration', 300, function() {
            return [
                'enabled' => config('mcp.enabled', false),
                'rolloutPercentage' => config('mcp.rollout_percentage', 0),
                'tokens' => [
                    'retell' => config('services.retell.api_key') ? '••••••••' : '',
                    'calcom' => config('services.calcom.api_key') ? '••••••••' : '',
                    'database' => 'internal',
                ],
                'rateLimits' => [
                    'requestsPerMinute' => config('mcp.rate_limits.requests_per_minute', 100),
                    'burstLimit' => config('mcp.rate_limits.burst_limit', 20),
                ],
                'circuitBreaker' => [
                    'failureThreshold' => config('mcp.circuit_breaker.failure_threshold', 5),
                    'resetTimeout' => config('mcp.circuit_breaker.reset_timeout', 60000),
                    'halfOpenRequests' => config('mcp.circuit_breaker.half_open_requests', 3),
                ],
            ];
        });

        return response()->json(['data' => $config]);
    }

    public function getMetrics()
    {
        // Get real-time metrics
        $metrics = [
            'totalRequests' => cache()->get('mcp:metrics:total_requests', 0),
            'successRate' => cache()->get('mcp:metrics:success_rate', 0),
            'averageLatency' => cache()->get('mcp:metrics:avg_latency', 0),
            'circuitBreakerState' => cache()->get('mcp:circuit_breaker:state', 'closed'),
            'activeConnections' => cache()->get('mcp:metrics:active_connections', 0),
            'requestsPerMinute' => cache()->get('mcp:metrics:requests_per_minute', 0),
            'errorRate' => cache()->get('mcp:metrics:error_rate', 0),
        ];

        return response()->json(['data' => $metrics]);
    }

    public function getRecentCalls()
    {
        // Get recent MCP calls from cache
        $calls = cache()->get('mcp:recent_calls', []);
        
        // Format for display
        $formattedCalls = collect($calls)->map(function($call) {
            return [
                'tool' => $call['tool'] ?? 'unknown',
                'operation' => $call['operation'] ?? 'unknown',
                'success' => $call['success'] ?? false,
                'duration' => $call['duration'] ?? 0,
                'timestamp' => $call['timestamp'] ?? now()->format('H:i:s'),
                'error' => $call['error'] ?? null,
            ];
        })->take(20)->values();

        return response()->json(['data' => $formattedCalls]);
    }

    // Add any other methods needed for the React component
    protected function getViewData(): array
    {
        return array_merge(
            parent::getViewData(),
            [
                'mountData' => $this->getMountData(),
            ]
        );
    }
}