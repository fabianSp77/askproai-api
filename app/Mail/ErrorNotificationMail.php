<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Throwable; // Für die Exception

class ErrorNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    // Öffentliche Eigenschaften für die Daten, die wir in der Mail brauchen
    public string $errorMessage;

    public string $errorFile;

    public int $errorLine;

    public ?string $retellCallId;

    public string $payloadSnippet;

    /**
     * Create a new message instance.
     */
    public function __construct(Throwable $exception, ?string $retellCallId, array $payload)
    {
        $this->errorMessage = $exception->getMessage();
        $this->errorFile = $exception->getFile();
        $this->errorLine = $exception->getLine();
        $this->retellCallId = $retellCallId ?? 'N/A';
        $this->payloadSnippet = json_encode(array_slice($payload, 0, 5), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); // Bessere Formatierung
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            // From-Adresse wird aus config('mail.from') geholt
            subject: '⚠️ Kritischer Fehler im AskProAI RetellWebhook',
        );
    }

    /**
     * Get the message content definition.
     * Verweist auf die Markdown-Vorlage.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.errors.notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
