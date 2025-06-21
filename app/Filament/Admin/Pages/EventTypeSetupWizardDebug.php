<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class EventTypeSetupWizardDebug extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';
    protected static ?string $navigationLabel = 'Debug Wizard';
    protected static ?string $navigationGroup = 'Einrichtung & Konfiguration';
    protected static ?int $navigationSort = 16;
    protected static bool $shouldRegisterNavigation = false;
    
    protected static string $view = 'filament.admin.pages.event-type-setup-wizard-debug';
    
    public ?int $selectedCompany = null;
    public ?int $selectedBranch = null;
    public array $branches = [];
    
    public function mount(): void
    {
        $user = auth()->user();
        if ($user && $user->company_id) {
            $this->selectedCompany = $user->company_id;
            $this->loadBranches();
        }
    }
    
    public function updatedSelectedCompany($value): void
    {
        Log::info('Debug: Company selection changed', [
            'new_value' => $value,
            'old_branch' => $this->selectedBranch
        ]);
        
        $this->selectedBranch = null;
        $this->loadBranches();
    }
    
    protected function loadBranches(): void
    {
        if (!$this->selectedCompany) {
            $this->branches = [];
            return;
        }
        
        try {
            $branches = \App\Models\Branch::withoutGlobalScopes()
                ->where('company_id', $this->selectedCompany)
                ->where('is_active', true)
                ->pluck('name', 'id')
                ->toArray();
            
            $this->branches = $branches;
            
            Log::info('Debug: Branches loaded', [
                'company_id' => $this->selectedCompany,
                'count' => count($branches),
                'branches' => array_keys($branches)
            ]);
        } catch (\Exception $e) {
            Log::error('Debug: Failed to load branches', [
                'error' => $e->getMessage()
            ]);
            $this->branches = [];
        }
    }
}