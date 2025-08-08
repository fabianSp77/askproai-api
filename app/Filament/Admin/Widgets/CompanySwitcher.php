<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use App\Models\Company;

class CompanySwitcher extends Widget
{
    protected static string $view = 'filament.admin.widgets.company-switcher';
    
    protected static ?int $sort = -100; // Show at the top
    
    protected int | string | array $columnSpan = 'full';
    
    public static function canView(): bool
    {
        $user = auth()->user();
        
        // Only show for users who can access multiple companies
        return $user->hasRole(['super_admin', 'reseller_owner', 'reseller_admin', 'reseller_support']);
    }
    
    public function getCurrentCompany(): ?Company
    {
        return session('current_company') 
            ? Company::find(session('current_company')) 
            : auth()->user()->company;
    }
    
    public function getAccessibleCompanies(): Collection
    {
        $user = auth()->user();
        
        if ($user->hasRole('super_admin')) {
            return Company::orderBy('name')->get();
        }
        
        if ($user->hasRole(['reseller_owner', 'reseller_admin', 'reseller_support'])) {
            $companies = collect([$user->company]);
            
            // Add child companies if user's company is a reseller
            if ($user->company && $user->company->isReseller()) {
                $companies = $companies->merge($user->company->childCompanies);
            }
            
            return $companies->sortBy('name');
        }
        
        return collect([$user->company]);
    }
    
    public function switchCompany(int $companyId): void
    {
        $user = auth()->user();
        $company = Company::find($companyId);
        
        if (!$company) {
            return;
        }
        
        // Check if user has access to this company
        if ($user->hasRole('super_admin') || 
            $company->id === $user->company_id ||
            ($user->company && $company->parent_company_id === $user->company_id)) {
            
            session(['current_company' => $companyId]);
            
            // Redirect to refresh the page with new context
            redirect()->route('filament.admin.pages.dashboard');
        }
    }
}