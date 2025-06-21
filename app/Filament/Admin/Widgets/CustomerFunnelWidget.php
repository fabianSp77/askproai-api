<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Traits\HasGlobalFilters;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

/**
 * Customer Funnel Widget
 * 
 * Zeigt den Conversion-Funnel:
 * Anrufe → Termine → Kunden
 */
class CustomerFunnelWidget extends Widget
{
    use HasGlobalFilters;
    protected static string $view = 'filament.admin.widgets.customer-funnel';
    
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];
    
    protected static ?int $sort = 2;
    
    public function mount(): void
    {
        $this->mountHasGlobalFilters();
    }
    
    #[On('refreshWidget')]
    public function refresh(): void
    {
        // Trigger Livewire refresh
    }
    
    public function getFunnelData(): array
    {
        // Ensure globalFilters is initialized
        if (!isset($this->globalFilters['company_id'])) {
            return [
                [
                    'label' => 'Anrufe',
                    'value' => 0,
                    'icon' => 'heroicon-o-phone',
                    'color' => 'primary',
                    'percentage' => 100,
                ],
                [
                    'label' => 'Termine gebucht',
                    'value' => 0,
                    'icon' => 'heroicon-o-calendar',
                    'color' => 'info',
                    'percentage' => 0,
                    'conversion_from_previous' => 0,
                ],
                [
                    'label' => 'Durchgeführt',
                    'value' => 0,
                    'icon' => 'heroicon-o-check-circle',
                    'color' => 'success',
                    'percentage' => 0,
                    'conversion_from_previous' => 0,
                ],
                [
                    'label' => 'Neue Kunden',
                    'value' => 0,
                    'icon' => 'heroicon-o-user-plus',
                    'color' => 'success',
                    'percentage' => 0,
                    'conversion_from_previous' => 0,
                ],
            ];
        }
        
        $cacheKey = 'customer_funnel_' . md5(serialize($this->globalFilters));
        
        return Cache::remember($cacheKey, 300, function() {
            // Use global filters
            $dateRange = $this->getDateRangeFromFilters();
            
            // Funnel-Daten berechnen
            $query = Call::where('company_id', $this->globalFilters['company_id']);
            if (isset($this->globalFilters['branch_id']) && $this->globalFilters['branch_id']) {
                $query->where('branch_id', $this->globalFilters['branch_id']);
            }
            $totalCalls = $query->whereBetween('created_at', $dateRange)->count();
                
            $query = Call::where('company_id', $this->globalFilters['company_id']);
            if (isset($this->globalFilters['branch_id']) && $this->globalFilters['branch_id']) {
                $query->where('branch_id', $this->globalFilters['branch_id']);
            }
            $callsWithAppointment = $query->whereBetween('created_at', $dateRange)
                ->whereHas('appointment')
                ->count();
                
            $query = Appointment::where('company_id', $this->globalFilters['company_id']);
            if (isset($this->globalFilters['branch_id']) && $this->globalFilters['branch_id']) {
                $query->where('branch_id', $this->globalFilters['branch_id']);
            }
            $appointmentsFromCalls = $query->whereBetween('created_at', $dateRange)
                ->whereNotNull('call_id')
                ->count();
                
            $query = Appointment::where('company_id', $this->globalFilters['company_id']);
            if (isset($this->globalFilters['branch_id']) && $this->globalFilters['branch_id']) {
                $query->where('branch_id', $this->globalFilters['branch_id']);
            }
            $completedAppointments = $query->whereBetween('starts_at', $dateRange)
                ->where('status', 'completed')
                ->count();
                
            $newCustomers = Customer::where('company_id', $this->globalFilters['company_id'])
                ->whereBetween('created_at', $dateRange)
                ->count();
            
            return [
                [
                    'label' => 'Anrufe',
                    'value' => $totalCalls,
                    'icon' => 'heroicon-o-phone',
                    'color' => 'primary',
                    'percentage' => 100,
                ],
                [
                    'label' => 'Termine gebucht',
                    'value' => $appointmentsFromCalls,
                    'icon' => 'heroicon-o-calendar',
                    'color' => 'info',
                    'percentage' => $totalCalls > 0 ? round(($appointmentsFromCalls / $totalCalls) * 100, 1) : 0,
                    'conversion_from_previous' => $totalCalls > 0 ? round(($appointmentsFromCalls / $totalCalls) * 100, 1) : 0,
                ],
                [
                    'label' => 'Durchgeführt',
                    'value' => $completedAppointments,
                    'icon' => 'heroicon-o-check-circle',
                    'color' => 'success',
                    'percentage' => $totalCalls > 0 ? round(($completedAppointments / $totalCalls) * 100, 1) : 0,
                    'conversion_from_previous' => $appointmentsFromCalls > 0 ? round(($completedAppointments / $appointmentsFromCalls) * 100, 1) : 0,
                ],
                [
                    'label' => 'Neue Kunden',
                    'value' => $newCustomers,
                    'icon' => 'heroicon-o-user-plus',
                    'color' => 'success',
                    'percentage' => $totalCalls > 0 ? round(($newCustomers / $totalCalls) * 100, 1) : 0,
                    'conversion_from_previous' => $completedAppointments > 0 ? round(($newCustomers / $completedAppointments) * 100, 1) : 0,
                ],
            ];
        });
    }
}