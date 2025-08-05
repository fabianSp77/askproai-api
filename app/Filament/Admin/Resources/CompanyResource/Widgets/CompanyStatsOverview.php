<?php

namespace App\Filament\Admin\Resources\CompanyResource\Widgets;

use App\Models\Branch;
use App\Models\Call;
use App\Models\PhoneNumber;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Appointment;
use App\Services\BalanceMonitoringService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class CompanyStatsOverview extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }

        // Filialen
        $branchCount = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('company_id', $this->record->id)
            ->count();

        // Mitarbeiter
        $staffCount = Staff::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('company_id', $this->record->id)
            ->where('active', true)
            ->count();

        // Kunden
        $customerCount = Customer::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('company_id', $this->record->id)
            ->count();

        // Anrufe heute
        $callsToday = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('company_id', $this->record->id)
            ->whereDate('created_at', today())
            ->count();

        // Anrufe diesen Monat
        $callsThisMonth = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('company_id', $this->record->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Guthaben (wenn Prepaid aktiv)
        $balanceService = app(BalanceMonitoringService::class);
        $companyWithBalance = \App\Models\Company::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->with('prepaidBalance')
            ->find($this->record->id);
        
        $balanceStatus = $balanceService->getBalanceStatus($companyWithBalance);
        $currentBalance = $balanceStatus['effective_balance'] ?? 0;

        // Chart-Daten für die letzten 7 Tage
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayCount = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('company_id', $this->record->id)
                ->whereDate('created_at', $date)
                ->count();
            $chartData[] = $dayCount;
        }

        return [
            Stat::make('Filialen', $branchCount)
                ->description('Aktive Standorte')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('success'),
                
            Stat::make('Mitarbeiter', $staffCount)
                ->description('Aktive Mitarbeiter')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
                
            Stat::make('Kunden', $customerCount)
                ->description('Registrierte Kunden')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),
                
            Stat::make('Anrufe heute', $callsToday)
                ->description($callsThisMonth . ' diesen Monat')
                ->descriptionIcon('heroicon-m-phone')
                ->color('info')
                ->chart($chartData),
                
            Stat::make('Guthaben', number_format($currentBalance, 2, ',', '.') . ' €')
                ->description($balanceStatus['available_minutes'] ?? 0 . ' Minuten verfügbar')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color($currentBalance > 50 ? 'success' : ($currentBalance > 10 ? 'warning' : 'danger')),
        ];
    }
}