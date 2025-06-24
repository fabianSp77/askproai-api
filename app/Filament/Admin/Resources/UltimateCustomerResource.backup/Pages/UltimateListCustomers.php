<?php

namespace App\Filament\Admin\Resources\UltimateCustomerResource\Pages;

use App\Filament\Admin\Resources\UltimateCustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Pagination\CursorPaginator;

class UltimateListCustomers extends ListRecords
{
    protected static string $resource = UltimateCustomerResource::class;
    
    protected static string $view = 'filament.admin.pages.ultra-customers-dashboard';
    
    protected function getHeaderActions(): array
    {
        return [];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [];
    }
    
    public function getHeading(): string
    {
        return ''; // Heading is in the custom view
    }
    
    protected function getViewData(): array
    {
        $stats = $this->calculateCustomerStats();
        
        return [
            'customers' => $this->getTableRecords(),
            'totalCustomers' => $stats['total'],
            'newCustomers' => $stats['new'],
            'vipCustomers' => $stats['vip'],
            'atRiskCustomers' => $stats['at_risk'],
        ];
    }
    
    protected function calculateCustomerStats(): array
    {
        $baseQuery = static::getResource()::getEloquentQuery();
        
        return [
            'total' => $baseQuery->clone()->count(),
            'new' => $baseQuery->clone()
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
            'vip' => $baseQuery->clone()
                ->where('customer_type', 'vip')
                ->orWhere('is_vip', true)
                ->count(),
            'at_risk' => $baseQuery->clone()
                ->whereDoesntHave('appointments', function($q) {
                    $q->where('starts_at', '>=', now()->subDays(90));
                })
                ->count(),
        ];
    }
    
    public function getTableRecords(): \Illuminate\Database\Eloquent\Collection|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Contracts\Pagination\CursorPaginator
    {
        return $this->getTableQuery()->paginate($this->getTableRecordsPerPage());
    }
    
    public function getDefaultTableRecordsPerPageSelectOption(): int
    {
        return 25;
    }
}