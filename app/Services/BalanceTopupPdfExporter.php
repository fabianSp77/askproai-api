<?php

namespace App\Services;

use App\Models\BalanceTopup;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BalanceTopupPdfExporter
{
    /**
     * Export single balance topup as PDF
     */
    public function exportTopup(BalanceTopup $topup): \Illuminate\Http\Response
    {
        $data = [
            'topup' => $topup,
            'tenant' => $topup->tenant,
            'title' => 'Aufladungsbeleg',
            'generatedAt' => now()->format('d.m.Y H:i:s'),
        ];
        
        $pdf = PDF::loadView('pdf.balance-topup', $data);
        
        return $pdf->download("aufladung-{$topup->id}-" . now()->format('Y-m-d') . ".pdf");
    }
    
    /**
     * Export multiple topups as PDF
     */
    public function exportMultiple($topups, array $options = []): \Illuminate\Http\Response
    {
        $topups = $topups instanceof Collection ? $topups : collect($topups);
        
        $data = [
            'topups' => $topups,
            'title' => $options['title'] ?? 'Aufladungsübersicht',
            'generatedAt' => now()->format('d.m.Y H:i:s'),
            'summary' => [
                'total' => $topups->sum('amount'),
                'bonusTotal' => $topups->sum('bonus_amount'),
                'grandTotal' => $topups->sum(fn($t) => $t->getTotalAmount()),
                'count' => $topups->count(),
                'succeeded' => $topups->where('status', 'succeeded')->count(),
                'pending' => $topups->where('status', 'pending')->count(),
                'failed' => $topups->where('status', 'failed')->count(),
            ]
        ];
        
        $pdf = PDF::loadView('pdf.balance-topups-list', $data);
        
        $filename = $options['filename'] ?? 'aufladungen-' . now()->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }
    
    /**
     * Export monthly statement for a tenant
     */
    public function exportMonthlyStatement(int $tenantId, Carbon $month): \Illuminate\Http\Response
    {
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();
        
        $topups = BalanceTopup::where('tenant_id', $tenantId)
            ->where('status', 'succeeded')
            ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
            ->orderBy('paid_at')
            ->get();
        
        $tenant = \App\Models\Tenant::find($tenantId);
        
        $data = [
            'topups' => $topups,
            'tenant' => $tenant,
            'month' => $month->format('F Y'),
            'monthGerman' => $this->getGermanMonth($month),
            'year' => $month->format('Y'),
            'generatedAt' => now()->format('d.m.Y H:i:s'),
            'summary' => [
                'total' => $topups->sum('amount'),
                'bonusTotal' => $topups->sum('bonus_amount'),
                'grandTotal' => $topups->sum(fn($t) => $t->getTotalAmount()),
                'count' => $topups->count(),
            ]
        ];
        
        $pdf = PDF::loadView('pdf.balance-topup-statement', $data);
        
        return $pdf->download("aufladungen-{$tenant->slug}-{$month->format('Y-m')}.pdf");
    }
    
    /**
     * Export as receipt/invoice
     */
    public function exportReceipt(BalanceTopup $topup): \Illuminate\Http\Response
    {
        if ($topup->status !== 'succeeded') {
            throw new \Exception('Nur erfolgreiche Aufladungen können als Beleg exportiert werden');
        }
        
        $data = [
            'topup' => $topup,
            'tenant' => $topup->tenant,
            'receiptNumber' => 'RG-' . str_pad($topup->id, 8, '0', STR_PAD_LEFT),
            'title' => 'Zahlungsbeleg',
            'generatedAt' => now()->format('d.m.Y H:i:s'),
            'vatRate' => 19, // German VAT
            'netAmount' => $topup->getTotalAmount() / 1.19,
            'vatAmount' => $topup->getTotalAmount() - ($topup->getTotalAmount() / 1.19),
        ];
        
        $pdf = PDF::loadView('pdf.balance-topup-receipt', $data);
        
        return $pdf->download("beleg-{$topup->id}-" . now()->format('Y-m-d') . ".pdf");
    }
    
    /**
     * Get German month name
     */
    private function getGermanMonth(Carbon $date): string
    {
        $months = [
            1 => 'Januar',
            2 => 'Februar',
            3 => 'März',
            4 => 'April',
            5 => 'Mai',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'August',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Dezember',
        ];
        
        return $months[$date->month] . ' ' . $date->year;
    }
}