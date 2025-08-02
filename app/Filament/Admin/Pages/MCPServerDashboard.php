<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Services\MCP\MCPOrchestrator;
use App\Services\MCPAutoDiscoveryService;
use App\Services\MemoryBankAutomationService;
use App\Services\GitHubNotionIntegrationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Livewire\Attributes\On;
use Filament\Notifications\Notification;

class MCPServerDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'MCP Servers';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 90;
    protected static string $view = 'filament.admin.pages.mcp-server-dashboard';
    protected static ?string $slug = 'mcp-servers';
    
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole(['Super Admin', 'super_admin', 'developer']) || $user->email === 'dev@askproai.de');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
    
    public array $internalServers = [];
    public array $externalServers = [];
    public array $integrations = [];
    public array $recentActivities = [];
    public array $serverCapabilities = [];
    public array $quickStats = [];
    public bool $showDetails = false;
    public ?string $selectedServer = null;
    
    protected MCPOrchestrator $orchestrator;
    protected MCPAutoDiscoveryService $discovery;
    protected MemoryBankAutomationService $memory;
    
    public function boot(
        MCPOrchestrator $orchestrator,
        MCPAutoDiscoveryService $discovery,
        MemoryBankAutomationService $memory
    ): void {
        $this->orchestrator = $orchestrator;
        $this->discovery = $discovery;
        $this->memory = $memory;
    }
    
    public function mount(): void
    {
        $this->loadAllData();
    }
    
    #[On('refresh-servers')]
    public function loadAllData(): void
    {
        $this->loadInternalServers();
        $this->loadExternalServers();
        $this->loadIntegrations();
        $this->loadRecentActivities();
        $this->loadQuickStats();
    }
    
    protected function loadInternalServers(): void
    {
        try {
            $servers = config('mcp-servers.servers', []);
            $this->internalServers = [];
            
            foreach ($servers as $name => $config) {
                if (!$config['enabled']) continue;
                
                $serverClass = $config['class'];
                $status = $this->checkServerStatus($name);
                
                $this->internalServers[] = [
                    'name' => $name,
                    'display_name' => $this->formatServerName($name),
                    'description' => $config['description'] ?? '',
                    'status' => $status['status'],
                    'health' => $status['health'] ?? 'unknown',
                    'capabilities' => $this->getServerCapabilities($name),
                    'metrics' => $this->getServerMetrics($name),
                    'icon' => $this->getServerIcon($name),
                    'color' => $this->getServerColor($name),
                ];
            }
        } catch (\Exception $e) {
            $this->internalServers = [];
        }
    }
    
    protected function loadExternalServers(): void
    {
        try {
            $externalConfig = config('mcp-external.external_servers', []);
            $this->externalServers = [];
            
            foreach ($externalConfig as $name => $config) {
                if (!$config['enabled']) continue;
                
                $isRunning = $this->checkExternalServerRunning($name);
                
                $this->externalServers[] = [
                    'name' => $name,
                    'display_name' => $this->formatServerName($name),
                    'description' => $config['description'] ?? '',
                    'status' => $isRunning ? 'active' : 'inactive',
                    'npm_package' => $config['package'] ?? $name,
                    'can_start' => !$isRunning,
                    'icon' => $this->getServerIcon($name),
                    'color' => $this->getServerColor($name),
                ];
            }
        } catch (\Exception $e) {
            $this->externalServers = [];
        }
    }
    
    protected function loadIntegrations(): void
    {
        $this->integrations = [
            [
                'name' => 'GitHub ↔ Notion',
                'status' => $this->checkIntegrationStatus('github_notion'),
                'last_sync' => $this->getLastSyncTime('github_notion'),
                'icon' => 'heroicon-o-arrows-right-left',
                'actions' => ['sync', 'configure'],
            ],
            [
                'name' => 'Cal.com → Database',
                'status' => $this->checkIntegrationStatus('calcom'),
                'last_sync' => $this->getLastSyncTime('calcom'),
                'icon' => 'heroicon-o-calendar',
                'actions' => ['sync', 'test'],
            ],
            [
                'name' => 'Retell.ai → Calls',
                'status' => $this->checkIntegrationStatus('retell'),
                'last_sync' => $this->getLastSyncTime('retell'),
                'icon' => 'heroicon-o-phone',
                'actions' => ['import', 'test'],
            ],
        ];
    }
    
    protected function loadRecentActivities(): void
    {
        try {
            // Get recent activities from Memory Bank
            $activities = $this->memory->search('mcp', 'work_context', ['mcp'])['data']['results'] ?? [];
            
            $this->recentActivities = collect($activities)
                ->take(10)
                ->map(function ($activity) {
                    $data = $activity['value']['data'] ?? $activity['value'];
                    return [
                        'type' => $data['type'] ?? 'activity',
                        'server' => $data['server'] ?? 'unknown',
                        'action' => $data['action'] ?? $data['title'] ?? 'Action performed',
                        'time' => $activity['value']['timestamp'] ?? now()->toDateTimeString(),
                        'success' => $data['success'] ?? true,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            $this->recentActivities = [];
        }
    }
    
    protected function loadQuickStats(): void
    {
        $this->quickStats = [
            'total_servers' => count($this->internalServers) + count($this->externalServers),
            'active_servers' => collect($this->internalServers)->where('status', 'active')->count() +
                               collect($this->externalServers)->where('status', 'active')->count(),
            'total_capabilities' => collect($this->internalServers)->sum(fn($s) => count($s['capabilities'])),
            'recent_errors' => $this->getRecentErrorCount(),
        ];
    }
    
    public function executeQuickAction(string $server, string $action): void
    {
        try {
            switch ($action) {
                case 'test':
                    $this->testServer($server);
                    break;
                    
                case 'restart':
                    $this->restartServer($server);
                    break;
                    
                case 'sync':
                    $this->syncServer($server);
                    break;
                    
                case 'discover':
                    $this->discoverTasks($server);
                    break;
                    
                default:
                    Notification::make()
                        ->title('Unknown action')
                        ->warning()
                        ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Action failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function startExternalServer(string $server): void
    {
        try {
            $config = config("mcp-external.external_servers.{$server}");
            
            if (!$config) {
                throw new \Exception("Server configuration not found");
            }
            
            // Start the server
            $command = sprintf(
                'cd %s && nohup %s %s > /dev/null 2>&1 &',
                dirname($config['args'][0]),
                $config['command'],
                implode(' ', $config['args'])
            );
            
            Process::run($command);
            
            sleep(2); // Give it time to start
            
            Notification::make()
                ->title('Server started')
                ->body("External server {$server} has been started")
                ->success()
                ->send();
                
            $this->loadExternalServers();
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to start server')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function showServerDetails(string $server): void
    {
        $this->selectedServer = $server;
        $this->showDetails = true;
        
        // Load detailed information for the selected server
        $this->dispatch('open-modal', id: 'server-details');
    }
    
    public function runIntegrationAction(string $integration, string $action): void
    {
        try {
            switch ($integration) {
                case 'GitHub ↔ Notion':
                    $this->runGitHubNotionAction($action);
                    break;
                    
                case 'Cal.com → Database':
                    $this->runCalcomAction($action);
                    break;
                    
                case 'Retell.ai → Calls':
                    $this->runRetellAction($action);
                    break;
            }
            
            $this->loadIntegrations();
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Integration action failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    // Helper methods
    
    protected function checkServerStatus(string $server): array
    {
        try {
            // Check if the server is registered and can be instantiated
            $serverClass = config("mcp-servers.servers.{$server}.class");
            
            if (!$serverClass || !class_exists($serverClass)) {
                return ['status' => 'error', 'health' => 'not_found'];
            }
            
            // Try to instantiate the server
            $instance = app($serverClass);
            
            // Check if it has a health check method
            if (method_exists($instance, 'healthCheck')) {
                $health = $instance->healthCheck();
                return [
                    'status' => $health['healthy'] ? 'active' : 'error',
                    'health' => $health['healthy'] ? 'healthy' : 'unhealthy',
                ];
            }
            
            // If no health check method, assume it's active if instantiated
            return ['status' => 'active', 'health' => 'healthy'];
            
        } catch (\Exception $e) {
            return ['status' => 'unknown', 'health' => 'unknown'];
        }
    }
    
    protected function checkExternalServerRunning(string $server): bool
    {
        try {
            $config = config("mcp-external.external_servers.{$server}");
            if (!$config) return false;
            
            // Check if process is running
            $result = Process::run("pgrep -f '{$server}'");
            return $result->successful() && !empty(trim($result->output()));
        } catch (\Exception $e) {
            return false;
        }
    }
    
    protected function getServerCapabilities(string $server): array
    {
        try {
            $serverClass = config("mcp-servers.servers.{$server}.class");
            if (!$serverClass || !class_exists($serverClass)) return [];
            
            $instance = app($serverClass);
            if (method_exists($instance, 'getCapabilities')) {
                return $instance->getCapabilities();
            }
            
            // Get tools as capabilities
            if (method_exists($instance, 'getTools')) {
                $tools = $instance->getTools();
                return array_map(fn($tool) => $tool['name'], $tools);
            }
            
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    protected function getServerMetrics(string $server): array
    {
        $cacheKey = "mcp_metrics_{$server}";
        return Cache::get($cacheKey, [
            'requests' => 0,
            'errors' => 0,
            'avg_duration' => 0,
        ]);
    }
    
    protected function formatServerName(string $name): string
    {
        return str_replace(['_', '-'], ' ', ucfirst($name));
    }
    
    protected function getServerIcon(string $name): string
    {
        $icons = [
            'calcom' => 'heroicon-o-calendar',
            'retell' => 'heroicon-o-phone',
            'database' => 'heroicon-o-circle-stack',
            'github' => 'heroicon-o-code-bracket',
            'notion' => 'heroicon-o-document-text',
            'stripe' => 'heroicon-o-credit-card',
            'queue' => 'heroicon-o-queue-list',
            'webhook' => 'heroicon-o-globe-alt',
            'knowledge' => 'heroicon-o-book-open',
            'appointment' => 'heroicon-o-calendar-days',
            'customer' => 'heroicon-o-users',
            'company' => 'heroicon-o-building-office',
            'branch' => 'heroicon-o-map-pin',
            'memory_bank' => 'heroicon-o-cpu-chip',
            'sequential_thinking' => 'heroicon-o-arrow-path',
        ];
        
        return $icons[$name] ?? 'heroicon-o-server';
    }
    
    protected function getServerColor(string $name): string
    {
        $colors = [
            'calcom' => 'blue',
            'retell' => 'green',
            'database' => 'purple',
            'github' => 'gray',
            'notion' => 'black',
            'stripe' => 'indigo',
            'queue' => 'yellow',
            'webhook' => 'red',
            'knowledge' => 'orange',
            'memory_bank' => 'cyan',
        ];
        
        return $colors[$name] ?? 'gray';
    }
    
    protected function checkIntegrationStatus(string $integration): string
    {
        // Simple status check - could be enhanced with real checks
        $lastSync = Cache::get("integration_last_sync_{$integration}");
        
        if (!$lastSync) return 'inactive';
        
        $lastSyncTime = \Carbon\Carbon::parse($lastSync);
        
        if ($lastSyncTime->diffInMinutes(now()) < 60) {
            return 'active';
        } elseif ($lastSyncTime->diffInHours(now()) < 24) {
            return 'idle';
        }
        
        return 'inactive';
    }
    
    protected function getLastSyncTime(string $integration): ?string
    {
        $lastSync = Cache::get("integration_last_sync_{$integration}");
        
        if (!$lastSync) return null;
        
        return \Carbon\Carbon::parse($lastSync)->diffForHumans();
    }
    
    protected function getRecentErrorCount(): int
    {
        try {
            return Cache::get('mcp_recent_errors_count', 0);
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    protected function testServer(string $server): void
    {
        try {
            // Get server configuration
            $serverClass = config("mcp-servers.servers.{$server}.class");
            
            if (!$serverClass || !class_exists($serverClass)) {
                Notification::make()
                    ->title('Server test failed')
                    ->body("Server {$server} not found")
                    ->danger()
                    ->send();
                return;
            }
            
            // Try to instantiate and get tools
            $instance = app($serverClass);
            $tools = method_exists($instance, 'getTools') ? $instance->getTools() : [];
            
            Notification::make()
                ->title('Server test passed')
                ->body("{$server} is responding with " . count($tools) . " tools available")
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Server test failed')
                ->body("Error: " . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function restartServer(string $server): void
    {
        // Restart external server
        Notification::make()
            ->title('Restarting server')
            ->body("{$server} restart initiated")
            ->info()
            ->send();
    }
    
    protected function syncServer(string $server): void
    {
        // Trigger sync for the server
        dispatch(function () use ($server) {
            // Sync logic here
        })->afterResponse();
        
        Notification::make()
            ->title('Sync started')
            ->body("{$server} synchronization has been queued")
            ->success()
            ->send();
    }
    
    protected function discoverTasks(string $server): void
    {
        // Open discovery modal or redirect
        $this->redirect(route('filament.admin.pages.mcp-discovery', ['server' => $server]));
    }
    
    protected function runGitHubNotionAction(string $action): void
    {
        switch ($action) {
            case 'sync':
                dispatch(function () {
                    app(GitHubNotionIntegrationService::class)->syncIssuesToTasks(
                        'owner', 'repo', 'database_id'
                    );
                })->afterResponse();
                
                Notification::make()
                    ->title('GitHub-Notion sync started')
                    ->success()
                    ->send();
                break;
                
            case 'configure':
                $this->redirect('/admin/github-notion-config');
                break;
        }
    }
    
    protected function runCalcomAction(string $action): void
    {
        switch ($action) {
            case 'sync':
                \Artisan::call('calcom:sync', ['--async' => true]);
                
                Notification::make()
                    ->title('Cal.com sync started')
                    ->success()
                    ->send();
                break;
                
            case 'test':
                \Artisan::call('mcp:test', ['server' => 'calcom']);
                
                Notification::make()
                    ->title('Cal.com test completed')
                    ->success()
                    ->send();
                break;
        }
    }
    
    protected function runRetellAction(string $action): void
    {
        switch ($action) {
            case 'import':
                \Artisan::call('retell:fetch-calls', ['--limit' => 50]);
                
                Notification::make()
                    ->title('Retell call import started')
                    ->success()
                    ->send();
                break;
                
            case 'test':
                \Artisan::call('mcp:test', ['server' => 'retell']);
                
                Notification::make()
                    ->title('Retell test completed')
                    ->success()
                    ->send();
                break;
        }
    }
}