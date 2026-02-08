<?php

namespace App\Filament\Widgets\Premium;

use App\Filament\Widgets\Premium\Concerns\HasPremiumStyling;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Invoices List Widget
 *
 * Displays recent invoices with payment status and a payment score progress bar.
 */
class InvoicesListWidget extends Widget
{
    use InteractsWithPageFilters;
    use HasPremiumStyling;

    protected static string $view = 'filament.widgets.premium.invoices-list';
    protected static bool $isLazy = false;
    protected int|string|array $columnSpan = 1;

    /**
     * Calculate payment score (percentage of paid invoices).
     * Note: Caching disabled for reactive filter updates.
     */
    public function calculatePaymentScore(): array
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $query = Invoice::query();

            // Apply company filter if applicable
            if ($companyId) {
                // Check if Invoice has tenant_id or company_id
                if (method_exists(Invoice::class, 'scopeForTenant')) {
                    $query->forTenant($companyId);
                } else {
                    $query->where('company_id', $companyId);
                }
            }

            $total = (clone $query)->count();
            $paid = (clone $query)->where('status', 'paid')->count();

            $score = $total > 0 ? round(($paid / $total) * 100, 1) : 0;

            return [
                'score' => $score,
                'paid' => $paid,
                'total' => $total,
            ];
        } catch (\Throwable $e) {
            Log::error('[InvoicesListWidget] calculatePaymentScore failed', ['error' => $e->getMessage()]);
            return ['score' => 0, 'paid' => 0, 'total' => 0];
        }
    }

    /**
     * Get recent invoices (last 3).
     * Note: Caching disabled for reactive filter updates.
     */
    public function getRecentInvoices(): Collection
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $query = Invoice::query();

            // Apply company filter if applicable
            if ($companyId) {
                if (method_exists(Invoice::class, 'scopeForTenant')) {
                    $query->forTenant($companyId);
                } else {
                    $query->where('company_id', $companyId);
                }
            }

            return $query
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get()
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'number' => $invoice->invoice_number ?? '#' . $invoice->id,
                        'date' => Carbon::parse($invoice->created_at)->format('d.m.Y'),
                        'amount' => $this->formatInvoiceAmount($invoice),
                        'status' => $this->mapInvoiceStatus($invoice->status),
                    ];
                });
        } catch (\Throwable $e) {
            Log::error('[InvoicesListWidget] getRecentInvoices failed', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * Handle filter updates - refresh the widget when filters change.
     */
    public function updatedFilters(): void
    {
        // Widget will automatically re-render with new data
    }

    /**
     * Format invoice amount.
     */
    protected function formatInvoiceAmount($invoice): string
    {
        // Check for total_amount or amount field
        $amount = $invoice->total_amount ?? $invoice->amount ?? 0;

        // Amount might be in cents or euros
        if ($amount > 1000) {
            // Likely in cents
            return number_format($amount / 100, 2, ',', '.') . ' €';
        }

        return number_format($amount, 2, ',', '.') . ' €';
    }

    /**
     * Map invoice status to premium badge status.
     */
    protected function mapInvoiceStatus(?string $status): string
    {
        return match ($status) {
            'paid' => 'paid',
            'unpaid', 'open' => 'unpaid',
            'overdue' => 'overdue',
            default => 'pending',
        };
    }

    /**
     * Get status icon based on type.
     */
    public function getStatusIcon(string $status): string
    {
        return match ($status) {
            'paid' => 'heroicon-o-check-circle',
            'unpaid' => 'heroicon-o-clock',
            'overdue' => 'heroicon-o-exclamation-circle',
            default => 'heroicon-o-document',
        };
    }
}
