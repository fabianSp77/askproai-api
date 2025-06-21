<?php

namespace App\Filament\Admin\Resources\InvoiceResource\Widgets;

use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InvoiceStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        
        // Current month revenue
        $currentMonthRevenue = Invoice::where('status', 'paid')
            ->whereBetween('paid_at', [$currentMonth, now()])
            ->sum('total');
            
        // Last month revenue
        $lastMonthRevenue = Invoice::where('status', 'paid')
            ->whereBetween('paid_at', [$lastMonth, $lastMonth->copy()->endOfMonth()])
            ->sum('total');
            
        // Open invoices
        $openInvoicesAmount = Invoice::where('status', 'open')
            ->sum('total');
            
        // Overdue invoices
        $overdueCount = Invoice::where('status', 'open')
            ->where('due_date', '<', now())
            ->count();
            
        // Draft invoices
        $draftInvoicesAmount = Invoice::where('status', 'draft')
            ->sum('total');
        $draftInvoicesCount = Invoice::where('status', 'draft')
            ->count();
            
        // Revenue change
        $revenueChange = $lastMonthRevenue > 0 
            ? round((($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;
            
        return [
            Stat::make('Umsatz (aktueller Monat)', '€ ' . number_format($currentMonthRevenue, 2, ',', '.'))
                ->description($revenueChange >= 0 
                    ? $revenueChange . '% mehr als letzten Monat'
                    : abs($revenueChange) . '% weniger als letzten Monat'
                )
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger')
                ->chart($this->getMonthlyRevenueChart()),
                
            Stat::make('Offene Rechnungen', '€ ' . number_format($openInvoicesAmount, 2, ',', '.'))
                ->description(Invoice::where('status', 'open')->count() . ' Rechnungen')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
                
            Stat::make('Überfällige Rechnungen', $overdueCount)
                ->description($overdueCount > 0 ? 'Aktion erforderlich' : 'Alles in Ordnung')
                ->descriptionIcon($overdueCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($overdueCount > 0 ? 'danger' : 'success'),
                
            Stat::make('Entwürfe', '€ ' . number_format($draftInvoicesAmount, 2, ',', '.'))
                ->description($draftInvoicesCount . ' Entwürfe - Potentieller Umsatz')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('gray'),
        ];
    }
    
    protected function getMonthlyRevenueChart(): array
    {
        $data = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $revenue = Invoice::where('status', 'paid')
                ->whereDate('paid_at', $date)
                ->sum('total');
            $data[] = $revenue;
        }
        
        return $data;
    }
}