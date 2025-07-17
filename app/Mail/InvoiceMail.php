<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public float $newBalance;
    public float $bonusAmount;
    protected InvoicePdfService $pdfService;

    /**
     * Create a new message instance.
     */
    public function __construct(Invoice $invoice, float $newBalance = 0, float $bonusAmount = 0)
    {
        $this->invoice = $invoice;
        $this->newBalance = $newBalance;
        $this->bonusAmount = $bonusAmount;
        $this->pdfService = app(InvoicePdfService::class);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ihre Rechnung ' . $this->invoice->invoice_number,
            replyTo: [
                ['email' => 'support@askproai.de', 'name' => 'AskProAI Support'],
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invoice',
            with: [
                'invoice' => $this->invoice,
                'newBalance' => $this->newBalance,
                'bonusAmount' => $this->bonusAmount,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        try {
            // Generate or get PDF
            $pdfPath = $this->pdfService->getPdf($this->invoice);
            $filename = $this->pdfService->getDownloadFilename($this->invoice);
            
            return [
                Attachment::fromPath($pdfPath)
                    ->as($filename)
                    ->withMime('application/pdf'),
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to attach invoice PDF to email', [
                'invoice_id' => $this->invoice->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}