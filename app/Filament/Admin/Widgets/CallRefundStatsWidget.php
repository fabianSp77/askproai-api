<?php

namespace App\Filament\Admin\Widgets;

use App\Models\CallCharge;
use App\Services\CallRefundService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class CallRefundStatsWidget extends BaseWidget
{
    protected static ?int $sort = 15;
    
    protected static ?string $pollingInterval = '30s';
    
    public function getHeading(): ?string
    {
        return 'Erstattungs-Statistiken';
    }
    
    protected function getStats(): array
    {
        $refundService = app(CallRefundService::class);
        
        // Get current month stats
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        
        // Get refund stats for all companies (admin view)
        $monthlyCharges = CallCharge::whereBetween('charged_at', [$startOfMonth, $endOfMonth]);
        $monthlyRefunds = CallCharge::whereBetween('refunded_at', [$startOfMonth, $endOfMonth])
            ->where('refund_status', '!=', 'none');
        
        $totalCalls = $monthlyCharges->count();
        $refundedCalls = $monthlyRefunds->count();
        $refundPercentage = $totalCalls > 0 ? round(($refundedCalls / $totalCalls) * 100, 2) : 0;
        
        $totalCharged = $monthlyCharges->sum('amount_charged');
        $totalRefunded = $monthlyRefunds->sum('refunded_amount');
        
        // Get refunds by reason
        $refundsByReason = CallCharge::whereBetween('refunded_at', [$startOfMonth, $endOfMonth])
            ->where('refund_status', '!=', 'none')
            ->groupBy('refund_reason')
            ->selectRaw('refund_reason, COUNT(*) as count')
            ->orderBy('count', 'desc')
            ->limit(3)
            ->get();
        
        $topReason = $refundsByReason->first();
        
        return [
            Stat::make('Erstattungsquote', $refundPercentage . '%')
                ->description('Anteil erstatteter Anrufe')
                ->descriptionIcon($refundPercentage > 2 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->chart([$refundPercentage])
                ->color($refundPercentage > 2 ? 'warning' : 'success'),
                
            Stat::make('Erstattete Anrufe', Number::format($refundedCalls))
                ->description('von ' . Number::format($totalCalls) . ' Anrufen')
                ->descriptionIcon('heroicon-m-receipt-refund')
                ->color('warning'),
                
            Stat::make('Erstattungsbetrag', Number::currency($totalRefunded, 'EUR'))
                ->description(Number::percentage($totalCharged > 0 ? ($totalRefunded / $totalCharged) * 100 : 0, 1) . ' der Einnahmen')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('danger'),
                
            Stat::make('Häufigster Grund', $topReason ? $this->formatReason($topReason->refund_reason) : 'Keine')
                ->description($topReason ? Number::format($topReason->count) . ' Erstattungen' : 'Keine Erstattungen')
                ->descriptionIcon('heroicon-m-information-circle')
                ->color('primary'),
        ];
    }
    
    private function formatReason(?string $reason): string
    {
        if (!$reason) return 'Unbekannt';
        
        return match(true) {
            str_contains($reason, 'Technisches Problem') => 'Tech. Problem',
            str_contains($reason, 'Qualitätsproblem') => 'Qualität',
            str_contains($reason, 'Falsche Nummer') => 'Falsche Nr.',
            str_contains($reason, 'Kundenbeschwerde') => 'Beschwerde',
            str_contains($reason, 'Testanruf') => 'Test',
            default => 'Sonstiges'
        };
    }
}