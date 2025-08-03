<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\Appointment;
use App\Models\Staff;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class OccupancyWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.occupancy-widget';
    protected int | string | array $columnSpan = 1;
    protected static ?int $sort = 3;

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

    protected function getViewData(): array
    {
        $cacheKey = "occupancy-{$this->companyId}-{$this->selectedBranchId}";
        
        $data = Cache::remember($cacheKey, 300, function () {
            return $this->calculateOccupancy();
        });

        return $data;
    }

    protected function calculateOccupancy(): array
    {
        $now = Carbon::now();
        $startOfWeek = $now->startOfWeek();
        $endOfWeek = $now->endOfWeek();
        
        // Basis: 8 Stunden pro Tag, 30 Minuten Slots
        $slotsPerDay = 16; // 8 Stunden * 2 Slots pro Stunde
        $workDays = 5; // Mo-Fr
        
        // Mitarbeiter zählen
        $staffCount = Staff::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('branches', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, function ($q) {
                $q->whereHas('branches', fn($q) => $q->where('branch_id', $this->selectedBranchId));
            })
            ->where('is_active', true)
            ->count();
        
        if ($staffCount === 0) {
            return [
                'today_occupancy' => 0,
                'week_occupancy' => 0,
                'peak_hours' => [],
                'available_slots_today' => 0,
                'total_slots_today' => 0,
                'staff_utilization' => [],
            ];
        }
        
        // Heute
        $totalSlotsToday = $staffCount * $slotsPerDay;
        $bookedSlotsToday = Appointment::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->whereDate('starts_at', today())
            ->whereIn('status', ['scheduled', 'confirmed', 'completed'])
            ->count();
        
        $todayOccupancy = $totalSlotsToday > 0 ? ($bookedSlotsToday / $totalSlotsToday) * 100 : 0;
        
        // Diese Woche
        $totalSlotsWeek = $staffCount * $slotsPerDay * $workDays;
        $bookedSlotsWeek = Appointment::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->whereBetween('starts_at', [$startOfWeek, $endOfWeek])
            ->whereIn('status', ['scheduled', 'confirmed', 'completed'])
            ->count();
        
        $weekOccupancy = $totalSlotsWeek > 0 ? ($bookedSlotsWeek / $totalSlotsWeek) * 100 : 0;
        
        // Peak Hours Analysis
        $peakHours = $this->calculatePeakHours();
        
        // Staff Utilization
        $staffUtilization = $this->calculateStaffUtilization();
        
        return [
            'today_occupancy' => round($todayOccupancy),
            'week_occupancy' => round($weekOccupancy),
            'peak_hours' => $peakHours,
            'available_slots_today' => $totalSlotsToday - $bookedSlotsToday,
            'total_slots_today' => $totalSlotsToday,
            'staff_utilization' => $staffUtilization,
            'trend' => $this->calculateTrend($todayOccupancy),
        ];
    }

    protected function calculatePeakHours(): array
    {
        $hourlyData = [];
        
        // Analysiere Termine der letzten 30 Tage nach Stunde
        $appointments = Appointment::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->where('starts_at', '>=', Carbon::now()->subDays(30))
            ->whereIn('status', ['scheduled', 'confirmed', 'completed'])
            ->get();
        
        foreach ($appointments as $appointment) {
            $hour = Carbon::parse($appointment->starts_at)->hour;
            if (!isset($hourlyData[$hour])) {
                $hourlyData[$hour] = 0;
            }
            $hourlyData[$hour]++;
        }
        
        // Top 3 Peak Hours
        arsort($hourlyData);
        $peakHours = array_slice($hourlyData, 0, 3, true);
        
        $formatted = [];
        foreach ($peakHours as $hour => $count) {
            $formatted[] = [
                'hour' => $hour . ':00',
                'count' => $count,
                'percentage' => round(($count / array_sum($hourlyData)) * 100),
            ];
        }
        
        return $formatted;
    }

    protected function calculateStaffUtilization(): array
    {
        $staff = Staff::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('branches', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, function ($q) {
                $q->whereHas('branches', fn($q) => $q->where('branch_id', $this->selectedBranchId));
            })
            ->where('is_active', true)
            ->limit(5)
            ->get();
        
        $utilization = [];
        
        foreach ($staff as $member) {
            $appointments = Appointment::where('staff_id', $member->id)
                ->whereDate('starts_at', today())
                ->whereIn('status', ['scheduled', 'confirmed', 'completed'])
                ->count();
            
            $utilizationRate = ($appointments / 16) * 100; // 16 slots per day
            
            $utilization[] = [
                'name' => $member->first_name . ' ' . substr($member->last_name, 0, 1) . '.',
                'rate' => round($utilizationRate),
                'appointments' => $appointments,
            ];
        }
        
        // Sortiere nach Auslastung
        usort($utilization, fn($a, $b) => $b['rate'] <=> $a['rate']);
        
        return $utilization;
    }

    protected function calculateTrend($currentOccupancy): array
    {
        // Vergleiche mit gestern
        $yesterdayOccupancy = Cache::get("occupancy-yesterday-{$this->companyId}-{$this->selectedBranchId}", $currentOccupancy);
        
        $diff = $currentOccupancy - $yesterdayOccupancy;
        
        return [
            'direction' => $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'stable'),
            'value' => abs(round($diff)),
        ];
    }
}