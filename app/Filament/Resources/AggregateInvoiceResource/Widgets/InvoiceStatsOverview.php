<?php

namespace App\Filament\Resources\AggregateInvoiceResource\Widgets;

use App\Models\AggregateInvoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InvoiceStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $openInvoices = AggregateInvoice::where('status', 'open')->count();
        $overdueInvoices = AggregateInvoice::overdue()->count();
        $openAmount = AggregateInvoice::where('status', 'open')->sum('total_cents') / 100;
        $paidThisMonth = AggregateInvoice::where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('total_cents') / 100;

        return [
            Stat::make('Offene Rechnungen', $openInvoices)
                ->description('Warten auf Zahlung')
                ->icon('heroicon-o-document-text')
                ->color($overdueInvoices > 0 ? 'danger' : 'warning'),

            Stat::make('Überfällig', $overdueInvoices)
                ->description('Zahlungsfrist überschritten')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($overdueInvoices > 0 ? 'danger' : 'gray'),

            Stat::make('Offener Betrag', number_format($openAmount, 0, ',', '.') . ' €')
                ->description('Summe offener Rechnungen')
                ->icon('heroicon-o-currency-euro')
                ->color('primary'),

            Stat::make('Bezahlt (Monat)', number_format($paidThisMonth, 0, ',', '.') . ' €')
                ->description(now()->format('F Y'))
                ->icon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }
}
