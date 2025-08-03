<?php

namespace App\Filament\Admin\Widgets;

use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

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
    
    // Memory optimization settings
    protected int $queryLimit = 1000;
    protected int $chunkSize = 100;
    
    // Prevent infinite loops - ENHANCED
    private static bool $isUpdating = false;
    private static array $dateRangeCache = [];
    private static int $refreshCount = 0;
    private static $lastRefreshTime = null;

    public function mount(): void
    {
        // Set company ID from auth user
        $this->companyId = auth()->user()?->company_id ?? null;

        // Apply any global filters that were set
        if (!empty(static::$globalFilters)) {
            $this->applyFilters(static::$globalFilters);
        }
    }

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

    public static function setFilters(array $filters): void
    {
        static::$globalFilters = $filters;
    }

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

    protected function applyDateFilter(Builder $query, string $column = 'created_at'): Builder
    {
        $dates = $this->getDateRange();

        if ($dates['start'] && $dates['end']) {
            $query->whereBetween($column, [$dates['start'], $dates['end']]);
        }

        return $query;
    }

    protected function applyBranchFilter(Builder $query, string $column = 'branch_id'): Builder
    {
        if ($this->branchFilter !== 'all' && !empty($this->branchFilter)) {
            $branchIds = explode(',', $this->branchFilter);
            $query->whereIn($column, $branchIds);
        }

        return $query;
    }

    protected function getDateRange(): array
    {
        // Cache key based on filter and dates
        $cacheKey = $this->dateFilter . '|' . ($this->startDate ?? '') . '|' . ($this->endDate ?? '');
        
        // Return cached result if available
        if (isset(self::$dateRangeCache[$cacheKey])) {
            return self::$dateRangeCache[$cacheKey];
        }
        
        $start = null;
        $end = null;

        try {
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
        } catch (\Exception $e) {
            \Log::error('FilterableWidget date range error: ' . $e->getMessage());
            // Fallback to safe defaults
            $start = Carbon::today();
            $end = Carbon::today()->endOfDay();
        }

        $result = [
            'start' => $start,
            'end' => $end,
        ];
        
        // Cache the result
        self::$dateRangeCache[$cacheKey] = $result;

        return $result;
    }

    public function refreshWithFilters(array $filters): void
    {
        // Rate limiting - prevent more than 1 refresh per second
        $now = microtime(true);
        if (self::$lastRefreshTime && ($now - self::$lastRefreshTime) < 1.0) {
            \Log::warning('FilterableWidget: Refresh rate limited');
            return;
        }
        
        // Prevent infinite loops
        if (self::$isUpdating) {
            \Log::warning('FilterableWidget: Preventing recursive refresh');
            return;
        }
        
        // Limit total refreshes
        self::$refreshCount++;
        if (self::$refreshCount > 10) {
            \Log::error('FilterableWidget: Too many refreshes, stopping');
            return;
        }
        
        try {
            self::$isUpdating = true;
            self::$lastRefreshTime = $now;
            $this->applyFilters($filters);
            $this->dispatch('$refresh');
        } finally {
            self::$isUpdating = false;
        }
    }

    protected function getListeners(): array
    {
        return [
            // Removed automatic refreshWidgets listener to prevent loops
            // 'refreshWidgets' => '$refresh',
            'updateFilters' => 'refreshWithFilters',
        ];
    }
}
