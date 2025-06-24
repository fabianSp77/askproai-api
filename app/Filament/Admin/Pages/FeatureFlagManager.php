<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Services\FeatureFlagService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class FeatureFlagManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationLabel = 'Feature Flags';
    protected static ?string $title = 'Feature Flag Management';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 50;
    
    protected static string $view = 'filament.admin.pages.feature-flag-manager';
    
    public array $flags = [];
    public array $companyOverrides = [];
    public bool $showStats = false;
    public ?string $selectedFlag = null;
    public array $flagStats = [];
    
    // Edit states
    public array $editStates = [];
    public array $rolloutPercentages = [];
    
    public function mount(): void
    {
        $this->loadFlags();
    }
    
    public function loadFlags(): void
    {
        $service = app(FeatureFlagService::class);
        $this->flags = $service->getAllFlags();
        
        // Initialize edit states and percentages
        foreach ($this->flags as $flag) {
            $this->editStates[$flag->key] = false;
            $this->rolloutPercentages[$flag->key] = $flag->rollout_percentage;
        }
        
        // Load company overrides if user has company context
        if ($companyId = auth()->user()->company_id ?? null) {
            $this->companyOverrides = $service->getCompanyOverrides($companyId);
        }
    }
    
    public function toggleFlag(string $key): void
    {
        $service = app(FeatureFlagService::class);
        
        $flag = collect($this->flags)->firstWhere('key', $key);
        $newState = !$flag->enabled;
        
        $service->createOrUpdate([
            'key' => $key,
            'enabled' => $newState
        ]);
        
        // Clear all caches
        Cache::tags(['feature_flags'])->flush();
        
        Notification::make()
            ->title('Feature Flag Updated')
            ->body("'{$flag->name}' has been " . ($newState ? 'enabled' : 'disabled'))
            ->success()
            ->send();
            
        $this->loadFlags();
    }
    
    public function updateRollout(string $key): void
    {
        $service = app(FeatureFlagService::class);
        
        $percentage = $this->rolloutPercentages[$key] ?? '0';
        
        // Validate percentage
        if (!is_numeric($percentage) || $percentage < 0 || $percentage > 100) {
            Notification::make()
                ->title('Invalid Rollout Percentage')
                ->body('Please enter a value between 0 and 100')
                ->danger()
                ->send();
            return;
        }
        
        $service->createOrUpdate([
            'key' => $key,
            'rollout_percentage' => $percentage
        ]);
        
        Notification::make()
            ->title('Rollout Updated')
            ->body("Rollout percentage set to {$percentage}%")
            ->success()
            ->send();
            
        $this->editStates[$key] = false;
        $this->loadFlags();
    }
    
    public function showFlagStats(string $key): void
    {
        $this->selectedFlag = $key;
        $this->flagStats = app(FeatureFlagService::class)->getUsageStats($key, 24);
        $this->showStats = true;
    }
    
    public function closeStats(): void
    {
        $this->showStats = false;
        $this->selectedFlag = null;
        $this->flagStats = [];
    }
    
    public function createCompanyOverride(string $key, bool $enabled): void
    {
        $companyId = auth()->user()->company_id ?? null;
        
        if (!$companyId) {
            Notification::make()
                ->title('No Company Context')
                ->body('You must be associated with a company to create overrides')
                ->warning()
                ->send();
            return;
        }
        
        app(FeatureFlagService::class)->setOverride(
            $key,
            $companyId,
            $enabled,
            'Manual override via admin panel'
        );
        
        Notification::make()
            ->title('Override Created')
            ->body('Company-specific override has been set')
            ->success()
            ->send();
            
        $this->loadFlags();
    }
    
    public function removeCompanyOverride(string $key): void
    {
        $companyId = auth()->user()->company_id ?? null;
        
        if (!$companyId) {
            return;
        }
        
        app(FeatureFlagService::class)->removeOverride($key, $companyId);
        
        Notification::make()
            ->title('Override Removed')
            ->body('Company-specific override has been removed')
            ->success()
            ->send();
            
        $this->loadFlags();
    }
    
    public function refreshFlags(): void
    {
        Cache::tags(['feature_flags'])->flush();
        $this->loadFlags();
        
        Notification::make()
            ->title('Flags Refreshed')
            ->body('Feature flag cache has been cleared')
            ->success()
            ->send();
    }
    
    public function emergencyKillSwitch(): void
    {
        app(FeatureFlagService::class)->emergencyDisableAll('Manual emergency disable from admin panel');
        
        Notification::make()
            ->title('EMERGENCY KILL SWITCH ACTIVATED')
            ->body('All feature flags have been disabled')
            ->danger()
            ->send();
            
        $this->loadFlags();
    }
    
    public static function canAccess(): bool
    {
        // Only super admins can manage feature flags
        return auth()->user()?->email === 'admin@example.com' || 
               auth()->user()?->hasRole('super_admin') ||
               auth()->user()?->id === 1;
    }
}