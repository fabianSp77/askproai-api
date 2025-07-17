<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class CallSummaryBatchEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public array $summaryData;
    public ?array $csvAttachment;

    /**
     * Create a new message instance.
     */
    public function __construct(array $summaryData, ?array $csvAttachment = null)
    {
        $this->summaryData = $summaryData;
        $this->csvAttachment = $csvAttachment;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $frequency = $this->summaryData['frequency'] === 'hourly' ? 'Stündliche' : 'Tägliche';
        $subject = sprintf(
            '%s Anrufzusammenfassung - %s - %d Anrufe',
            $frequency,
            $this->summaryData['company']->name,
            $this->summaryData['totalCalls']
        );

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.call-summary-batch',
            with: $this->summaryData,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->csvAttachment) {
            $attachments[] = Attachment::fromData(
                fn () => $this->csvAttachment['content'],
                $this->csvAttachment['filename']
            )->withMime('text/csv');
        }

        return $attachments;
    }
}