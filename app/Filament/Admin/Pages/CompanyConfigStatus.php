<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Company;

class CompanyConfigStatus extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-information-circle';
    protected static ?string $navigationLabel = 'Config Status';
    protected static ?string $title = 'Company Configuration Status';
    protected static ?string $navigationGroup = 'Einstellungen';
    protected static ?int $navigationSort = 4;
    
    protected static string $view = 'filament.admin.pages.company-config-status';
    
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Deaktiviert - Use CompanyIntegrationPortal instead
    }
    
    public ?Company $company = null;
    public array $status = [];
    
    public static function canAccess(): bool
    {
        return auth()->check();
    }
    
    public function mount(): void
    {
        $user = auth()->user();
        
        // Get the company
        if ($user->hasRole('super_admin')) {
            $this->company = Company::first();
        } else {
            $this->company = Company::find($user->company_id);
        }
        
        if ($this->company) {
            $this->calculateStatus();
        }
    }
    
    protected function calculateStatus(): void
    {
        $this->status = [
            'basic' => [
                'name' => $this->company->name,
                'slug' => $this->company->slug,
                'active' => $this->company->is_active,
                'branches' => $this->company->branches()->count(),
                'phones' => $this->company->phoneNumbers()->count(),
            ],
            'calcom' => [
                'has_api_key' => !empty($this->company->calcom_api_key),
                'has_team_slug' => !empty($this->company->calcom_team_slug),
                'event_types' => $this->company->eventTypes()->count(),
            ],
            'retell' => [
                'has_api_key' => !empty($this->company->retell_api_key),
                'has_agent_id' => !empty($this->company->retell_agent_id),
                'calls_count' => $this->company->calls()->count(),
            ],
        ];
    }
    
    public function getSubheading(): ?string
    {
        return $this->company ? 'Status for: ' . $this->company->name : 'No company found';
    }
}