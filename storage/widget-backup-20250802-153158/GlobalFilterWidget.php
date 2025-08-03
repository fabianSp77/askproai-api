<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Traits\HasGlobalFilters;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

/**
 * Globales Filter-Widget
 * 
 * Zeigt Filter-Optionen die über alle Widgets synchronisiert werden:
 * - Zeitraum (Period)
 * - Filiale (Branch)
 * - Mitarbeiter (Staff)
 * - Service
 * - Custom Date Range
 */
class GlobalFilterWidget extends Widget
{
    use HasGlobalFilters;
    
    protected static string $view = 'filament.admin.widgets.global-filter';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = -1; // Immer ganz oben
    
    public static function canView(): bool
    {
        return true; // Always show this widget
    }
    
    public bool $showDatePicker = false;
    
    public function mount(): void
    {
        $this->mountHasGlobalFilters();
        $this->showDatePicker = $this->globalFilters['period'] === 'custom';
    }
    
    /**
     * Period geändert
     */
    public function updatedGlobalFiltersPeriod($value): void
    {
        $this->showDatePicker = $value === 'custom';
        
        if ($value !== 'custom') {
            $this->globalFilters['date_from'] = null;
            $this->globalFilters['date_to'] = null;
        }
        
        $this->updateGlobalFilter('period', $value);
    }
    
    /**
     * Branch geändert
     */
    public function updatedGlobalFiltersBranchId($value): void
    {
        $this->updateGlobalFilter('branch_id', $value);
    }
    
    /**
     * Staff geändert
     */
    public function updatedGlobalFiltersStaffId($value): void
    {
        $this->updateGlobalFilter('staff_id', $value);
    }
    
    /**
     * Service geändert
     */
    public function updatedGlobalFiltersServiceId($value): void
    {
        $this->updateGlobalFilter('service_id', $value);
    }
    
    /**
     * Custom Date Range
     */
    public function applyDateRange(): void
    {
        if ($this->globalFilters['date_from'] && $this->globalFilters['date_to']) {
            $this->setDateRange(
                $this->globalFilters['date_from'],
                $this->globalFilters['date_to']
            );
        }
    }
    
    /**
     * Get Branches für Dropdown
     */
    public function getBranches(): array
    {
        if (!isset($this->globalFilters['company_id']) || !$this->globalFilters['company_id']) {
            return [];
        }
        
        return Cache::remember('filter_branches_' . $this->globalFilters['company_id'], 300, function() {
            return Branch::where('company_id', $this->globalFilters['company_id'])
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        });
    }
    
    /**
     * Get Staff für Dropdown
     */
    public function getStaff(): array
    {
        if (!isset($this->globalFilters['company_id']) || !$this->globalFilters['company_id']) {
            return [];
        }
        
        $cacheKey = 'filter_staff_' . $this->globalFilters['company_id'];
        
        if (isset($this->globalFilters['branch_id']) && $this->globalFilters['branch_id']) {
            $cacheKey .= '_' . $this->globalFilters['branch_id'];
        }
        
        return Cache::remember($cacheKey, 300, function() {
            $query = Staff::where('company_id', $this->globalFilters['company_id']);
                
            if (isset($this->globalFilters['branch_id']) && $this->globalFilters['branch_id']) {
                $query->whereHas('branches', function($q) {
                    $q->where('branches.id', $this->globalFilters['branch_id']);
                });
            }
            
            return $query->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        });
    }
    
    /**
     * Get Services für Dropdown
     */
    public function getServices(): array
    {
        if (!isset($this->globalFilters['company_id']) || !$this->globalFilters['company_id']) {
            return [];
        }
        
        $cacheKey = 'filter_services_' . $this->globalFilters['company_id'];
        
        if (isset($this->globalFilters['branch_id']) && $this->globalFilters['branch_id']) {
            $cacheKey .= '_' . $this->globalFilters['branch_id'];
        }
        
        return Cache::remember($cacheKey, 300, function() {
            $query = Service::where('company_id', $this->globalFilters['company_id']);
                
            if (isset($this->globalFilters['branch_id']) && $this->globalFilters['branch_id']) {
                $query->whereHas('branches', function($q) {
                    $q->where('branches.id', $this->globalFilters['branch_id']);
                });
            }
            
            return $query->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        });
    }
    
    /**
     * Get active Filter Count für Badge
     */
    public function getActiveFilterCount(): int
    {
        $count = 0;
        
        if (isset($this->globalFilters['period']) && $this->globalFilters['period'] !== 'today') $count++;
        if (isset($this->globalFilters['branch_id']) && $this->globalFilters['branch_id']) $count++;
        if (isset($this->globalFilters['staff_id']) && $this->globalFilters['staff_id']) $count++;
        if (isset($this->globalFilters['service_id']) && $this->globalFilters['service_id']) $count++;
        if ((isset($this->globalFilters['date_from']) && $this->globalFilters['date_from']) || 
            (isset($this->globalFilters['date_to']) && $this->globalFilters['date_to'])) $count++;
        
        return $count;
    }
    
    /**
     * Beschreibung der aktiven Filter
     */
    public function getActiveFilterDescription(): string
    {
        $parts = [];
        
        $periodOptions = $this->getPeriodOptions();
        $period = $this->globalFilters['period'] ?? 'today';
        $parts[] = $periodOptions[$period]['label'] ?? 'Heute';
        
        if (isset($this->globalFilters['branch_id']) && $this->globalFilters['branch_id']) {
            $branch = Branch::find($this->globalFilters['branch_id']);
            if ($branch) {
                $parts[] = $branch->name;
            }
        }
        
        if (isset($this->globalFilters['staff_id']) && $this->globalFilters['staff_id']) {
            $staff = Staff::find($this->globalFilters['staff_id']);
            if ($staff) {
                $parts[] = $staff->name;
            }
        }
        
        return implode(' • ', $parts);
    }
}