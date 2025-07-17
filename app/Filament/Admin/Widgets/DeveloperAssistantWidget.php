<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Services\DeveloperAssistantService;
use Livewire\Attributes\On;
use Illuminate\Support\Str;

class DeveloperAssistantWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.developer-assistant';
    protected static ?int $sort = 10;
    protected static ?string $pollingInterval = null;
    
    public array $recentGenerations = [];
    public array $suggestions = [];
    public string $selectedTab = 'suggestions';
    
    public function mount(): void
    {
        $this->loadData();
    }
    
    public function loadData(): void
    {
        try {
            $assistant = app(DeveloperAssistantService::class);
            
            // Get development suggestions
            $result = $assistant->suggestNextSteps();
            if ($result['success']) {
                $this->suggestions = $result['suggestions']['immediate'] ?? [];
            }
            
            // Get recent generations from memory
            $memory = app(\App\Services\MemoryBankAutomationService::class);
            $recent = $memory->search('', 'code_generation', [], 5);
            
            if (!empty($recent['data']['results'])) {
                $this->recentGenerations = array_map(function ($item) {
                    $data = $item['value']['data'] ?? $item['value'];
                    return [
                        'type' => $data['type'] ?? 'unknown',
                        'description' => Str::limit($data['description'] ?? '', 50),
                        'timestamp' => $data['timestamp'] ?? '',
                        'id' => $item['id'] ?? ''
                    ];
                }, $recent['data']['results']);
            }
        } catch (\Exception $e) {
            // Silently fail
        }
    }
    
    public function generateCode(): void
    {
        $this->dispatch('open-modal', id: 'code-generation-modal');
    }
    
    public function analyzeCode(): void
    {
        $this->redirect(route('filament.admin.pages.code-analyzer'));
    }
    
    public function openDevTools(): void
    {
        $this->redirect(route('filament.admin.pages.developer-tools'));
    }
    
    public function switchTab(string $tab): void
    {
        $this->selectedTab = $tab;
    }
    
    #[On('refresh-assistant')]
    public function refresh(): void
    {
        $this->loadData();
    }
    
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'developer']) ?? false;
    }
}