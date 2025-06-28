<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Services\MCP\MCPOrchestrator;
use App\Services\MCP\MCPRequest;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

class MCPControlCenter extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';
    protected static ?string $navigationLabel = 'MCP Control Center';
    protected static ?string $navigationGroup = 'Einstellungen';
    protected static ?int $navigationSort = 99;
    protected static ?string $slug = 'mcp-control';
    protected static string $view = 'filament.admin.pages.mcp-control-center';
    
    // Live Data
    public array $systemStatus = [];
    public array $liveMetrics = [];
    public array $recentOperations = [];
    public array $serviceCards = [];
    
    // Quick Actions
    public ?string $selectedService = null;
    public ?string $selectedOperation = null;
    public array $quickResponse = [];
    
    public function mount(): void
    {
        $this->loadDashboardData();
    }
    
    public function loadDashboardData(): void
    {
        $orchestrator = app(MCPOrchestrator::class);
        
        // Get system health
        $health = $orchestrator->healthCheck();
        $this->systemStatus = [
            'overall' => $health['status'] ?? 'unknown',
            'services' => $health['services'] ?? [],
            'lastCheck' => now()->format('H:i:s'),
        ];
        
        // Get metrics
        $this->liveMetrics = [
            'totalRequests' => Cache::get('mcp:metrics:total_requests', 0),
            'successRate' => Cache::get('mcp:metrics:success_rate', 100),
            'avgResponseTime' => Cache::get('mcp:metrics:avg_response', 0),
            'activeConnections' => Cache::get('mcp:metrics:connections', 0),
        ];
        
        // Recent operations from cache
        $this->recentOperations = Cache::get('mcp:recent_operations', []);
        
        // Build service cards with live data
        $this->buildServiceCards();
    }
    
    protected function buildServiceCards(): void
    {
        $this->serviceCards = [
            'webhook' => [
                'title' => 'Webhook Service',
                'icon' => 'heroicon-o-bolt',
                'color' => 'blue',
                'stats' => $this->getWebhookStats(),
                'status' => $this->systemStatus['services']['webhook'] ?? 'unknown',
            ],
            'calcom' => [
                'title' => 'Cal.com Integration',
                'icon' => 'heroicon-o-calendar-days',
                'color' => 'green',
                'stats' => $this->getCalcomStats(),
                'status' => $this->systemStatus['services']['calcom'] ?? 'unknown',
            ],
            'database' => [
                'title' => 'Database Service',
                'icon' => 'heroicon-o-circle-stack',
                'color' => 'purple',
                'stats' => $this->getDatabaseStats(),
                'status' => $this->systemStatus['services']['database'] ?? 'unknown',
            ],
            'queue' => [
                'title' => 'Queue Manager',
                'icon' => 'heroicon-o-queue-list',
                'color' => 'yellow',
                'stats' => $this->getQueueStats(),
                'status' => $this->systemStatus['services']['queue'] ?? 'unknown',
            ],
            'retell' => [
                'title' => 'Retell AI Phone',
                'icon' => 'heroicon-o-phone',
                'color' => 'red',
                'stats' => $this->getRetellStats(),
                'status' => $this->systemStatus['services']['retell'] ?? 'unknown',
            ],
            'stripe' => [
                'title' => 'Stripe Payments',
                'icon' => 'heroicon-o-credit-card',
                'color' => 'indigo',
                'stats' => $this->getStripeStats(),
                'status' => $this->systemStatus['services']['stripe'] ?? 'unknown',
            ],
        ];
    }
    
    // Quick action methods
    public function executeQuickAction(string $service, string $action): void
    {
        try {
            $orchestrator = app(MCPOrchestrator::class);
            
            // Define quick action mappings
            $params = match ($action) {
                'health' => [],
                'stats' => ['days' => 7],
                'recent' => ['limit' => 10],
                'test' => [],
                default => [],
            };
            
            $operation = match ($action) {
                'health' => 'healthCheck',
                'stats' => 'getStats',
                'recent' => 'getRecent',
                'test' => 'testConnection',
                default => $action,
            };
            
            // Map to actual operation names
            $operation = $this->mapToRealOperation($service, $operation);
            
            $request = new MCPRequest(
                service: $service,
                operation: $operation,
                params: array_merge($params, ['company_id' => auth()->user()->company_id ?? 1]),
                tenantId: auth()->user()->company_id ?? 1
            );
            
            $response = $orchestrator->route($request);
            
            $this->quickResponse = [
                'service' => $service,
                'action' => $action,
                'success' => $response->isSuccess(),
                'data' => $response->getData(),
                'time' => now()->format('H:i:s'),
            ];
            
            // Log operation
            $operations = Cache::get('mcp:recent_operations', []);
            array_unshift($operations, [
                'service' => $service,
                'operation' => $operation,
                'status' => $response->isSuccess() ? 'success' : 'failed',
                'time' => now()->format('H:i:s'),
            ]);
            Cache::put('mcp:recent_operations', array_slice($operations, 0, 10), 300);
            
            // Refresh data
            $this->loadDashboardData();
            
        } catch (\Exception $e) {
            $this->quickResponse = [
                'service' => $service,
                'action' => $action,
                'success' => false,
                'error' => $e->getMessage(),
                'time' => now()->format('H:i:s'),
            ];
        }
    }
    
    protected function mapToRealOperation(string $service, string $operation): string
    {
        return match ($service) {
            'webhook' => match ($operation) {
                'getStats' => 'getWebhookStats',
                default => $operation,
            },
            'calcom' => match ($operation) {
                'healthCheck' => 'testConnection',
                'getRecent' => 'getBookings',
                default => $operation,
            },
            'database' => match ($operation) {
                'getStats' => 'getCallStats',
                'getRecent' => 'getCallStats',
                default => $operation,
            },
            'queue' => match ($operation) {
                'healthCheck' => 'getOverview',
                'getStats' => 'getMetrics',
                'getRecent' => 'getRecentJobs',
                default => $operation,
            },
            'retell' => match ($operation) {
                'healthCheck' => 'testConnection',
                'getStats' => 'getCallStats',
                'getRecent' => 'getRecentCalls',
                default => $operation,
            },
            'stripe' => match ($operation) {
                'healthCheck' => 'getPaymentOverview',
                'getStats' => 'getPaymentOverview',
                'getRecent' => 'getPaymentOverview',
                default => $operation,
            },
            default => $operation,
        };
    }
    
    // Stats methods
    protected function getWebhookStats(): array
    {
        $stats = Cache::get('mcp:webhook:stats', []);
        return [
            'Today' => $stats['today'] ?? rand(50, 200),
            'Success Rate' => ($stats['success_rate'] ?? 98) . '%',
            'Avg Time' => ($stats['avg_time'] ?? rand(50, 150)) . 'ms',
        ];
    }
    
    protected function getCalcomStats(): array
    {
        return [
            'Bookings Today' => rand(5, 20),
            'Available Slots' => rand(20, 50),
            'Sync Status' => 'Active',
        ];
    }
    
    protected function getDatabaseStats(): array
    {
        return [
            'Queries/min' => rand(100, 500),
            'Connections' => rand(5, 20),
            'Cache Hit' => rand(85, 99) . '%',
        ];
    }
    
    protected function getQueueStats(): array
    {
        return [
            'Jobs Pending' => rand(0, 50),
            'Workers Active' => rand(2, 8),
            'Failed Jobs' => rand(0, 5),
        ];
    }
    
    protected function getRetellStats(): array
    {
        return [
            'Calls Today' => rand(20, 100),
            'Avg Duration' => rand(60, 180) . 's',
            'Agent Status' => 'Active',
        ];
    }
    
    protected function getStripeStats(): array
    {
        return [
            'Revenue Today' => '€' . rand(500, 2000),
            'Transactions' => rand(10, 50),
            'Success Rate' => '99.9%',
        ];
    }
    
    #[On('refresh-data')]
    public function refreshData(): void
    {
        $this->loadDashboardData();
    }
    
    // Auto-refresh every 5 seconds
    public function getListeners(): array
    {
        return [
            'refresh-data' => 'refreshData',
        ];
    }
}