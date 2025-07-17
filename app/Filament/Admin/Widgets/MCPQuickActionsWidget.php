<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Process;
use Livewire\Attributes\On;

class MCPQuickActionsWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.mcp-quick-actions';
    protected static ?int $sort = 5;
    protected static ?string $pollingInterval = null;
    
    public array $recentCommands = [];
    public bool $showCommandOutput = false;
    public string $commandOutput = '';
    
    public function mount(): void
    {
        $this->loadRecentCommands();
    }
    
    public function loadRecentCommands(): void
    {
        $this->recentCommands = cache()->get('mcp_recent_commands', []);
    }
    
    public function executeQuickAction(string $action): void
    {
        try {
            $command = '';
            $params = [];
            
            switch ($action) {
                case 'import_calls':
                    $command = 'mcp calls';
                    break;
                    
                case 'sync_calcom':
                    $command = 'calcom:sync';
                    break;
                    
                case 'check_health':
                    $command = 'mcp:health';
                    break;
                    
                case 'discover_task':
                    $this->dispatch('open-modal', id: 'mcp-discovery-modal');
                    return;
                    
                case 'view_dashboard':
                    $this->redirect(route('filament.admin.pages.mcp-servers'));
                    return;
                    
                default:
                    $this->dispatch('notify', [
                        'type' => 'error',
                        'message' => 'Unknown action: ' . $action
                    ]);
                    return;
            }
            
            // Execute command
            $result = Process::run("php artisan {$command}");
            
            if ($result->successful()) {
                $this->commandOutput = $result->output();
                $this->showCommandOutput = true;
                
                // Cache recent command
                $recent = cache()->get('mcp_recent_commands', []);
                array_unshift($recent, [
                    'command' => $command,
                    'action' => $action,
                    'timestamp' => now()->toDateTimeString(),
                    'success' => true
                ]);
                cache()->put('mcp_recent_commands', array_slice($recent, 0, 5), 3600);
                
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Command executed successfully!'
                ]);
            } else {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Command failed: ' . $result->errorOutput()
                ]);
            }
            
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    public function closeOutput(): void
    {
        $this->showCommandOutput = false;
        $this->commandOutput = '';
    }
    
    #[On('refresh-widget')]
    public function refresh(): void
    {
        $this->loadRecentCommands();
    }
    
    public static function canView(): bool
    {
        // Only show for admins or developers
        return auth()->user()?->hasAnyRole(['admin', 'developer']) ?? false;
    }
}