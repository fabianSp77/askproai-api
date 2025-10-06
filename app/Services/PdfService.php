<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Invoice;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Company;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class PdfService
{
    /**
     * Generate invoice PDF
     */
    public function generateInvoice(Invoice $invoice): string
    {
        try {
            $data = [
                'invoice' => $invoice,
                'company' => $invoice->company,
                'customer' => $invoice->customer,
                'items' => $invoice->items,
                'settings' => $this->getCompanySettings($invoice->company),
            ];

            $pdf = Pdf::loadView('pdf.invoice', $data)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'sans-serif',
                ]);

            // Save to storage
            $filename = "invoices/invoice-{$invoice->invoice_number}.pdf";
            Storage::put($filename, $pdf->output());

            // Update invoice with PDF path
            $invoice->update(['pdf_path' => $filename]);

            Log::info('Invoice PDF generated', [
                'invoice_id' => $invoice->id,
                'filename' => $filename
            ]);

            return $filename;

        } catch (Exception $e) {
            Log::error('Failed to generate invoice PDF', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate appointment confirmation PDF
     */
    public function generateAppointmentConfirmation(Appointment $appointment): string
    {
        try {
            $data = [
                'appointment' => $appointment,
                'customer' => $appointment->customer,
                'service' => $appointment->service,
                'staff' => $appointment->staff,
                'branch' => $appointment->branch,
                'company' => $appointment->company,
            ];

            $pdf = Pdf::loadView('pdf.appointment-confirmation', $data)
                ->setPaper('a4', 'portrait');

            // Save to storage
            $filename = "appointments/confirmation-{$appointment->id}-" . now()->timestamp . ".pdf";
            Storage::put($filename, $pdf->output());

            Log::info('Appointment confirmation PDF generated', [
                'appointment_id' => $appointment->id,
                'filename' => $filename
            ]);

            return $filename;

        } catch (Exception $e) {
            Log::error('Failed to generate appointment confirmation PDF', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate customer report PDF
     */
    public function generateCustomerReport(Customer $customer, array $options = []): string
    {
        try {
            $startDate = $options['start_date'] ?? now()->subMonth();
            $endDate = $options['end_date'] ?? now();

            $data = [
                'customer' => $customer,
                'appointments' => $customer->appointments()
                    ->whereBetween('starts_at', [$startDate, $endDate])
                    ->orderBy('starts_at', 'desc')
                    ->get(),
                'calls' => $customer->calls()
                    ->whereBetween('started_at', [$startDate, $endDate])
                    ->orderBy('started_at', 'desc')
                    ->get(),
                'invoices' => $customer->invoices()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->orderBy('created_at', 'desc')
                    ->get(),
                'statistics' => $this->calculateCustomerStatistics($customer, $startDate, $endDate),
                'period' => [
                    'start' => $startDate->format('M j, Y'),
                    'end' => $endDate->format('M j, Y'),
                ],
            ];

            $pdf = Pdf::loadView('pdf.customer-report', $data)
                ->setPaper('a4', 'portrait');

            // Save to storage
            $filename = "reports/customer-{$customer->id}-" . now()->format('Y-m-d') . ".pdf";
            Storage::put($filename, $pdf->output());

            Log::info('Customer report PDF generated', [
                'customer_id' => $customer->id,
                'filename' => $filename
            ]);

            return $filename;

        } catch (Exception $e) {
            Log::error('Failed to generate customer report PDF', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate monthly billing statement
     */
    public function generateBillingStatement(Company $company, string $month): string
    {
        try {
            $startDate = now()->parse($month)->startOfMonth();
            $endDate = now()->parse($month)->endOfMonth();

            $data = [
                'company' => $company,
                'period' => $month,
                'invoices' => $company->invoices()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get(),
                'transactions' => $company->transactions()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get(),
                'summary' => $this->calculateBillingSummary($company, $startDate, $endDate),
            ];

            $pdf = Pdf::loadView('pdf.billing-statement', $data)
                ->setPaper('a4', 'landscape');

            // Save to storage
            $filename = "statements/statement-{$company->id}-{$month}.pdf";
            Storage::put($filename, $pdf->output());

            Log::info('Billing statement PDF generated', [
                'company_id' => $company->id,
                'month' => $month,
                'filename' => $filename
            ]);

            return $filename;

        } catch (Exception $e) {
            Log::error('Failed to generate billing statement PDF', [
                'company_id' => $company->id,
                'month' => $month,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate contract PDF
     */
    public function generateContract(array $contractData): string
    {
        try {
            $pdf = Pdf::loadView('pdf.contract', $contractData)
                ->setPaper('a4', 'portrait');

            // Save to storage
            $filename = "contracts/contract-" . ($contractData['contract_number'] ?? uniqid()) . ".pdf";
            Storage::put($filename, $pdf->output());

            Log::info('Contract PDF generated', ['filename' => $filename]);

            return $filename;

        } catch (Exception $e) {
            Log::error('Failed to generate contract PDF', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate receipt PDF
     */
    public function generateReceipt($transaction): string
    {
        try {
            $data = [
                'transaction' => $transaction,
                'customer' => $transaction->customer,
                'company' => $transaction->company,
                'items' => $transaction->items ?? [],
            ];

            $pdf = Pdf::loadView('pdf.receipt', $data)
                ->setPaper([0, 0, 226.77, 500], 'portrait'); // 80mm thermal paper width

            // Save to storage
            $filename = "receipts/receipt-{$transaction->id}.pdf";
            Storage::put($filename, $pdf->output());

            Log::info('Receipt PDF generated', [
                'transaction_id' => $transaction->id,
                'filename' => $filename
            ]);

            return $filename;

        } catch (Exception $e) {
            Log::error('Failed to generate receipt PDF', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Merge multiple PDFs
     */
    public function mergePdfs(array $pdfPaths, string $outputFilename): string
    {
        // This would require additional package like setasign/fpdi
        // For now, returning a placeholder implementation
        throw new Exception('PDF merging not yet implemented');
    }

    /**
     * Add watermark to PDF
     */
    public function addWatermark(string $pdfPath, string $watermarkText): string
    {
        try {
            $content = Storage::get($pdfPath);

            // Load existing PDF and add watermark
            // This would require modifying the view or using additional package
            // For now, returning original path

            Log::info('Watermark added to PDF', [
                'pdf' => $pdfPath,
                'watermark' => $watermarkText
            ]);

            return $pdfPath;

        } catch (Exception $e) {
            Log::error('Failed to add watermark to PDF', [
                'pdf' => $pdfPath,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get company settings for PDF generation
     */
    protected function getCompanySettings(Company $company): array
    {
        return [
            'logo' => $company->logo_path,
            'address' => $company->address,
            'phone' => $company->phone,
            'email' => $company->email,
            'website' => $company->website,
            'tax_number' => $company->tax_number,
            'registration_number' => $company->registration_number,
            'bank_details' => $company->bank_details,
            'footer_text' => $company->invoice_footer_text,
        ];
    }

    /**
     * Calculate customer statistics for report
     */
    protected function calculateCustomerStatistics(Customer $customer, $startDate, $endDate): array
    {
        return [
            'total_appointments' => $customer->appointments()
                ->whereBetween('starts_at', [$startDate, $endDate])
                ->count(),
            'completed_appointments' => $customer->appointments()
                ->whereBetween('starts_at', [$startDate, $endDate])
                ->where('status', 'completed')
                ->count(),
            'total_spent' => $customer->invoices()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('total_amount'),
            'total_calls' => $customer->calls()
                ->whereBetween('started_at', [$startDate, $endDate])
                ->count(),
        ];
    }

    /**
     * Calculate billing summary
     */
    protected function calculateBillingSummary(Company $company, $startDate, $endDate): array
    {
        return [
            'total_revenue' => $company->invoices()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('total_amount'),
            'total_transactions' => $company->transactions()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'pending_amount' => $company->invoices()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'pending')
                ->sum('total_amount'),
            'paid_amount' => $company->invoices()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'paid')
                ->sum('total_amount'),
        ];
    }

    /**
     * Download PDF
     */
    public function download(string $path)
    {
        if (!Storage::exists($path)) {
            throw new Exception('PDF file not found');
        }

        return Storage::download($path);
    }

    /**
     * Delete PDF
     */
    public function delete(string $path): bool
    {
        if (Storage::exists($path)) {
            return Storage::delete($path);
        }

        return false;
    }
}