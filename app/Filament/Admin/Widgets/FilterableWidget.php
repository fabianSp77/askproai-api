<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use Carbon\Carbon;

abstract class FilterableWidget extends Widget
{
    protected static ?array $filters = null;
    
    public ?string $dateFilter = 'today';
    public ?int $companyId = null;
    public ?string $branchFilter = 'all';
    public ?string $startDate = null;
    public ?string $endDate = null;
    
    public function mount(): void
    {
        if (static::$filters) {
            $this->dateFilter = static::$filters['dateFilter'] ?? 'today';
            $this->companyId = static::$filters['companyId'] ?? auth()->user()->company_id;
            $this->branchFilter = static::$filters['branchFilter'] ?? 'all';
            $this->startDate = static::$filters['startDate'] ?? null;
            $this->endDate = static::$filters['endDate'] ?? null;
        } else {
            $this->companyId = auth()->user()->company_id;
        }
    }
    
    public static function setFilters(array $filters): void
    {
        static::$filters = $filters;
    }
    
    protected function getStartDate(): Carbon
    {
        return $this->startDate ? Carbon::parse($this->startDate) : Carbon::today();
    }
    
    protected function getEndDate(): Carbon
    {
        return $this->endDate ? Carbon::parse($this->endDate) : Carbon::today();
    }
    
    protected function applyDateFilter($query, $dateColumn = 'created_at')
    {
        return $query->whereBetween($dateColumn, [
            $this->getStartDate()->startOfDay(),
            $this->getEndDate()->endOfDay()
        ]);
    }
    
    protected function applyBranchFilter($query, $branchColumn = 'branch_id')
    {
        if ($this->branchFilter !== 'all') {
            return $query->where($branchColumn, $this->branchFilter);
        }
        return $query;
    }
}