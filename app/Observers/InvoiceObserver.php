<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\CommissionCalculation;
use Illuminate\Support\Facades\Log;

class InvoiceObserver
{
    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice): void
    {
        // Check if invoice was just marked as paid
        if ($invoice->isDirty('status') && $invoice->status === 'paid' && $invoice->getOriginal('status') !== 'paid') {
            $this->calculateCommission($invoice);
        }
    }

    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        // If invoice is created as already paid, calculate commission
        if ($invoice->status === 'paid') {
            $this->calculateCommission($invoice);
        }
    }

    /**
     * Calculate commission for a paid invoice.
     */
    protected function calculateCommission(Invoice $invoice): void
    {
        try {
            // Check if commission already calculated
            $existingCalculation = CommissionCalculation::where('invoice_id', $invoice->id)->first();
            
            if ($existingCalculation) {
                Log::info('Commission already calculated for invoice', ['invoice_id' => $invoice->id]);
                return;
            }
            
            // Calculate commission
            $calculation = CommissionCalculation::calculateForInvoice($invoice);
            
            if ($calculation) {
                Log::info('Commission calculated for invoice', [
                    'invoice_id' => $invoice->id,
                    'commission_amount' => $calculation->commission_amount,
                    'reseller_id' => $calculation->reseller_company_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to calculate commission for invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}