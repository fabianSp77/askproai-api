<?php

namespace App\Services;

use App\Models\BalanceTopup;
use App\Models\Transaction;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class InvoiceGenerator
{
    private array $companyInfo = [
        'name' => 'AskPro AI GmbH',
        'address' => 'Musterstraße 123',
        'city' => '12345 Berlin',
        'tax_id' => 'DE123456789',
        'email' => 'billing@askproai.de',
        'phone' => '+49 30 12345678',
        'bank' => 'Deutsche Bank',
        'iban' => 'DE89 3704 0044 0532 0130 00',
        'bic' => 'DEUTDEDBBER'
    ];
    
    /**
     * Generate invoice for a topup transaction
     */
    public function generateTopupInvoice(BalanceTopup $topup): string
    {
        $tenant = $topup->tenant;
        $invoiceNumber = $this->generateInvoiceNumber($topup);
        
        // Prepare invoice data
        $data = [
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $topup->paid_at ?? $topup->created_at,
            'due_date' => now(),
            'company' => $this->companyInfo,
            'customer' => [
                'name' => $tenant->name,
                'address' => $tenant->address ?? 'N/A',
                'city' => $tenant->city ?? 'N/A',
                'postal_code' => $tenant->postal_code ?? 'N/A',
                'country' => $tenant->country ?? 'Deutschland',
                'tax_id' => $tenant->tax_id ?? null,
                'email' => $tenant->billing_email ?? $tenant->users->first()?->email
            ],
            'items' => [
                [
                    'description' => 'Guthaben-Aufladung',
                    'quantity' => 1,
                    'unit_price' => $topup->amount,
                    'tax_rate' => 19,
                    'total' => $topup->amount
                ]
            ],
            'subtotal' => $topup->amount,
            'tax_amount' => $topup->amount * 0.19 / 1.19, // Price includes VAT
            'total' => $topup->amount,
            'payment_method' => $topup->payment_method ?? 'Kreditkarte',
            'payment_status' => 'Bezahlt',
            'stripe_reference' => $topup->stripe_payment_intent_id
        ];
        
        // Add bonus if applicable
        if ($topup->bonus_amount > 0) {
            $data['items'][] = [
                'description' => 'Bonus-Guthaben',
                'quantity' => 1,
                'unit_price' => $topup->bonus_amount,
                'tax_rate' => 0,
                'total' => $topup->bonus_amount
            ];
            $data['subtotal'] += $topup->bonus_amount;
            $data['total'] += $topup->bonus_amount;
        }
        
        // Generate PDF
        $pdf = Pdf::loadView('pdf.invoice', $data);
        
        // Store invoice
        $filename = "invoices/{$tenant->id}/{$invoiceNumber}.pdf";
        Storage::disk('private')->put($filename, $pdf->output());
        
        // Store invoice metadata
        $this->storeInvoiceMetadata($topup, $invoiceNumber, $filename);
        
        return $filename;
    }
    
    /**
     * Generate monthly statement for a tenant
     */
    public function generateMonthlyStatement(Tenant $tenant, string $month): string
    {
        $startDate = \Carbon\Carbon::parse($month . '-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        // Get all transactions for the month
        $transactions = Transaction::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get();
        
        // Calculate totals
        $topups = $transactions->where('type', 'topup')->sum('amount_cents');
        $usage = $transactions->where('type', 'usage')->sum('amount_cents') * -1;
        $refunds = $transactions->where('type', 'refund')->sum('amount_cents');
        
        // Group transactions by day
        $dailyTransactions = $transactions->groupBy(function ($item) {
            return $item->created_at->format('Y-m-d');
        });
        
        $statementNumber = $this->generateStatementNumber($tenant, $month);
        
        $data = [
            'statement_number' => $statementNumber,
            'statement_period' => $startDate->format('F Y'),
            'statement_date' => now(),
            'company' => $this->companyInfo,
            'customer' => [
                'name' => $tenant->name,
                'address' => $tenant->address ?? 'N/A',
                'city' => $tenant->city ?? 'N/A',
                'postal_code' => $tenant->postal_code ?? 'N/A',
                'country' => $tenant->country ?? 'Deutschland',
                'customer_id' => $tenant->id
            ],
            'opening_balance' => $this->getOpeningBalance($tenant, $startDate),
            'closing_balance' => $this->getClosingBalance($tenant, $endDate),
            'total_topups' => $topups / 100,
            'total_usage' => $usage / 100,
            'total_refunds' => $refunds / 100,
            'transactions' => $transactions,
            'daily_groups' => $dailyTransactions,
            'summary' => [
                'call_count' => $transactions->where('call_id', '!=', null)->count(),
                'total_minutes' => $this->calculateTotalMinutes($transactions),
                'average_cost' => $usage > 0 ? ($usage / $transactions->where('type', 'usage')->count()) / 100 : 0
            ]
        ];
        
        // Generate PDF
        $pdf = Pdf::loadView('pdf.statement', $data);
        $pdf->setPaper('A4', 'portrait');
        
        // Store statement
        $filename = "statements/{$tenant->id}/{$statementNumber}.pdf";
        Storage::disk('private')->put($filename, $pdf->output());
        
        return $filename;
    }
    
    /**
     * Generate credit note for refunds
     */
    public function generateCreditNote(Transaction $refund): string
    {
        if ($refund->type !== 'refund') {
            throw new \InvalidArgumentException('Transaction must be a refund');
        }
        
        $tenant = $refund->tenant;
        $creditNoteNumber = $this->generateCreditNoteNumber($refund);
        
        $data = [
            'credit_note_number' => $creditNoteNumber,
            'credit_note_date' => $refund->created_at,
            'company' => $this->companyInfo,
            'customer' => [
                'name' => $tenant->name,
                'address' => $tenant->address ?? 'N/A',
                'city' => $tenant->city ?? 'N/A',
                'postal_code' => $tenant->postal_code ?? 'N/A',
                'country' => $tenant->country ?? 'Deutschland'
            ],
            'reason' => $refund->description,
            'amount' => abs($refund->amount_cents) / 100,
            'tax_amount' => abs($refund->amount_cents) * 0.19 / 119, // VAT included
            'total' => abs($refund->amount_cents) / 100,
            'reference_invoice' => $refund->metadata['invoice_number'] ?? null,
            'reference_transaction' => $refund->reference
        ];
        
        // Generate PDF
        $pdf = Pdf::loadView('pdf.credit-note', $data);
        
        // Store credit note
        $filename = "credit-notes/{$tenant->id}/{$creditNoteNumber}.pdf";
        Storage::disk('private')->put($filename, $pdf->output());
        
        return $filename;
    }
    
    /**
     * Generate invoice number
     */
    private function generateInvoiceNumber(BalanceTopup $topup): string
    {
        $year = $topup->created_at->format('Y');
        $month = $topup->created_at->format('m');
        
        // Get next invoice number for this month
        $lastNumber = Cache::remember(
            "invoice.counter.{$year}.{$month}",
            86400,
            function () use ($year, $month) {
                // Query database for last invoice number
                return \DB::table('invoice_metadata')
                    ->where('invoice_number', 'like', "INV-{$year}{$month}-%")
                    ->max('sequence_number') ?? 0;
            }
        );
        
        $nextNumber = $lastNumber + 1;
        Cache::increment("invoice.counter.{$year}.{$month}");
        
        return sprintf('INV-%s%s-%04d', $year, $month, $nextNumber);
    }
    
    /**
     * Generate statement number
     */
    private function generateStatementNumber(Tenant $tenant, string $month): string
    {
        $period = \Carbon\Carbon::parse($month . '-01');
        return sprintf(
            'STMT-%d-%s-%s',
            $tenant->id,
            $period->format('Y'),
            $period->format('m')
        );
    }
    
    /**
     * Generate credit note number
     */
    private function generateCreditNoteNumber(Transaction $refund): string
    {
        $year = $refund->created_at->format('Y');
        $sequence = Cache::increment("credit-note.counter.{$year}");
        
        return sprintf('CN-%s-%04d', $year, $sequence);
    }
    
    /**
     * Store invoice metadata
     */
    private function storeInvoiceMetadata(BalanceTopup $topup, string $invoiceNumber, string $filename): void
    {
        \DB::table('invoice_metadata')->insert([
            'invoice_number' => $invoiceNumber,
            'tenant_id' => $topup->tenant_id,
            'topup_id' => $topup->id,
            'amount' => $topup->amount,
            'filename' => $filename,
            'sequence_number' => (int) substr($invoiceNumber, -4),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    
    /**
     * Get opening balance for a period
     */
    private function getOpeningBalance(Tenant $tenant, \Carbon\Carbon $date): int
    {
        $lastTransaction = Transaction::where('tenant_id', $tenant->id)
            ->where('created_at', '<', $date)
            ->orderBy('created_at', 'desc')
            ->first();
        
        return $lastTransaction ? $lastTransaction->balance_after_cents : 0;
    }
    
    /**
     * Get closing balance for a period
     */
    private function getClosingBalance(Tenant $tenant, \Carbon\Carbon $date): int
    {
        $lastTransaction = Transaction::where('tenant_id', $tenant->id)
            ->where('created_at', '<=', $date->endOfDay())
            ->orderBy('created_at', 'desc')
            ->first();
        
        return $lastTransaction ? $lastTransaction->balance_after_cents : 0;
    }
    
    /**
     * Calculate total minutes from transactions
     */
    private function calculateTotalMinutes($transactions): int
    {
        return $transactions
            ->filter(fn($t) => $t->call_id !== null)
            ->sum(function ($t) {
                return $t->call ? ceil($t->call->duration_seconds / 60) : 0;
            });
    }
    
    /**
     * Send invoice via email
     */
    public function sendInvoiceEmail(string $filename, string $email): void
    {
        $path = Storage::disk('private')->path($filename);
        
        \Mail::to($email)->send(new \App\Mail\InvoiceMail($path));
    }
}