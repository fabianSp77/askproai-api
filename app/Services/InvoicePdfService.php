<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Carbon\Carbon;

class InvoicePdfService
{
    /**
     * Generate PDF for an invoice
     */
    public function generatePdf(Invoice $invoice): string
    {
        try {
            // Load invoice with all relationships
            $invoice->load(['company', 'items', 'billingPeriods']);
            
            // Generate HTML from Blade template
            $html = View::make('pdf.invoice', [
                'invoice' => $invoice,
            ])->render();
            
            // Generate PDF using Browsershot
            $pdf = $this->createPdfFromHtml($html, $invoice->number);
            
            // Store PDF
            $path = $this->storePdf($invoice, $pdf);
            
            // Update invoice with PDF path
            $invoice->update(['pdf_path' => $path]);
            
            // Create archive entry for GoBD compliance
            $this->archiveInvoice($invoice, $pdf);
            
            return $path;
            
        } catch (\Exception $e) {
            Log::error('Failed to generate invoice PDF', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Get or generate PDF for an invoice
     */
    public function getPdf(Invoice $invoice): string
    {
        // Check if PDF already exists
        if ($invoice->pdf_path && Storage::disk('invoices')->exists($invoice->pdf_path)) {
            return Storage::disk('invoices')->path($invoice->pdf_path);
        }
        
        // Generate new PDF
        $path = $this->generatePdf($invoice);
        return Storage::disk('invoices')->path($path);
    }
    
    /**
     * Get PDF content as string
     */
    public function getPdfContent(Invoice $invoice): string
    {
        $path = $this->getPdf($invoice);
        return file_get_contents($path);
    }
    
    /**
     * Create PDF from HTML using Browsershot
     */
    protected function createPdfFromHtml(string $html, string $filename): string
    {
        $browsershot = Browsershot::html($html)
            ->format('A4')
            ->margins(0, 0, 0, 0)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->pdf();
            
        // Add PDF/A metadata for compliance
        $browsershot->setOption('displayHeaderFooter', false)
            ->setOption('printBackground', true)
            ->setOption('preferCSSPageSize', true);
            
        return $browsershot;
    }
    
    /**
     * Store PDF in filesystem
     */
    protected function storePdf(Invoice $invoice, string $pdfContent): string
    {
        $year = $invoice->invoice_date->format('Y');
        $month = $invoice->invoice_date->format('m');
        $filename = $invoice->number . '.pdf';
        
        // Path structure: invoices/2025/01/ASK-2025-00001-7.pdf
        $path = "{$year}/{$month}/{$filename}";
        
        // Ensure directory exists
        Storage::disk('invoices')->makeDirectory("{$year}/{$month}");
        
        // Store PDF
        Storage::disk('invoices')->put($path, $pdfContent);
        
        return $path;
    }
    
    /**
     * Archive invoice for GoBD compliance
     */
    protected function archiveInvoice(Invoice $invoice, string $pdfContent): void
    {
        // Calculate hash for immutability
        $hash = hash('sha256', $pdfContent);
        
        // Store in archive (separate disk with write-once policy)
        $archivePath = "archive/{$invoice->company_id}/{$invoice->number}.pdf";
        Storage::disk('archive')->put($archivePath, $pdfContent);
        
        // Update invoice with archive information
        $invoice->update([
            'archive_hash' => $hash,
            'archived_at' => now(),
        ]);
        
        // Create audit log entry
        if (class_exists(\App\Services\InvoiceComplianceService::class)) {
            app(InvoiceComplianceService::class)->createAuditLog($invoice, 'pdf_generated', [
                'hash' => $hash,
                'path' => $archivePath,
            ]);
        }
    }
    
    /**
     * Generate filename for download
     */
    public function getDownloadFilename(Invoice $invoice): string
    {
        $date = $invoice->invoice_date->format('Y-m-d');
        return "Rechnung_{$invoice->number}_{$date}.pdf";
    }
    
    /**
     * Regenerate PDF (only if not finalized)
     */
    public function regeneratePdf(Invoice $invoice): string
    {
        // Check if invoice can be modified
        if ($invoice->finalized_at) {
            throw new \Exception('Cannot regenerate PDF for finalized invoice');
        }
        
        // Delete existing PDF
        if ($invoice->pdf_path && Storage::disk('invoices')->exists($invoice->pdf_path)) {
            Storage::disk('invoices')->delete($invoice->pdf_path);
        }
        
        // Generate new PDF
        return $this->generatePdf($invoice);
    }
    
    /**
     * Generate batch PDFs for multiple invoices
     */
    public function generateBatchPdfs(array $invoiceIds): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];
        
        foreach ($invoiceIds as $invoiceId) {
            try {
                $invoice = Invoice::findOrFail($invoiceId);
                $this->generatePdf($invoice);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][$invoiceId] = $e->getMessage();
                Log::error('Batch PDF generation failed', [
                    'invoice_id' => $invoiceId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $results;
    }
    
    /**
     * Create preview HTML for invoice
     */
    public function getPreviewHtml(Invoice $invoice): string
    {
        $invoice->load(['company', 'items', 'billingPeriods']);
        
        return View::make('pdf.invoice', [
            'invoice' => $invoice,
            'preview' => true,
        ])->render();
    }
    
    /**
     * Check if PDF exists
     */
    public function pdfExists(Invoice $invoice): bool
    {
        return $invoice->pdf_path && Storage::disk('invoices')->exists($invoice->pdf_path);
    }
    
    /**
     * Get PDF URL for download
     */
    public function getPdfUrl(Invoice $invoice): ?string
    {
        if (!$this->pdfExists($invoice)) {
            return null;
        }
        
        // Generate temporary signed URL (valid for 1 hour)
        return Storage::disk('invoices')->temporaryUrl(
            $invoice->pdf_path,
            now()->addHour()
        );
    }
}