<?php

namespace App\Filament\Admin\Traits;

use Illuminate\Support\Facades\Session;
use Livewire\Attributes\On;

/**
 * Global Filter Trait für Widget-Synchronisation
 * 
 * Ermöglicht Cross-Widget Filter-Updates über Livewire Events
 * Speichert Filter-Zustände in der Session für Persistenz
 */
trait HasGlobalFilters
{
    /**
     * Aktuelle Filter-Werte
     */
    public array $globalFilters = [];
    
    /**
     * Get current filters
     */
    public function getFilters(): array
    {
        return $this->globalFilters;
    }
    
    /**
     * Initialisiere Filter aus Session/Request
     */
    public function mountHasGlobalFilters(): void
    {
        $companyId = auth()->user()?->company_id;
        
        // Ensure we have a valid company_id
        if (!$companyId) {
            $this->globalFilters = [
                'company_id' => null,
                'period' => 'today',
                'branch_id' => null,
                'staff_id' => null,
                'service_id' => null,
                'date_from' => null,
                'date_to' => null,
            ];
            return;
        }
        
        $this->globalFilters = [
            'period' => request('period', session('dashboard.period', 'today')),
            'branch_id' => request('branch_id', session('dashboard.branch_id')),
            'company_id' => $companyId,
            'date_from' => request('date_from', session('dashboard.date_from')),
            'date_to' => request('date_to', session('dashboard.date_to')),
            'staff_id' => request('staff_id', session('dashboard.staff_id')),
            'service_id' => request('service_id', session('dashboard.service_id')),
        ];
        
        // Speichere in Session
        $this->persistFilters();
    }
    
    /**
     * Handle Filter-Updates von anderen Komponenten
     */
    #[On('global-filter-updated')]
    public function handleGlobalFilterUpdate(array $filters): void
    {
        $this->globalFilters = array_merge($this->globalFilters, $filters);
        $this->persistFilters();
        
        // Trigger Widget-Refresh
        $this->dispatch('refreshWidget');
        
        // Widget-spezifisches Update
        if (method_exists($this, 'onFiltersUpdated')) {
            $this->onFiltersUpdated();
        }
    }
    
    /**
     * Broadcast Filter-Änderung an alle Widgets
     */
    public function updateGlobalFilter(string $key, mixed $value): void
    {
        $this->globalFilters[$key] = $value;
        $this->persistFilters();
        
        // Broadcast an alle Komponenten
        $this->dispatch('global-filter-updated', filters: [$key => $value]);
    }
    
    /**
     * Periode schnell ändern
     */
    public function setPeriod(string $period): void
    {
        $this->updateGlobalFilter('period', $period);
    }
    
    /**
     * Branch/Filiale ändern
     */
    public function setBranch(?int $branchId): void
    {
        $this->updateGlobalFilter('branch_id', $branchId);
    }
    
    /**
     * Datum-Range setzen
     */
    public function setDateRange(?string $from, ?string $to): void
    {
        $this->globalFilters['date_from'] = $from;
        $this->globalFilters['date_to'] = $to;
        $this->globalFilters['period'] = 'custom';
        $this->persistFilters();
        
        $this->dispatch('global-filter-updated', filters: [
            'date_from' => $from,
            'date_to' => $to,
            'period' => 'custom',
        ]);
    }
    
    /**
     * Filter zurücksetzen
     */
    public function resetGlobalFilters(): void
    {
        $this->globalFilters = [
            'period' => 'today',
            'branch_id' => null,
            'company_id' => auth()->user()?->company_id,
            'date_from' => null,
            'date_to' => null,
            'staff_id' => null,
            'service_id' => null,
        ];
        
        $this->persistFilters();
        $this->dispatch('global-filter-updated', filters: $this->globalFilters);
    }
    
    /**
     * Speichere Filter in Session
     */
    protected function persistFilters(): void
    {
        foreach ($this->globalFilters as $key => $value) {
            if ($key !== 'company_id') { // Company ID nicht in Session speichern
                Session::put("dashboard.{$key}", $value);
            }
        }
    }
    
    /**
     * Get Date Range basierend auf Period
     */
    public function getDateRangeFromFilters(): array
    {
        $period = $this->globalFilters['period'] ?? 'today';
        
        if ($period === 'custom' && 
            isset($this->globalFilters['date_from']) && 
            isset($this->globalFilters['date_to'])) {
            return [
                \Carbon\Carbon::parse($this->globalFilters['date_from'])->startOfDay(),
                \Carbon\Carbon::parse($this->globalFilters['date_to'])->endOfDay(),
            ];
        }
        
        return match($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->yesterday()->startOfDay(), now()->yesterday()->endOfDay()],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            'last_week' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'this_quarter' => [now()->firstOfQuarter(), now()->lastOfQuarter()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };
    }
    
    /**
     * Get aktive Filter als Query-Constraints
     */
    public function applyGlobalFilters($query)
    {
        // Company Filter (Multi-Tenancy)
        if (isset($this->globalFilters['company_id']) && $this->globalFilters['company_id']) {
            $query->where('company_id', $this->globalFilters['company_id']);
        }
        
        // Branch Filter
        if (isset($this->globalFilters['branch_id']) && $this->globalFilters['branch_id']) {
            $query->where('branch_id', $this->globalFilters['branch_id']);
        }
        
        // Staff Filter
        if (isset($this->globalFilters['staff_id']) && $this->globalFilters['staff_id']) {
            $query->where('staff_id', $this->globalFilters['staff_id']);
        }
        
        // Service Filter
        if (isset($this->globalFilters['service_id']) && $this->globalFilters['service_id']) {
            $query->where('service_id', $this->globalFilters['service_id']);
        }
        
        // Date Range Filter
        $dateRange = $this->getDateRangeFromFilters();
        $dateColumn = $this->getDateColumnForModel($query->getModel());
        
        if ($dateColumn) {
            $query->whereBetween($dateColumn, $dateRange);
        }
        
        return $query;
    }
    
    /**
     * Bestimme relevante Datums-Spalte basierend auf Model
     */
    protected function getDateColumnForModel($model): ?string
    {
        $modelClass = get_class($model);
        
        return match($modelClass) {
            \App\Models\Appointment::class => 'starts_at',
            \App\Models\Call::class => 'created_at',
            \App\Models\Customer::class => 'created_at',
            default => 'created_at',
        };
    }
    
    /**
     * Render Period Pills für UI
     */
    public function getPeriodOptions(): array
    {
        return [
            'today' => ['label' => 'Heute', 'icon' => 'heroicon-o-calendar'],
            'yesterday' => ['label' => 'Gestern', 'icon' => 'heroicon-o-calendar'],
            'this_week' => ['label' => 'Diese Woche', 'icon' => 'heroicon-o-calendar-days'],
            'last_week' => ['label' => 'Letzte Woche', 'icon' => 'heroicon-o-calendar-days'],
            'this_month' => ['label' => 'Dieser Monat', 'icon' => 'heroicon-o-calendar-days'],
            'last_month' => ['label' => 'Letzter Monat', 'icon' => 'heroicon-o-calendar-days'],
            'this_quarter' => ['label' => 'Dieses Quartal', 'icon' => 'heroicon-o-chart-bar'],
            'this_year' => ['label' => 'Dieses Jahr', 'icon' => 'heroicon-o-chart-bar'],
            'custom' => ['label' => 'Benutzerdefiniert', 'icon' => 'heroicon-o-adjustments-horizontal'],
        ];
    }
}