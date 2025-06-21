<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Appointment;
use App\Models\Call;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DailyOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    public ?int $companyId = null;
    public ?int $selectedBranchId = null;

    protected function getListeners(): array
    {
        return [
            'tenantFilterUpdated' => 'handleTenantFilterUpdate',
        ];
    }

    public function handleTenantFilterUpdate($companyId, $branchId): void
    {
        $this->companyId = $companyId;
        $this->selectedBranchId = $branchId;
    }

    protected function getStats(): array
    {
        $today = Carbon::today();
        
        // Termine heute
        $appointmentsToday = Appointment::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->whereDate('starts_at', $today)
            ->count();
            
        // Davon erledigt
        $completedToday = Appointment::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->whereDate('starts_at', $today)
            ->where('status', 'completed')
            ->count();
            
        // Termine morgen
        $appointmentsTomorrow = Appointment::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->whereDate('starts_at', $today->copy()->addDay())
            ->count();

        return [
            Stat::make('Termine heute', $appointmentsToday)
                ->description($completedToday . ' erledigt')
                ->color($appointmentsToday > 0 ? 'primary' : 'gray'),
                
            Stat::make('Termine morgen', $appointmentsTomorrow)
                ->description('Vorbereitung erforderlich')
                ->color($appointmentsTomorrow > 10 ? 'warning' : 'info'),
                
            Stat::make('Freie Slots heute', $this->getAvailableSlots())
                ->description('Noch buchbar')
                ->color('success'),
        ];
    }
    
    protected function getAvailableSlots(): int
    {
        // Vereinfachte Berechnung: 8h * 2 Slots * Anzahl Mitarbeiter - gebuchte Termine
        $totalSlots = 16 * 5; // Beispiel: 5 Mitarbeiter
        $bookedSlots = Appointment::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->whereDate('starts_at', today())
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->count();
            
        return max(0, $totalSlots - $bookedSlots);
    }
}