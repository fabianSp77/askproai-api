<?php

namespace App\Services;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class TransactionPdfExporter
{
    /**
     * Generate a PDF for a single transaction
     */
    public function exportTransaction(Transaction $transaction): \Illuminate\Http\Response
    {
        $transaction->load(['tenant', 'call', 'appointment', 'topup']);
        
        $data = [
            'transaction' => $transaction,
            'generatedAt' => Carbon::now(),
            'companyInfo' => $this->getCompanyInfo(),
            'relatedTransactions' => $this->getRelatedTransactions($transaction),
        ];
        
        $pdf = Pdf::loadView('pdf.transaction-detail', $data);
        $pdf->setPaper('A4', 'portrait');
        
        $filename = sprintf(
            'transaction_%s_%s.pdf',
            $transaction->id,
            now()->format('Y-m-d_His')
        );
        
        return $pdf->download($filename);
    }
    
    /**
     * Generate a PDF for multiple transactions
     */
    public function exportMultiple($transactions, array $options = []): \Illuminate\Http\Response
    {
        $data = [
            'transactions' => $transactions,
            'generatedAt' => Carbon::now(),
            'companyInfo' => $this->getCompanyInfo(),
            'summary' => $this->calculateSummary($transactions),
            'filters' => $options['filters'] ?? [],
        ];
        
        $pdf = Pdf::loadView('pdf.transaction-list', $data);
        $pdf->setPaper('A4', 'landscape');
        
        $filename = sprintf(
            'transactions_export_%s.pdf',
            now()->format('Y-m-d_His')
        );
        
        return $pdf->download($filename);
    }
    
    /**
     * Generate a monthly statement PDF
     */
    public function exportMonthlyStatement(int $tenantId, Carbon $month): \Illuminate\Http\Response
    {
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();
        
        $transactions = Transaction::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'asc')
            ->get();
        
        $openingBalance = Transaction::where('tenant_id', $tenantId)
            ->where('created_at', '<', $startDate)
            ->orderBy('created_at', 'desc')
            ->value('balance_after_cents') ?? 0;
        
        $data = [
            'transactions' => $transactions,
            'month' => $month,
            'tenant' => \App\Models\Tenant::find($tenantId),
            'openingBalance' => $openingBalance,
            'closingBalance' => $transactions->last()?->balance_after_cents ?? $openingBalance,
            'summary' => $this->calculateMonthlySummary($transactions),
            'companyInfo' => $this->getCompanyInfo(),
        ];
        
        $pdf = Pdf::loadView('pdf.monthly-statement', $data);
        $pdf->setPaper('A4', 'portrait');
        
        $filename = sprintf(
            'statement_%s_%s.pdf',
            $month->format('Y-m'),
            now()->format('Y-m-d_His')
        );
        
        return $pdf->download($filename);
    }
    
    /**
     * Generate an invoice PDF for a topup transaction
     */
    public function exportInvoice(Transaction $transaction): \Illuminate\Http\Response
    {
        if ($transaction->type !== Transaction::TYPE_TOPUP) {
            throw new \InvalidArgumentException('Only topup transactions can be exported as invoices');
        }
        
        $transaction->load(['tenant', 'topup']);
        
        $data = [
            'transaction' => $transaction,
            'invoiceNumber' => $this->generateInvoiceNumber($transaction),
            'dueDate' => $transaction->created_at->addDays(14),
            'companyInfo' => $this->getCompanyInfo(),
            'taxRate' => 0.19, // 19% German VAT
            'netAmount' => $transaction->amount_cents / 100 / 1.19,
            'taxAmount' => ($transaction->amount_cents / 100) - ($transaction->amount_cents / 100 / 1.19),
            'grossAmount' => $transaction->amount_cents / 100,
        ];
        
        $pdf = Pdf::loadView('pdf.invoice', $data);
        $pdf->setPaper('A4', 'portrait');
        
        $filename = sprintf(
            'invoice_%s_%s.pdf',
            $data['invoiceNumber'],
            now()->format('Y-m-d_His')
        );
        
        return $pdf->download($filename);
    }
    
    protected function getCompanyInfo(): array
    {
        return [
            'name' => config('app.company_name', 'AskProAI GmbH'),
            'address' => config('app.company_address', 'Musterstraße 123'),
            'city' => config('app.company_city', '12345 Berlin'),
            'email' => config('app.company_email', 'billing@askproai.de'),
            'phone' => config('app.company_phone', '+49 30 123456789'),
            'vat_id' => config('app.company_vat_id', 'DE123456789'),
            'bank_name' => config('app.company_bank_name', 'Deutsche Bank'),
            'iban' => config('app.company_iban', 'DE89 3704 0044 0532 0130 00'),
            'bic' => config('app.company_bic', 'DEUTDEFF'),
        ];
    }
    
    protected function getRelatedTransactions(Transaction $transaction): \Illuminate\Support\Collection
    {
        if (!$transaction->tenant_id) {
            return collect();
        }
        
        return Transaction::where('tenant_id', $transaction->tenant_id)
            ->where('id', '!=', $transaction->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }
    
    protected function calculateSummary($transactions): array
    {
        $credits = $transactions->where('amount_cents', '>', 0)->sum('amount_cents');
        $debits = $transactions->where('amount_cents', '<', 0)->sum('amount_cents');
        
        return [
            'total_transactions' => $transactions->count(),
            'total_credits' => $credits / 100,
            'total_debits' => abs($debits) / 100,
            'net_amount' => ($credits + $debits) / 100,
            'average_transaction' => $transactions->avg('amount_cents') / 100,
            'highest_credit' => $transactions->where('amount_cents', '>', 0)->max('amount_cents') / 100,
            'highest_debit' => abs($transactions->where('amount_cents', '<', 0)->min('amount_cents')) / 100,
        ];
    }
    
    protected function calculateMonthlySummary($transactions): array
    {
        $summary = $this->calculateSummary($transactions);
        
        // Group by type
        $byType = [];
        foreach ($transactions->groupBy('type') as $type => $group) {
            $byType[$type] = [
                'count' => $group->count(),
                'total' => $group->sum('amount_cents') / 100,
            ];
        }
        
        $summary['by_type'] = $byType;
        
        // Daily averages
        $daysInMonth = $transactions->first()?->created_at->daysInMonth ?? 30;
        $summary['daily_average'] = $summary['net_amount'] / $daysInMonth;
        
        return $summary;
    }
    
    protected function generateInvoiceNumber(Transaction $transaction): string
    {
        $prefix = 'INV';
        $year = $transaction->created_at->format('Y');
        $sequence = str_pad($transaction->id, 6, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$year}-{$sequence}";
    }
}