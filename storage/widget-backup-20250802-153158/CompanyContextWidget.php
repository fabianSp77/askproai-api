<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class CompanyContextWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.company-context';
    protected static ?int $sort = -100;
    protected int | string | array $columnSpan = 'full';
    
    public function getCompany()
    {
        return Auth::user()?->company;
    }
    
    public function getBranch()
    {
        return Auth::user()?->branch;
    }
    
    public function canSwitchCompany(): bool
    {
        return Auth::user()?->hasRole('super_admin') || 
               Auth::user()?->companies()->count() > 1;
    }
    
    public function canSwitchBranch(): bool
    {
        $company = $this->getCompany();
        return $company && $company->branches()->count() > 1;
    }
    
    public function switchCompany(int $companyId): void
    {
        $user = Auth::user();
        $company = $user->companies()->find($companyId);
        
        if ($company) {
            session(['current_company_id' => $company->id]);
            session(['current_branch_id' => null]);
            $this->dispatch('company-switched');
            $this->redirect('/admin');
        }
    }
    
    public function switchBranch(int $branchId): void
    {
        $company = $this->getCompany();
        $branch = $company?->branches()->find($branchId);
        
        if ($branch) {
            session(['current_branch_id' => $branch->id]);
            $this->dispatch('branch-switched');
            $this->redirect('/admin');
        }
    }
}