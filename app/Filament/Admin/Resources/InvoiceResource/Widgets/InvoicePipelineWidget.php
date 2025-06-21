<?php

namespace App\Filament\Admin\Resources\InvoiceResource\Widgets;

use App\Models\Invoice;
use Filament\Widgets\Widget;

class InvoicePipelineWidget extends Widget
{
    protected static string $view = 'filament.admin.resources.invoice-resource.widgets.invoice-pipeline-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    public function getPipelineData(): array
    {
        // Get counts and amounts for each status
        $draft = Invoice::where('status', 'draft');
        $open = Invoice::where('status', 'open');
        $paid = Invoice::where('status', 'paid');
        $cancelled = Invoice::where('status', 'cancelled');
        
        $draftCount = $draft->count();
        $openCount = $open->count();
        $paidCount = $paid->count();
        $cancelledCount = $cancelled->count();
        
        $draftAmount = $draft->sum('total');
        $openAmount = $open->sum('total');
        $paidAmount = $paid->sum('total');
        $cancelledAmount = $cancelled->sum('total');
        
        // Calculate conversion rates
        $totalInvoices = $draftCount + $openCount + $paidCount + $cancelledCount;
        $draftToOpenRate = ($openCount + $paidCount) > 0 && $totalInvoices > 0 
            ? round((($openCount + $paidCount) / $totalInvoices) * 100, 1) 
            : 0;
        $openToPaidRate = $paidCount > 0 && ($openCount + $paidCount) > 0 
            ? round(($paidCount / ($openCount + $paidCount)) * 100, 1) 
            : 0;
        
        // Get average processing times
        $avgDraftToOpen = $this->getAverageProcessingTime('draft', 'open');
        $avgOpenToPaid = $this->getAverageProcessingTime('open', 'paid');
        
        return [
            'pipeline' => [
                [
                    'name' => 'Entwürfe',
                    'icon' => 'heroicon-o-pencil-square',
                    'color' => 'gray',
                    'count' => $draftCount,
                    'amount' => $draftAmount,
                    'percentage' => $totalInvoices > 0 ? round(($draftCount / $totalInvoices) * 100, 1) : 0,
                ],
                [
                    'name' => 'Offen',
                    'icon' => 'heroicon-o-clock',
                    'color' => 'warning',
                    'count' => $openCount,
                    'amount' => $openAmount,
                    'percentage' => $totalInvoices > 0 ? round(($openCount / $totalInvoices) * 100, 1) : 0,
                ],
                [
                    'name' => 'Bezahlt',
                    'icon' => 'heroicon-o-check-circle',
                    'color' => 'success',
                    'count' => $paidCount,
                    'amount' => $paidAmount,
                    'percentage' => $totalInvoices > 0 ? round(($paidCount / $totalInvoices) * 100, 1) : 0,
                ],
            ],
            'cancelled' => [
                'name' => 'Storniert',
                'icon' => 'heroicon-o-x-circle',
                'color' => 'danger',
                'count' => $cancelledCount,
                'amount' => $cancelledAmount,
                'percentage' => $totalInvoices > 0 ? round(($cancelledCount / $totalInvoices) * 100, 1) : 0,
            ],
            'conversions' => [
                'draft_to_open' => $draftToOpenRate,
                'open_to_paid' => $openToPaidRate,
            ],
            'processing_times' => [
                'draft_to_open' => $avgDraftToOpen,
                'open_to_paid' => $avgOpenToPaid,
            ],
            'total_amount' => $draftAmount + $openAmount + $paidAmount,
            'potential_revenue' => $draftAmount + $openAmount,
        ];
    }
    
    protected function getAverageProcessingTime(string $fromStatus, string $toStatus): string
    {
        // This would need proper implementation with status change tracking
        // For now, return placeholder
        return match($fromStatus . '_' . $toStatus) {
            'draft_open' => '~2 Tage',
            'open_paid' => '~14 Tage',
            default => 'N/A',
        };
    }
}