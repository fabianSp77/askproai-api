<?php

namespace App\Filament\Admin\Resources\BranchResource\Widgets;

use App\Models\Call;
use App\Models\PhoneNumber;
use App\Models\Staff;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class BranchStatsWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }

        // Hole alle Telefonnummern der Filiale
        $phoneNumbers = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('branch_id', $this->record->id)
            ->pluck('number')
            ->toArray();

        // Anrufe heute
        $callsToday = 0;
        if (!empty($phoneNumbers)) {
            $callsToday = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->whereIn('to_number', $phoneNumbers)
                ->whereDate('created_at', today())
                ->count();
        }

        // Anrufe diese Woche
        $callsThisWeek = 0;
        $minutesThisWeek = 0;
        if (!empty($phoneNumbers)) {
            $weekCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->whereIn('to_number', $phoneNumbers)
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            
            $callsThisWeek = $weekCalls->count();
            $minutesThisWeek = $weekCalls->sum('duration_sec') / 60;
        }

        // Mitarbeiter
        $staffCount = Staff::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('branch_id', $this->record->id)
            ->where('is_active', true)
            ->count();

        // Chart-Daten für die letzten 7 Tage
        $chartData = [];
        if (!empty($phoneNumbers)) {
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dayCount = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->whereIn('to_number', $phoneNumbers)
                    ->whereDate('created_at', $date)
                    ->count();
                $chartData[] = $dayCount;
            }
        } else {
            $chartData = [0, 0, 0, 0, 0, 0, 0];
        }

        return [
            Stat::make('Anrufe heute', $callsToday)
                ->description('Eingehende Anrufe')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart($chartData),
                
            Stat::make('Anrufe diese Woche', $callsThisWeek)
                ->description(round($minutesThisWeek, 1) . ' Minuten')
                ->descriptionIcon('heroicon-m-phone')
                ->color('primary')
                ->chart($this->getWeeklyCallChart($phoneNumbers)),
                
            Stat::make('Mitarbeiter', $staffCount)
                ->description('Aktive Mitarbeiter')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),
        ];
    }
    
    protected function getWeeklyCallChart(array $phoneNumbers): array
    {
        $chartData = [];
        
        if (empty($phoneNumbers)) {
            return [0, 0, 0, 0, 0, 0, 0];
        }
        
        // Hole Daten für jeden Tag der aktuellen Woche
        $startOfWeek = now()->startOfWeek();
        
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            
            // Nur bis heute zählen
            if ($date->isFuture()) {
                $chartData[] = 0;
            } else {
                $dayCount = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->whereIn('to_number', $phoneNumbers)
                    ->whereDate('created_at', $date)
                    ->count();
                $chartData[] = $dayCount;
            }
        }
        
        return $chartData;
    }
}