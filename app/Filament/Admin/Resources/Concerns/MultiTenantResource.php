<?php

namespace App\Filament\Admin\Resources\Concerns;

use Filament\Tables;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Branch;
use App\Models\Company;

trait MultiTenantResource
{
    /**
     * Get base query with multi-tenant filtering
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        
        // Super admins and resellers see everything
        if ($user && ($user->hasRole('super_admin') || $user->hasRole('reseller'))) {
            return $query;
        }
        
        // Filter by user's company
        if ($user && $user->company_id) {
            // Check if model has company_id column
            $model = $query->getModel();
            $table = $model->getTable();
            
            // Check if the table actually has a company_id column
            if (\Schema::hasColumn($table, 'company_id')) {
                $query->where($table . '.company_id', $user->company_id);
            }
        }
        
        return $query;
    }
    
    /**
     * Get standard multi-tenant filters
     */
    public static function getMultiTenantFilters(): array
    {
        $user = auth()->user();
        $filters = [];
        
        // Company filter (only for super admins)
        if ($user && ($user->hasRole('super_admin') || $user->hasRole('reseller'))) {
            $filters[] = Tables\Filters\SelectFilter::make('company_id')
                ->label('Unternehmen')
                ->relationship('company', 'name')
                ->searchable()
                ->preload()
                ->indicator('Unternehmen');
        }
        
        // Branch filter (for everyone, but filtered by company for non-admins)
        $filters[] = Tables\Filters\SelectFilter::make('branch_id')
            ->label('Filiale')
            ->options(function () use ($user) {
                $query = Branch::query();
                
                // Filter branches by company for non-admins
                if ($user && !$user->hasRole('super_admin') && !$user->hasRole('reseller')) {
                    $query->where('company_id', $user->company_id);
                }
                
                return $query->pluck('name', 'id');
            })
            ->searchable()
            ->indicator('Filiale');
            
        return $filters;
    }
    
    /**
     * Get company badge column
     */
    public static function getCompanyColumn(): Tables\Columns\TextColumn
    {
        $user = auth()->user();
        
        return Tables\Columns\TextColumn::make('company.name')
            ->label('Unternehmen')
            ->badge()
            ->color('gray')
            ->icon('heroicon-m-building-office')
            ->searchable()
            ->sortable()
            ->visible(fn () => $user && ($user->hasRole('super_admin') || $user->hasRole('reseller')))
            ->toggleable();
    }
    
    /**
     * Get branch badge column
     */
    public static function getBranchColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('branch.name')
            ->label('Filiale')
            ->badge()
            ->color('info')
            ->icon('heroicon-m-building-office-2')
            ->searchable()
            ->sortable()
            ->placeholder('Nicht zugeordnet')
            ->toggleable();
    }
    
    /**
     * Get multi-tenant form schema components
     */
    public static function getMultiTenantFormSchema(): array
    {
        $user = auth()->user();
        $schema = [];
        
        // Company select (only for super admins)
        if ($user && ($user->hasRole('super_admin') || $user->hasRole('reseller'))) {
            $schema[] = Forms\Components\Select::make('company_id')
                ->label('Unternehmen')
                ->relationship('company', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->reactive()
                ->afterStateUpdated(fn (Forms\Set $set) => $set('branch_id', null));
        }
        
        // Branch select (filtered by company)
        $schema[] = Forms\Components\Select::make('branch_id')
            ->label('Filiale')
            ->options(function (Forms\Get $get) use ($user) {
                $companyId = $get('company_id') ?? $user->company_id;
                
                if (!$companyId) {
                    return [];
                }
                
                return Branch::where('company_id', $companyId)
                    ->pluck('name', 'id');
            })
            ->searchable()
            ->preload()
            ->required(fn () => static::isBranchRequired());
            
        return $schema;
    }
    
    /**
     * Override this method in resources to determine if branch is required
     */
    protected static function isBranchRequired(): bool
    {
        return false;
    }
    
    /**
     * Add multi-tenant eager loading
     */
    public static function getMultiTenantRelations(): array
    {
        return ['company', 'branch'];
    }
}