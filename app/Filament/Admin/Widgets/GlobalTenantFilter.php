<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Support\Facades\Session;

class GlobalTenantFilter extends Widget implements HasForms
{
    use InteractsWithForms;
    
    protected static string $view = 'filament.admin.widgets.global-tenant-filter';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = -1;
    
    public ?int $selectedCompanyId = null;
    public ?string $selectedBranchId = null;
    
    public function mount(): void
    {
        $user = auth()->user();
        
        // Set defaults from session or user
        $this->selectedCompanyId = Session::get('filter_company_id') ?? $user->company_id;
        $this->selectedBranchId = Session::get('filter_branch_id');
        
        // Initialize form
        $this->form->fill([
            'company_id' => $this->selectedCompanyId,
            'branch_id' => $this->selectedBranchId,
        ]);
    }
    
    protected function getFormSchema(): array
    {
        $user = auth()->user();
        $schema = [];
        
        // Company filter (only for admins)
        if ($user && ($user->hasRole('super_admin') || $user->hasRole('reseller'))) {
            $schema[] = Select::make('company_id')
                ->label('Unternehmen')
                ->options(Company::pluck('name', 'id'))
                ->placeholder('Alle Unternehmen')
                ->searchable()
                ->reactive()
                ->afterStateUpdated(function ($state) {
                    $this->selectedCompanyId = $state;
                    $this->selectedBranchId = null;
                    Session::put('filter_company_id', $state);
                    Session::forget('filter_branch_id');
                    $this->form->fill(['branch_id' => null]);
                    $this->emit('globalFilterUpdated');
                });
        }
        
        // Branch filter
        $schema[] = Select::make('branch_id')
            ->label('Filiale')
            ->options(function () use ($user) {
                $query = Branch::query();
                
                if ($this->selectedCompanyId) {
                    $query->where('company_id', $this->selectedCompanyId);
                } elseif (!$user->hasRole('super_admin') && !$user->hasRole('reseller')) {
                    $query->where('company_id', $user->company_id);
                }
                
                return $query->pluck('name', 'id');
            })
            ->placeholder('Alle Filialen')
            ->searchable()
            ->reactive()
            ->afterStateUpdated(function ($state) {
                $this->selectedBranchId = $state;
                Session::put('filter_branch_id', $state);
                $this->emit('globalFilterUpdated');
            });
            
        return $schema;
    }
    
    public function getFilters(): array
    {
        return [
            'company_id' => $this->selectedCompanyId,
            'branch_id' => $this->selectedBranchId,
        ];
    }
    
    public static function canView(): bool
    {
        return true;
    }
}