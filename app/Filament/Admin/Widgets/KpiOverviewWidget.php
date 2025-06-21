<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class KpiOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = '60s';
    
    protected function getStats(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        
        // Cache key basierend auf Company ID
        $companyId = auth()->user()->company_id ?? null;
        $cacheKey = "kpi_stats_{$companyId}_{$today->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 60, function () use ($today, $yesterday, $companyId) {
            // Optimierte Single Query für alle Metriken
            $todayStats = DB::table('appointments')
                ->selectRaw('
                    COUNT(*) as total_appointments,
                    SUM(CASE WHEN status = "completed" THEN COALESCE(price, 0) ELSE 0 END) as revenue,
                    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_appointments
                ')
                ->when($companyId, function ($q) use ($companyId) {
                    $q->whereExists(function ($q) use ($companyId) {
                        $q->select(DB::raw(1))
                            ->from('branches')
                            ->whereColumn('branches.id', 'appointments.branch_id')
                            ->where('branches.company_id', $companyId);
                    });
                })
                ->whereDate('starts_at', $today)
                ->first();
            
            // Gestern für Vergleich
            $yesterdayRevenue = DB::table('appointments')
                ->when($companyId, function ($q) use ($companyId) {
                    $q->whereExists(function ($q) use ($companyId) {
                        $q->select(DB::raw(1))
                            ->from('branches')
                            ->whereColumn('branches.id', 'appointments.branch_id')
                            ->where('branches.company_id', $companyId);
                    });
                })
                ->whereDate('starts_at', $yesterday)
                ->where('status', 'completed')
                ->sum('price');
            
            // Anrufe heute
            $callStats = DB::table('calls')
                ->selectRaw('
                    COUNT(*) as total_calls,
                    SUM(CASE WHEN appointment_id IS NOT NULL THEN 1 ELSE 0 END) as converted_calls
                ')
                ->when($companyId, function ($q) use ($companyId) {
                    $q->whereExists(function ($q) use ($companyId) {
                        $q->select(DB::raw(1))
                            ->from('branches')
                            ->whereColumn('branches.id', 'calls.branch_id')
                            ->where('branches.company_id', $companyId);
                    });
                })
                ->whereDate('created_at', $today)
                ->first();
            
            // Neue Kunden heute
            $newCustomers = Customer::query()
                ->when($companyId, function ($q) use ($companyId) {
                    $q->whereHas('appointments.branch', function ($q) use ($companyId) {
                        $q->where('company_id', $companyId);
                    });
                })
                ->whereDate('created_at', $today)
                ->count();
            
            // Berechne Trends
            $revenue = $todayStats->revenue ?? 0;
            $revenueTrend = $yesterdayRevenue > 0 
                ? round((($revenue - $yesterdayRevenue) / $yesterdayRevenue) * 100)
                : 0;
                
            $conversionRate = $callStats->total_calls > 0
                ? round(($callStats->converted_calls / $callStats->total_calls) * 100)
                : 0;
            
            return [
                Stat::make('Umsatz heute', number_format($revenue, 2, ',', '.') . ' €')
                    ->description($revenueTrend >= 0 ? '↑ +' . $revenueTrend . '%' : '↓ ' . $revenueTrend . '%')
                    ->descriptionIcon($revenueTrend >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                    ->color($revenueTrend >= 0 ? 'success' : 'danger'),
                    
                Stat::make('Anrufe', $callStats->total_calls)
                    ->description($callStats->converted_calls . ' konvertiert')
                    ->descriptionIcon('heroicon-o-phone')
                    ->color('primary'),
                    
                Stat::make('Konversion', $conversionRate . '%')
                    ->description('Anruf → Termin')
                    ->descriptionIcon('heroicon-o-arrow-path')
                    ->color($conversionRate >= 40 ? 'success' : ($conversionRate >= 25 ? 'warning' : 'danger')),
                    
                Stat::make('Neue Kunden', $newCustomers)
                    ->description('Heute registriert')
                    ->descriptionIcon('heroicon-o-user-plus')
                    ->color($newCustomers > 0 ? 'success' : 'gray'),
            ];
        });
    }
}