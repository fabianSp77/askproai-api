<?php

namespace App\Livewire;

use App\Services\BranchContextManager;
use Livewire\Component;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class GlobalBranchSelector extends Component
{
    public $currentBranchId;
    public $branches = [];
    public $showAllBranchesOption = false;
    public $showMobileMenu = false;
    public $isLoading = true;
    
    protected ?BranchContextManager $branchContext = null;
    
    public function boot()
    {
        $this->branchContext = app(BranchContextManager::class);
    }
    
    public function mount()
    {
        try {
            $this->isLoading = true;
            
            // Ensure branchContext is initialized
            if (!$this->branchContext) {
                $this->branchContext = app(BranchContextManager::class);
            }
            
            $this->loadBranches();
            
            $currentBranch = $this->branchContext->getCurrentBranch();
            $this->currentBranchId = $currentBranch?->id ?? '';
            
            Log::info('GlobalBranchSelector mounted', [
                'branches_loaded' => count($this->branches),
                'current_branch_id' => $this->currentBranchId
            ]);
        } catch (\Exception $e) {
            Log::error('GlobalBranchSelector mount error: ' . $e->getMessage());
            $this->branches = [];
            $this->currentBranchId = '';
        } finally {
            $this->isLoading = false;
        }
    }
    
    public function loadBranches()
    {
        try {
            // Ensure branchContext is initialized
            if (!$this->branchContext) {
                $this->branchContext = app(BranchContextManager::class);
            }
            
            $branches = $this->branchContext->getBranchesForUser();
            
            Log::info('GlobalBranchSelector: Loading branches', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'branches_count' => $branches->count(),
                'branches' => $branches->pluck('name', 'id')->toArray()
            ]);
            
            $this->branches = $branches->map(function ($branch) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'company_name' => $branch->company->name ?? '',
                    'is_active' => $branch->active,
                ];
            })->toArray();
            
            // Show "All Branches" option if user has access to multiple branches
            $this->showAllBranchesOption = count($this->branches) > 1;
        } catch (\Exception $e) {
            Log::error('GlobalBranchSelector: Error loading branches', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->branches = [];
            $this->showAllBranchesOption = false;
        }
    }
    
    public function switchBranch($branchId)
    {
        try {
            // Ensure branchContext is initialized
            if (!$this->branchContext) {
                $this->branchContext = app(BranchContextManager::class);
            }
            
            // Empty string means "All Branches"
            $branchId = $branchId === '' ? null : $branchId;
            
            if ($this->branchContext->setCurrentBranch($branchId)) {
                $this->currentBranchId = $branchId ?? '';
                
                // Show notification
                if ($branchId === null) {
                    Notification::make()
                        ->title('Ansicht geändert')
                        ->body('Sie sehen jetzt alle Filialen')
                        ->success()
                        ->send();
                } else {
                    $branch = collect($this->branches)->firstWhere('id', $branchId);
                    Notification::make()
                        ->title('Filiale gewechselt')
                        ->body('Aktuelle Filiale: ' . ($branch['name'] ?? 'Unbekannt'))
                        ->success()
                        ->send();
                }
                
                // Refresh the page to apply new context
                $this->dispatch('branch-switched', branchId: $branchId);
                
                // Force a page refresh to apply the new branch context everywhere
                $this->js('window.location.reload()');
            } else {
                Notification::make()
                    ->title('Fehler')
                    ->body('Sie haben keinen Zugriff auf diese Filiale')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error('Error switching branch: ' . $e->getMessage());
            Notification::make()
                ->title('Fehler')
                ->body('Fehler beim Wechseln der Filiale: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function handleBranchSwitch()
    {
        // This method exists to handle form submission
        // The actual switching is done by wire:click on individual buttons
    }
    
    public function render()
    {
        return view('livewire.global-branch-selector');
    }
}