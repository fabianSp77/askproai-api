<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

abstract class FilterableWidget extends Widget
{
    // Filter properties
    public ?int $companyId = null;
    public string $dateFilter = 'last7days';
    public string $branchFilter = 'all';
    public ?string $startDate = null;
    public ?string $endDate = null;
    
    // Static filter storage for cross-widget communication
    protected static array $globalFilters = [];
    
    public function mount(): void
    {
        // Set company ID from auth user
        $this->companyId = auth()->user()->company_id ?? null;
        
        // Apply any global filters that were set
        if (!empty(static::$globalFilters)) {
            $this->applyFilters(static::$globalFilters);
        }
    }
    
    /**
     * Override the parent getViewData method to add our filter data
     */
    protected function getViewData(): array
    {
        return [
            'companyId' => $this->companyId,
            'dateFilter' => $this->dateFilter,
            'branchFilter' => $this->branchFilter,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ];
    }
    
    /**
     * Set filters globally for all widgets
     */
    public static function setFilters(array $filters): void
    {
        static::$globalFilters = $filters;
    }
    
    /**
     * Apply filters to this widget instance
     */
    public function applyFilters(array $filters): void
    {
        if (isset($filters['companyId'])) {
            $this->companyId = $filters['companyId'];
        }
        
        if (isset($filters['dateFilter'])) {
            $this->dateFilter = $filters['dateFilter'];
        }
        
        if (isset($filters['branchFilter'])) {
            $this->branchFilter = $filters['branchFilter'];
        }
        
        if (isset($filters['startDate'])) {
            $this->startDate = $filters['startDate'];
        }
        
        if (isset($filters['endDate'])) {
            $this->endDate = $filters['endDate'];
        }
    }
    
    /**
     * Apply date filter to a query
     */
    protected function applyDateFilter(Builder $query, string $column = 'created_at'): Builder
    {
        $dates = $this->getDateRange();
        
        if ($dates['start'] && $dates['end']) {
            $query->whereBetween($column, [$dates['start'], $dates['end']]);
        }
        
        return $query;
    }
    
    /**
     * Apply branch filter to a query
     */
    protected function applyBranchFilter(Builder $query, string $column = 'branch_id'): Builder
    {
        if ($this->branchFilter !== 'all' && !empty($this->branchFilter)) {
            $branchIds = explode(',', $this->branchFilter);
            $query->whereIn($column, $branchIds);
        }
        
        return $query;
    }
    
    /**
     * Get date range based on current filter
     */
    protected function getDateRange(): array
    {
        $start = null;
        $end = null;
        
        switch ($this->dateFilter) {
            case 'today':
                $start = Carbon::today();
                $end = Carbon::today()->endOfDay();
                break;
                
            case 'yesterday':
                $start = Carbon::yesterday();
                $end = Carbon::yesterday()->endOfDay();
                break;
                
            case 'last7days':
                $start = Carbon::now()->subDays(7)->startOfDay();
                $end = Carbon::now()->endOfDay();
                break;
                
            case 'last30days':
                $start = Carbon::now()->subDays(30)->startOfDay();
                $end = Carbon::now()->endOfDay();
                break;
                
            case 'thisMonth':
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                break;
                
            case 'lastMonth':
                $start = Carbon::now()->subMonth()->startOfMonth();
                $end = Carbon::now()->subMonth()->endOfMonth();
                break;
                
            case 'thisYear':
                $start = Carbon::now()->startOfYear();
                $end = Carbon::now()->endOfYear();
                break;
                
            case 'custom':
                if ($this->startDate && $this->endDate) {
                    $start = Carbon::parse($this->startDate)->startOfDay();
                    $end = Carbon::parse($this->endDate)->endOfDay();
                }
                break;
        }
        
        return [
            'start' => $start,
            'end' => $end,
        ];
    }
    
    /**
     * Refresh widget data when filters change
     */
    public function refreshWithFilters(array $filters): void
    {
        $this->applyFilters($filters);
        $this->dispatch('$refresh');
    }
    
    /**
     * Listen for filter updates
     */
    protected function getListeners(): array
    {
        return [
            'refreshWidgets' => '$refresh',
            'updateFilters' => 'refreshWithFilters',
        ];
    }
}